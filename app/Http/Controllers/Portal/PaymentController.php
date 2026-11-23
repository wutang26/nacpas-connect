<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Site;
use App\Services\Payments\FastLipaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PaymentController extends Controller
{
    public function checkout(
        Request $request,
        Site $site,
        FastLipaService $fastLipa
    ) {
        abort_if(
            $site->status !== 'active' ||
            $site->organization?->status !== 'active',
            404
        );

        $validated = $request->validate([
            'package_id' => ['required', 'integer', 'min:1'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        // Normalize Tanzanian mobile numbers.
        $phone = preg_replace('/[\s\-()]/', '', $validated['phone']);

        if (str_starts_with($phone, '0')) {
            $phone = '+255' . substr($phone, 1);
        } elseif (str_starts_with($phone, '255')) {
            $phone = '+' . $phone;
        } elseif (preg_match('/^[67][0-9]{8}$/', $phone)) {
            $phone = '+255' . $phone;
        }

        $phoneValidation = Validator::make(
            ['phone' => $phone],
            ['phone' => ['required', 'regex:/^\+255[67][0-9]{8}$/']],
            [
                'phone.regex' =>
                    'Tafadhali ingiza namba sahihi ya simu ya Tanzania.',
            ]
        );

        if ($phoneValidation->fails()) {
            throw ValidationException::withMessages([
                'phone' => $phoneValidation->errors()->first('phone'),
            ]);
        }

        // FastLipa allows a maximum of 5 create requests per minute.
        // This limit is shared across the application.
        $rateLimitKey = 'fastlipa-create-transactions-global';

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            throw ValidationException::withMessages([
                'phone' =>
                    'Maombi mengi ya malipo yametumwa kwa sasa. Tafadhali subiri dakika moja ujaribu tena.',
            ]);
        }

        // Create local records first. No payment is considered successful here.
        $result = DB::transaction(function () use ($site, $validated, $phone) {
            $package = $site->packages()
                ->where('id', $validated['package_id'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$package) {
                throw ValidationException::withMessages([
                    'package_id' =>
                        'Kifurushi hiki hakipatikani kwa sasa.',
                ]);
            }

            $customer = Customer::where('site_id', $site->id)
                ->where('phone', $phone)
                ->lockForUpdate()
                ->first();

            if (!$customer) {
                $customer = new Customer();

                $customer->forceFill([
                    'site_id' => $site->id,
                    'phone' => $phone,
                    'status' => 'active',
                ]);

                $customer->save();
            }

            if ($customer->status !== 'active') {
                throw ValidationException::withMessages([
                    'phone' =>
                        'Namba hii ya simu imezuiwa kutumia huduma.',
                ]);
            }

            $subscriptionReference =
                'NCP-SUB-' . Str::upper(Str::random(12));

            $paymentReference =
                'NCP-PAY-' . Str::upper(Str::random(12));

            $packageSnapshot = [
                'package_id' => $package->id,
                'name' => $package->name,
                'description' => $package->description,
                'price' => (string) $package->price,
                'currency' => $package->currency,
                'duration_minutes' => $package->duration_minutes,
                'download_speed_kbps' => $package->download_speed_kbps,
                'upload_speed_kbps' => $package->upload_speed_kbps,
                'data_limit_bytes' => $package->data_limit_bytes,
                'device_limit' => $package->device_limit,
            ];

            $now = now();

            $subscriptionId = DB::table('subscriptions')->insertGetId([
                'site_id' => $site->id,
                'customer_id' => $customer->id,
                'package_id' => $package->id,
                'reference' => $subscriptionReference,
                'package_name' => $package->name,
                'amount' => $package->price,
                'duration_minutes' => $package->duration_minutes,
                'package_snapshot' => json_encode($packageSnapshot),
                'status' => 'pending',
                'starts_at' => null,
                'expires_at' => null,
                'activated_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $paymentId = DB::table('payments')->insertGetId([
                'subscription_id' => $subscriptionId,
                'customer_id' => $customer->id,
                'provider' => 'fastlipa',
                'provider_reference' => null,
                'internal_reference' => $paymentReference,
                'phone' => $phone,
                'amount' => $package->price,
                'currency' => $package->currency ?: 'TZS',
                'status' => 'pending',
                'paid_at' => null,
                'metadata' => json_encode([
                    'mode' => 'live',
                    'payment_initiated' => false,
                    'initiation_state' => 'not_started',
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return [
                'payment_id' => $paymentId,
                'payment_reference' => $paymentReference,
                'amount' => (float) $package->price,
                'phone' => $phone,
            ];
        });

        // Count this attempt before making the external API request.
        RateLimiter::hit($rateLimitKey, 60);

        $payload = [
            'amount' => $result['amount'],
            'number' => preg_replace('/^\+/', '', $result['phone']),
            'reference' => $result['payment_reference'],
        ];

        // Only provide a webhook URL when APP_URL is publicly reachable.
        $appUrl = rtrim((string) config('app.url'), '/');
        $appHost = parse_url($appUrl, PHP_URL_HOST);

        if (
            $appHost &&
            !in_array($appHost, ['localhost', '127.0.0.1', '::1'], true)
        ) {
            $payload['webhook_url'] =
                $appUrl . '/api/webhooks/fastlipa';
        }

        $payment = Payment::findOrFail($result['payment_id']);

        try {
            $gatewayResult = $fastLipa->createTransaction($payload);
            $body = $gatewayResult['body'] ?? [];
            $gatewayData = $body['data'] ?? [];
            $tranId = $gatewayData['tranID'] ?? $gatewayData['tranid'] ?? null;

            if (
                !($gatewayResult['successful'] ?? false) ||
                !($body['status'] ?? false) ||
                !is_string($tranId) ||
                $tranId === ''
            ) {
                $payment->update([
                    'status' => 'failed',
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'payment_initiated' => false,
                        'initiation_state' => 'rejected',
                        'gateway_http_status' =>
                            $gatewayResult['http_status'] ?? null,
                        'gateway_response' => $body,
                    ]),
                ]);

                Log::warning('FastLipa rejected payment initiation.', [
                    'payment_reference' => $payment->internal_reference,
                    'http_status' => $gatewayResult['http_status'] ?? null,
                ]);
            } else {
                $payment->update([
                    'provider' => 'fastlipa',
                    'provider_reference' => $tranId,
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'payment_initiated' => true,
                        'initiation_state' => 'initiated',
                        'gateway_payment_status' =>
                            $gatewayData['payment_status'] ?? 'PENDING',
                        'gateway_response' => $body,
                    ]),
                ]);
            }
        } catch (Throwable $exception) {
            // A timeout or connection error may happen after FastLipa
            // has received the request. Never retry create automatically.
            $payment->update([
                'metadata' => array_merge($payment->metadata ?? [], [
                    'payment_initiated' => false,
                    'initiation_state' => 'uncertain',
                    'initiation_uncertain' => true,
                    'error_type' => class_basename($exception),
                ]),
            ]);

            Log::error('FastLipa payment initiation result is uncertain.', [
                'payment_reference' => $payment->internal_reference,
                'exception' => class_basename($exception),
            ]);
        }

        return redirect()->route('portal.payment.pending', [
            'site' => $site->slug,
            'reference' => $result['payment_reference'],
        ]);
    }

    public function pending(Site $site, string $reference): Response
    {
        abort_if(
            $site->status !== 'active' ||
            $site->organization?->status !== 'active',
            404
        );

        $payment = DB::table('payments')
            ->join(
                'subscriptions',
                'payments.subscription_id',
                '=',
                'subscriptions.id'
            )
            ->join(
                'customers',
                'payments.customer_id',
                '=',
                'customers.id'
            )
            ->where('payments.internal_reference', $reference)
            ->where('subscriptions.site_id', $site->id)
            ->select([
                'payments.internal_reference',
                'payments.provider',
                'payments.provider_reference',
                'payments.amount',
                'payments.currency',
                'payments.phone',
                'payments.status',
                'payments.metadata',
                'payments.created_at',
                'subscriptions.reference as subscription_reference',
                'subscriptions.package_name',
                'subscriptions.status as subscription_status',
            ])
            ->first();

        abort_unless($payment, 404);

        $metadata = json_decode($payment->metadata ?? '{}', true) ?: [];

        return Inertia::render('Portal/PaymentPending', [
            'site' => [
                'name' => $site->name,
                'slug' => $site->slug,
            ],
            'payment' => [
                'reference' => $payment->internal_reference,
                'provider' => $payment->provider,
                'provider_reference' => $payment->provider_reference,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'phone' => $payment->phone,
                'status' => $payment->status,
                'initiation_state' =>
                    $metadata['initiation_state'] ?? 'unknown',
                'package_name' => $payment->package_name,
                'subscription_reference' =>
                    $payment->subscription_reference,
                'subscription_status' => $payment->subscription_status,
            ],
        ]);
    }
}
