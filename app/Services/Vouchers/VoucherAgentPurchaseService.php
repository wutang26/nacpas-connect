<?php

namespace App\Services\Vouchers;

use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherAgent;
use App\Models\VoucherAgentPurchase;
use App\Models\VoucherBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class VoucherAgentPurchaseService
{
    /**
     * Agent requests a quantity of vouchers from a batch.
     *
     * Pending purchases reserve stock logically. Actual vouchers are
     * assigned only after payment is confirmed.
     */
    public function requestPurchase(
        VoucherAgent $agent,
        VoucherBatch $batch,
        int $quantity
    ): VoucherAgentPurchase {
        if ($quantity < 1 || $quantity > 5000) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be between 1 and 5,000.',
            ]);
        }

        return DB::transaction(function () use (
            $agent,
            $batch,
            $quantity
        ) {
            // Always lock the batch first to serialize stock operations.
            $lockedBatch = VoucherBatch::query()
                ->lockForUpdate()
                ->findOrFail($batch->id);

            $lockedAgent = VoucherAgent::query()
                ->lockForUpdate()
                ->findOrFail($agent->id);

            if ($lockedAgent->status !== 'active') {
                throw ValidationException::withMessages([
                    'agent' => 'This voucher agent is not active.',
                ]);
            }

            if (
                (int) $lockedAgent->site_id !== (int) $lockedBatch->site_id
            ) {
                throw ValidationException::withMessages([
                    'batch' => 'The selected batch does not belong to this agent site.',
                ]);
            }

            if (
                $lockedBatch->status !== 'active' ||
                (
                    $lockedBatch->expires_at &&
                    $lockedBatch->expires_at->lessThanOrEqualTo(now())
                )
            ) {
                throw ValidationException::withMessages([
                    'batch' => 'This voucher batch is closed or expired.',
                ]);
            }

            $wholesaleCents = $this->toCents(
                $lockedBatch->wholesale_unit_price
            );

            $retailCents = $this->toCents(
                $lockedBatch->retail_unit_price
            );

            if ($wholesaleCents < 1 || $retailCents < $wholesaleCents) {
                throw ValidationException::withMessages([
                    'batch' => 'The batch pricing is invalid.',
                ]);
            }

            $maxAmountCents = 999999999999;

            if ($wholesaleCents > intdiv($maxAmountCents, $quantity)) {
                throw ValidationException::withMessages([
                    'quantity' => 'The purchase total exceeds the allowed amount.',
                ]);
            }

            $availableStock = Voucher::query()
                ->where('voucher_batch_id', $lockedBatch->id)
                ->where('site_id', $lockedBatch->site_id)
                ->where('status', 'available')
                ->whereNull('voucher_agent_id')
                ->whereNull('voucher_agent_purchase_id')
                ->count();

            $pendingReservations = VoucherAgentPurchase::query()
                ->where('voucher_batch_id', $lockedBatch->id)
                ->where('status', 'pending')
                ->sum('quantity');

            $remainingStock = $availableStock - (int) $pendingReservations;

            if ($quantity > $remainingStock) {
                throw ValidationException::withMessages([
                    'quantity' => sprintf(
                        'Insufficient stock. Available for new requests: %d.',
                        max(0, $remainingStock)
                    ),
                ]);
            }

            $totalCents = $wholesaleCents * $quantity;

            $purchase = VoucherAgentPurchase::create([
                'voucher_agent_id' => $lockedAgent->id,
                'voucher_batch_id' => $lockedBatch->id,
                'site_id' => $lockedBatch->site_id,
                'reference' => 'NCP-VP-' . Str::upper(Str::random(20)),
                'quantity' => $quantity,
                'wholesale_unit_price' => $this->fromCents($wholesaleCents),
                'retail_unit_price' => $this->fromCents($retailCents),
                'total_amount' => $this->fromCents($totalCents),
                'currency' => $lockedBatch->currency,
                'status' => 'pending',
            ]);

            return $purchase->load(['agent.user', 'batch']);
        }, 3);
    }

    /**
     * Confirm payment and atomically allocate vouchers to the agent.
     */
    public function confirmPayment(
        VoucherAgentPurchase $purchase,
        User $confirmedBy,
        string $paymentMethod,
        ?string $paymentReference = null
    ): VoucherAgentPurchase {
        $paymentMethod = trim($paymentMethod);
        $paymentReference = $paymentReference !== null
            ? trim($paymentReference)
            : null;

        if ($paymentMethod === '' || mb_strlen($paymentMethod) > 50) {
            throw ValidationException::withMessages([
                'payment_method' => 'A valid payment method is required.',
            ]);
        }

        if (
            $paymentReference !== null &&
            mb_strlen($paymentReference) > 120
        ) {
            throw ValidationException::withMessages([
                'payment_reference' => 'Payment reference is too long.',
            ]);
        }

        return DB::transaction(function () use (
            $purchase,
            $confirmedBy,
            $paymentMethod,
            $paymentReference
        ) {
            // The batch lock serializes all allocations from this batch.
            $initialPurchase = VoucherAgentPurchase::query()
                ->findOrFail($purchase->id);

            $batch = VoucherBatch::query()
                ->lockForUpdate()
                ->findOrFail($initialPurchase->voucher_batch_id);

            $lockedPurchase = VoucherAgentPurchase::query()
                ->lockForUpdate()
                ->findOrFail($purchase->id);

            // Idempotency: never allocate a paid purchase a second time.
            if ($lockedPurchase->status === 'paid') {
                $allocatedCount = Voucher::query()
                    ->where('voucher_agent_purchase_id', $lockedPurchase->id)
                    ->where('voucher_agent_id', $lockedPurchase->voucher_agent_id)
                    ->count();

                if ($allocatedCount !== (int) $lockedPurchase->quantity) {
                    throw new RuntimeException(
                        'Paid purchase allocation count does not match its quantity. Manual reconciliation is required.'
                    );
                }

                return $lockedPurchase->load([
                    'agent.user',
                    'batch',
                    'vouchers',
                ]);
            }

            if ($lockedPurchase->status !== 'pending') {
                throw ValidationException::withMessages([
                    'purchase' => 'Only pending purchases can be confirmed.',
                ]);
            }

            $agent = VoucherAgent::query()
                ->lockForUpdate()
                ->findOrFail($lockedPurchase->voucher_agent_id);

            if (
                $agent->status !== 'active' ||
                (int) $agent->site_id !== (int) $batch->site_id ||
                (int) $lockedPurchase->site_id !== (int) $batch->site_id
            ) {
                throw ValidationException::withMessages([
                    'purchase' => 'The agent is inactive or does not belong to this batch site.',
                ]);
            }

            if (
                $batch->status !== 'active' ||
                (
                    $batch->expires_at &&
                    $batch->expires_at->lessThanOrEqualTo(now())
                )
            ) {
                throw ValidationException::withMessages([
                    'purchase' => 'The voucher batch is closed or expired.',
                ]);
            }

            $quantity = (int) $lockedPurchase->quantity;

            $vouchers = Voucher::query()
                ->where('voucher_batch_id', $batch->id)
                ->where('site_id', $batch->site_id)
                ->where('status', 'available')
                ->whereNull('voucher_agent_id')
                ->whereNull('voucher_agent_purchase_id')
                ->orderBy('id')
                ->limit($quantity)
                ->lockForUpdate()
                ->get(['id']);

            if ($vouchers->count() !== $quantity) {
                throw ValidationException::withMessages([
                    'purchase' => 'Insufficient unassigned vouchers remain in this batch.',
                ]);
            }

            $voucherIds = $vouchers->pluck('id')->all();
            $now = now();

            $allocatedCount = Voucher::query()
                ->whereIn('id', $voucherIds)
                ->where('status', 'available')
                ->whereNull('voucher_agent_id')
                ->whereNull('voucher_agent_purchase_id')
                ->update([
                    'voucher_agent_id' => $agent->id,
                    'voucher_agent_purchase_id' => $lockedPurchase->id,
                    'status' => 'assigned',
                    'assigned_at' => $now,
                    'updated_at' => $now,
                ]);

            if ($allocatedCount !== $quantity) {
                throw new RuntimeException(
                    'Voucher allocation was incomplete. The transaction will be rolled back.'
                );
            }

            $lockedPurchase->update([
                'status' => 'paid',
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentReference,
                'confirmed_by' => $confirmedBy->id,
                'paid_at' => $now,
            ]);

            return $lockedPurchase->fresh()->load([
                'agent.user',
                'batch',
                'vouchers',
            ]);
        }, 3);
    }

    /**
     * Cancel a pending purchase and release its logical stock reservation.
     */
    public function cancelPurchase(
        VoucherAgentPurchase $purchase,
        ?string $reason = null
    ): VoucherAgentPurchase {
        return DB::transaction(function () use ($purchase, $reason) {
            $initialPurchase = VoucherAgentPurchase::query()
                ->findOrFail($purchase->id);

            VoucherBatch::query()
                ->lockForUpdate()
                ->findOrFail($initialPurchase->voucher_batch_id);

            $lockedPurchase = VoucherAgentPurchase::query()
                ->lockForUpdate()
                ->findOrFail($purchase->id);

            if ($lockedPurchase->status === 'cancelled') {
                return $lockedPurchase->load(['agent.user', 'batch']);
            }

            if ($lockedPurchase->status !== 'pending') {
                throw ValidationException::withMessages([
                    'purchase' => 'Only pending purchases can be cancelled.',
                ]);
            }

            $notes = trim((string) $reason);

            $lockedPurchase->update([
                'status' => 'cancelled',
                'notes' => $notes !== ''
                    ? $notes
                    : $lockedPurchase->notes,
            ]);

            return $lockedPurchase->fresh()->load([
                'agent.user',
                'batch',
            ]);
        }, 3);
    }

    /**
     * Convert a decimal database amount to integer cents without floats.
     */
    private function toCents(string|int|float $amount): int
    {
        $value = trim((string) $amount);

        if (! preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $value)) {
            throw new RuntimeException('Invalid monetary value in voucher batch.');
        }

        [$whole, $fraction] = array_pad(
            explode('.', $value, 2),
            2,
            '0'
        );

        $fraction = str_pad($fraction, 2, '0');

        return ((int) $whole * 100) + (int) substr($fraction, 0, 2);
    }

    private function fromCents(int $cents): string
    {
        return intdiv($cents, 100)
            . '.'
            . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
