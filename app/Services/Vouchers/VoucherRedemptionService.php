<?php

namespace App\Services\Vouchers;

use App\Models\Customer;
use App\Models\NetworkIntegration;
use App\Models\Package;
use App\Models\Site;
use App\Models\Subscription;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use App\Models\WifiPortalContext;
use App\Models\WifiSession;
use App\Services\Network\NetworkCommandService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class VoucherRedemptionService
{
    public function redeem(
        Site $site,
        string $code,
        string $phone,
        string $contextToken,
        string $sessionId
    ): array {
        $canonicalCode = $this->normalizeCode($code);
        $phone = $this->normalizePhone($phone);

        $sessionHash = hash('sha256', $sessionId);

        $result = DB::transaction(function () use (
            $site,
            $canonicalCode,
            $phone,
            $contextToken,
            $sessionHash
        ) {
            $lockedSite = Site::query()
                ->with('organization')
                ->lockForUpdate()
                ->find($site->id);

            if (
                ! $lockedSite ||
                $lockedSite->status !== 'active' ||
                $lockedSite->organization?->status !== 'active'
            ) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'Huduma ya WiFi haipatikani kwa sasa.',
                ]);
            }

            $context = WifiPortalContext::query()
                ->where('context_token', $contextToken)
                ->where('site_id', $lockedSite->id)
                ->where('session_hash', $sessionHash)
                ->where('context_status', 'captured')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if (
                ! $context ||
                empty($context->client_mac) ||
                empty($context->client_ip)
            ) {
                throw ValidationException::withMessages([
                    'voucher_code' =>
                        'Taarifa za kifaa hazijathibitishwa au zimeisha muda. Fungua upya ukurasa wa WiFi.',
                ]);
            }

            /*
             * Lock order must match VoucherAgentPurchaseService:
             * batch -> purchase -> agent -> voucher.
             *
             * First read the voucher without a lock to discover its batch.
             * The batch lock serializes this redemption with purchase
             * confirmation and voucher allocation.
             */
            $voucherReference = Voucher::query()
                ->where('code_hash', hash('sha256', $canonicalCode))
                ->first();

            if (
                ! $voucherReference ||
                (int) $voucherReference->site_id !== (int) $lockedSite->id
            ) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'Voucher si sahihi au si ya eneo hili.',
                ]);
            }

            $batch = \App\Models\VoucherBatch::query()
                ->whereKey($voucherReference->voucher_batch_id)
                ->lockForUpdate()
                ->first();

            if (
                ! $batch ||
                $batch->status !== 'active' ||
                (int) $batch->site_id !== (int) $lockedSite->id ||
                ($batch->expires_at && $batch->expires_at->lessThanOrEqualTo(now()))
            ) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'Voucher hii imeisha muda au batch yake imefungwa.',
                ]);
            }

            // Re-read after obtaining the batch lock. Allocation cannot
            // change this voucher while this transaction holds that lock.
            $voucherSnapshot = Voucher::query()
                ->whereKey($voucherReference->id)
                ->first();

            if (! $voucherSnapshot) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'Voucher haipatikani.',
                ]);
            }

            $purchase = null;
            $agent = null;

            if ($voucherSnapshot->status === 'assigned') {
                $purchase = $voucherSnapshot->agentPurchase()
                    ->lockForUpdate()
                    ->first();

                $agent = $voucherSnapshot->agent()
                    ->lockForUpdate()
                    ->first();
            }

            // Voucher is deliberately locked last.
            $voucher = Voucher::query()
                ->whereKey($voucherSnapshot->id)
                ->lockForUpdate()
                ->first();

            if (
                ! $voucher ||
                (int) $voucher->site_id !== (int) $lockedSite->id ||
                (int) $voucher->voucher_batch_id !== (int) $batch->id ||
                $voucher->status !== $voucherSnapshot->status ||
                $voucher->voucher_agent_id !== $voucherSnapshot->voucher_agent_id ||
                $voucher->voucher_agent_purchase_id !== $voucherSnapshot->voucher_agent_purchase_id
            ) {
                throw ValidationException::withMessages([
                    'voucher_code' =>
                        'Hali ya voucher imebadilika. Tafadhali jaribu tena.',
                ]);
            }

            if (! in_array($voucher->status, ['available', 'assigned'], true)) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'Voucher hii imeshatumika au imefungwa.',
                ]);
            }

            if (
                $voucher->expires_at &&
                $voucher->expires_at->lessThanOrEqualTo(now())
            ) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'Voucher hii imeisha muda.',
                ]);
            }

            if ($voucher->status === 'assigned') {
                if (
                    ! $purchase ||
                    ! $agent ||
                    $agent->status !== 'active' ||
                    $purchase->status !== 'paid' ||
                    ! $purchase->paid_at ||
                    (int) $purchase->voucher_agent_id !== (int) $agent->id ||
                    (int) $purchase->voucher_batch_id !== (int) $batch->id ||
                    (int) $purchase->site_id !== (int) $lockedSite->id
                ) {
                    throw ValidationException::withMessages([
                        'voucher_code' =>
                            'Voucher ya wakala haijathibitishwa kuwa imelipiwa.',
                    ]);
                }
            } elseif (
                $voucher->voucher_agent_id !== null ||
                $voucher->voucher_agent_purchase_id !== null
            ) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'Taarifa za voucher hazilingani na hali yake.',
                ]);
            }

            $package = Package::query()
                ->whereKey($voucher->package_id)
                ->where('site_id', $lockedSite->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (
                ! $package ||
                (int) $batch->package_id !== (int) $package->id
            ) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'Kifurushi cha voucher hakipatikani.',
                ]);
            }

            $clientMac = strtoupper(
                str_replace('-', ':', trim($context->client_mac))
            );

            if (! preg_match('/^(?:[0-9A-F]{2}:){5}[0-9A-F]{2}$/', $clientMac)) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'MAC address iliyopokelewa kutoka Omada si sahihi.',
                ]);
            }

            $clientIp = trim((string) $context->client_ip);

            if (! filter_var($clientIp, FILTER_VALIDATE_IP)) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'IP address iliyopokelewa kutoka Omada si sahihi.',
                ]);
            }

            $customer = Customer::query()
                ->where('site_id', $lockedSite->id)
                ->where('phone', $phone)
                ->lockForUpdate()
                ->first();

            if (! $customer) {
                $customer = Customer::create([
                    'site_id' => $lockedSite->id,
                    'phone' => $phone,
                    'status' => 'active',
                ]);
            }

            if ($customer->status !== 'active') {
                throw ValidationException::withMessages([
                    'phone' => 'Namba hii ya simu imezuiwa kutumia huduma.',
                ]);
            }

            $now = now();
            $expiresAt = $now->copy()->addMinutes(
                (int) $package->duration_minutes
            );

            $deviceLimit = (int) $package->device_limit;

            if ($deviceLimit < 1) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'Kifurushi kina device limit isiyo sahihi.',
                ]);
            }

            $accessToken = Str::random(64);

            $snapshot = [
                'package_id' => $package->id,
                'name' => $package->name,
                'description' => $package->description,
                'price' => (string) $voucher->retail_price,
                'currency' => $voucher->currency,
                'duration_minutes' => (int) $package->duration_minutes,
                'download_speed_kbps' => $package->download_speed_kbps,
                'upload_speed_kbps' => $package->upload_speed_kbps,
                'data_limit_bytes' => $package->data_limit_bytes,
                'device_limit' => $deviceLimit,
                'purchase_method' => 'voucher',
                'voucher_id' => $voucher->id,
            ];

            $subscription = Subscription::create([
                'site_id' => $lockedSite->id,
                'customer_id' => $customer->id,
                'package_id' => $package->id,
                'reference' => 'NCP-SUB-' . Str::upper(Str::random(16)),
                'package_name' => $package->name,
                'amount' => $voucher->retail_price,
                'duration_minutes' => $package->duration_minutes,
                'package_snapshot' => $snapshot,
                'access_token_hash' => hash('sha256', $accessToken),
                'status' => 'active',
                'starts_at' => $now,
                'expires_at' => $expiresAt,
                'activated_at' => $now,
            ]);

            $wifiSession = WifiSession::create([
                'site_id' => $lockedSite->id,
                'subscription_id' => $subscription->id,
                'customer_id' => $customer->id,
                'client_mac' => $clientMac,
                'client_ip' => $clientIp,
                'started_at' => null,
                'expires_at' => $expiresAt,
                'ended_at' => null,
                'bytes_in' => 0,
                'bytes_out' => 0,
                'status' => 'pending',
            ]);

            VoucherRedemption::create([
                'voucher_id' => $voucher->id,
                'voucher_batch_id' => $batch->id,
                'site_id' => $lockedSite->id,
                'package_id' => $package->id,
                'voucher_agent_id' => $voucher->voucher_agent_id,
                'customer_id' => $customer->id,
                'subscription_id' => $subscription->id,
                'wifi_session_id' => $wifiSession->id,
                'client_mac' => $clientMac,
                'client_ip' => $clientIp,
                'redeemed_at' => $now,
            ]);

            $voucher->update([
                'status' => 'redeemed',
                'redeemed_at' => $now,
            ]);

            $context->update([
                'subscription_id' => $subscription->id,
                'wifi_session_id' => $wifiSession->id,
                'context_status' => 'consumed',
            ]);

            return [
                'subscription' => $subscription,
                'wifi_session' => $wifiSession,
                'access_token' => $accessToken,
                'site_id' => $lockedSite->id,
            ];
        }, 3);

        /*
         * Network authorization is deliberately outside the redemption
         * transaction. A network failure must not undo a valid voucher
         * redemption or create a second redemption on retry.
         */
        $commandStatus = 'not_configured';

        $integration = NetworkIntegration::query()
            ->where('site_id', $result['site_id'])
            ->where('provider', 'omada')
            ->where('status', 'active')
            ->first();

        if ($integration) {
            try {
                $command = app(NetworkCommandService::class)
                    ->authorizeClient($integration, $result['wifi_session']);

                $commandStatus = $command->status;
            } catch (Throwable $exception) {
                Log::warning('Voucher redeemed but network authorization could not be queued.', [
                    'wifi_session_id' => $result['wifi_session']->id,
                    'exception' => class_basename($exception),
                ]);

                $commandStatus = 'not_queued';
            }
        }

        return [
            'subscription' => $result['subscription'],
            'wifi_session' => $result['wifi_session'],
            'access_token' => $result['access_token'],
            'command_status' => $commandStatus,
        ];
    }

    private function normalizeCode(string $code): string
    {
        $code = strtoupper(trim($code));
        $raw = preg_replace('/[\s-]+/', '', $code);

        if (str_starts_with($raw, 'NCP')) {
            $raw = substr($raw, 3);
        }

        if (
            strlen($raw) !== 16 ||
            ! preg_match('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{16}$/', $raw)
        ) {
            throw ValidationException::withMessages([
                'voucher_code' => 'Ingiza voucher code sahihi.',
            ]);
        }

        return 'NCP-' . implode('-', str_split($raw, 4));
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-()]/', '', trim($phone));

        if (str_starts_with($phone, '0')) {
            $phone = '+255' . substr($phone, 1);
        } elseif (str_starts_with($phone, '255')) {
            $phone = '+' . $phone;
        } elseif (preg_match('/^[67][0-9]{8}$/', $phone)) {
            $phone = '+255' . $phone;
        }

        if (! preg_match('/^\+255[67][0-9]{8}$/', $phone)) {
            throw ValidationException::withMessages([
                'phone' => 'Tafadhali ingiza namba sahihi ya simu ya Tanzania.',
            ]);
        }

        return $phone;
    }
}
