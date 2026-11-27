<?php

namespace App\Services\Vouchers;

use App\Models\Organization;
use App\Models\Package;
use App\Models\Site;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class VoucherBatchService
{
    /**
     * Generate a voucher batch.
     *
     * Voucher codes are stored encrypted and returned to authorized users.
     */
    public function createBatch(
        User $creator,
        Organization $organization,
        Site $site,
        Package $package,
        array $data
    ): array {
        $quantity = filter_var(
            $data['quantity'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($quantity === false || $quantity < 1 || $quantity > 5000) {
            throw ValidationException::withMessages([
                'quantity' => 'Batch quantity must be between 1 and 5,000.',
            ]);
        }

        $wholesaleCents = $this->toCents(
            $data['wholesale_unit_price'] ?? null,
            'wholesale_unit_price'
        );

        $retailCents = $this->toCents(
            $data['retail_unit_price'] ?? null,
            'retail_unit_price'
        );

        if ($wholesaleCents <= 0) {
            throw ValidationException::withMessages([
                'wholesale_unit_price' =>
                    'Wholesale price must be greater than zero.',
            ]);
        }

        if ($retailCents <= 0 || $retailCents < $wholesaleCents) {
            throw ValidationException::withMessages([
                'retail_unit_price' =>
                    'Retail price must be greater than or equal to wholesale price and greater than zero.',
            ]);
        }

        if (
            (int) $site->organization_id !== (int) $organization->id ||
            (int) $package->site_id !== (int) $site->id
        ) {
            throw ValidationException::withMessages([
                'package_id' =>
                    'The selected package does not belong to this organization site.',
            ]);
        }

        if (
            $site->status !== 'active' ||
            $organization->status !== 'active' ||
            !$package->is_active
        ) {
            throw ValidationException::withMessages([
                'package_id' =>
                    'The organization, site and package must all be active.',
            ]);
        }

        $expiresAt = null;

        if (!empty($data['expires_at'])) {
            try {
                $expiresAt = Carbon::parse($data['expires_at']);
            } catch (\Throwable $exception) {
                throw ValidationException::withMessages([
                    'expires_at' => 'Please provide a valid expiry date.',
                ]);
            }

            if ($expiresAt->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages([
                    'expires_at' => 'Voucher batch expiry must be in the future.',
                ]);
            }
        }

        return DB::transaction(function () use (
            $creator,
            $organization,
            $site,
            $package,
            $data,
            $quantity,
            $wholesaleCents,
            $retailCents,
            $expiresAt
        ) {
            // Recheck package and site state inside the transaction.
            $lockedOrganization = Organization::query()
                ->lockForUpdate()
                ->findOrFail($organization->id);

            $lockedSite = Site::query()
                ->lockForUpdate()
                ->findOrFail($site->id);

            $lockedPackage = Package::query()
                ->lockForUpdate()
                ->findOrFail($package->id);

            if (
                (int) $lockedSite->organization_id !== (int) $lockedOrganization->id ||
                (int) $lockedPackage->site_id !== (int) $lockedSite->id ||
                $lockedOrganization->status !== 'active' ||
                $lockedSite->status !== 'active' ||
                !$lockedPackage->is_active
            ) {
                throw ValidationException::withMessages([
                    'package_id' =>
                        'The selected site or package is no longer available.',
                ]);
            }

            // Read currency from the package after acquiring its row lock.
            $currency = strtoupper((string) $lockedPackage->currency);

            if (!preg_match('/^[A-Z]{3}$/', $currency)) {
                throw ValidationException::withMessages([
                    'package_id' => 'The package has an invalid currency.',
                ]);
            }

            if ($expiresAt && $expiresAt->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages([
                    'expires_at' =>
                        'Voucher batch expiry must be in the future.',
                ]);
            }

            $batch = VoucherBatch::create([
                'organization_id' => $lockedOrganization->id,
                'site_id' => $lockedSite->id,
                'package_id' => $lockedPackage->id,
                'created_by' => $creator->id,
                'batch_number' => $this->generateBatchNumber(),
                'quantity' => $quantity,
                'generated_count' => 0,
                'wholesale_unit_price' => $this->fromCents($wholesaleCents),
                'retail_unit_price' => $this->fromCents($retailCents),
                'currency' => $currency,
                'status' => 'active',
                'expires_at' => $expiresAt,
                'notes' => $data['notes'] ?? null,
            ]);

            $codes = [];

            for ($i = 0; $i < $quantity; $i++) {
                $code = $this->generateUniqueCode();

                Voucher::create([
                    'voucher_batch_id' => $batch->id,
                    'site_id' => $lockedSite->id,
                    'package_id' => $lockedPackage->id,
                    'code_hash' => hash('sha256', $code),
                    'encrypted_code' => Crypt::encryptString($code),
                    'code_suffix' => substr($code, -4),
                    'retail_price' => $this->fromCents($retailCents),
                    'currency' => $currency,
                    'status' => 'available',
                    'expires_at' => $expiresAt,
                ]);

                // Plaintext is returned once to the authorized creator.
                $codes[] = $code;
            }

            $batch->update([
                'generated_count' => count($codes),
            ]);

            return [
                'batch' => $batch->fresh([
                    'organization',
                    'site',
                    'package',
                    'creator',
                ]),
                'codes' => $codes,
            ];
        }, 3);
    }

    /**
     * Convert a decimal monetary value to integer cents.
     * No floating-point arithmetic is used.
     */
    private function toCents(mixed $value, string $field): int
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw ValidationException::withMessages([
                $field => 'Please provide a valid monetary amount.',
            ]);
        }

        $value = trim((string) $value);

        if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $value)) {
            throw ValidationException::withMessages([
                $field => 'Amount must be a valid positive amount with up to two decimal places.',
            ]);
        }

        [$whole, $fraction] = array_pad(
            explode('.', $value, 2),
            2,
            '0'
        );

        $fraction = str_pad($fraction, 2, '0');

        return ((int) $whole * 100) + (int) $fraction;
    }

    private function fromCents(int $cents): string
    {
        return intdiv($cents, 100)
            . '.'
            . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function generateBatchNumber(): string
    {
        do {
            $number = 'NCP-BAT-'
                . now()->format('YmdHis')
                . '-'
                . Str::upper(Str::random(5));
        } while (
            VoucherBatch::where('batch_number', $number)->exists()
        );

        return $number;
    }

    private function generateUniqueCode(): string
    {
        // Excludes visually confusing characters: 0, O, 1 and I.
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $raw = '';

            for ($i = 0; $i < 16; $i++) {
                $raw .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $code = 'NCP-' . implode('-', str_split($raw, 4));

            if (!Voucher::where(
                'code_hash',
                hash('sha256', $code)
            )->exists()) {
                return $code;
            }
        }

        throw new RuntimeException(
            'Unable to generate a unique voucher code. Please retry.'
        );
    }
}
