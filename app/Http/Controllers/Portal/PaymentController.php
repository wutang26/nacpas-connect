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
    /**
     * Create a new package purchase and initiate FastLipa payment.
     */
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

        /*
         * Normalize Tanzanian mobile numbers.
         */
        $phone = preg_replace(
            '/[\s\-()]/',
            '',
            $validated['phone']
        );

        if (str_starts_with($phone, '0')) {
            $phone = '+255' . substr($phone, 1);
        } elseif (str_starts_with($phone, '255')) {
            $phone = '+' . $phone;
        } elseif (preg_match('/^[67][0-9]{8}$/', $phone)) {
            $phone = '+255' . $phone;
        }

        $phoneValidation = Validator::make(
            ['phone' => $phone],
            [
                'phone' => [
                    'required',
                    'regex:/^\+255[67][0-9]{8}$/',
                ],
            ],
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

        /*
         * FastLipa allows a maximum of 5 create requests per minute.
         */
        $rateLimitKey = 'fastlipa-create-transactions-global';

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            throw ValidationException::withMessages([
                'phone' =>
                    'Maombi mengi ya malipo yametumwa kwa sasa. Tafadhali subiri dakika moja ujaribu tena.',
            ]);
        }

        /*
         * Create subscription, payment and access token.
         *
         * Only the token hash is stored in the database.
         */
        $result = DB::transaction(function () use (
            $site,
            $validated,
            $phone
        ) {
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

            /*
             * Generate a secure access token.
             */
            $accessToken = Str::random(64);

            $accessTokenHash = hash('sha256', $accessToken);

            /*
             * Snapshot package information.
             */
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

            /*
             * Create an independent subscription for this purchase.
             */
            $subscriptionId = DB::table('subscriptions')->insertGetId([
                'site_id' => $site->id,
                'customer_id' => $customer->id,
                'package_id' => $package->id,
                'reference' => $subscriptionReference,
                'package_name' => $package->name,
                'amount' => $package->price,
                'duration_minutes' => $package->duration_minutes,
                'package_snapshot' => json_encode($packageSnapshot),
                'access_token_hash' => $accessTokenHash,
                'status' => 'pending',
                'starts_at' => null,
                'expires_at' => null,
                'activated_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            /*
             * Create an independent payment.
             */
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
                'access_token' => $accessToken,
            ];
        });

        /*
         * Keep original access token in Laravel session.
         *
         * Never expose it in the URL, Inertia props,
         * payment metadata or application logs.
         */
        $tokenSessionKey =
            "subscription_access_tokens.{$site->id}.{$result['payment_reference']}";

        $request->session()->put(
            $tokenSessionKey,
            $result['access_token']
        );

        /*
         * Count this attempt before making the external request.
         */
        RateLimiter::hit($rateLimitKey, 60);

        /*
         * Prepare FastLipa request.
         */
        $payload = [
            'amount' => $result['amount'],
            'number' => preg_replace(
                '/^\+/',
                '',
                $result['phone']
            ),
            'reference' => $result['payment_reference'],
        ];

        /*
         * Only provide a webhook URL when APP_URL is publicly reachable.
         */
        $appUrl = rtrim(
            (string) config('app.url'),
            '/'
        );

        $appHost = parse_url($appUrl, PHP_URL_HOST);

        if (
            $appHost &&
            !in_array(
                $appHost,
                ['localhost', '127.0.0.1', '::1'],
                true
            )
        ) {
            $payload['webhook_url'] =
                $appUrl . '/api/webhooks/fastlipa';
        }

        $payment = Payment::findOrFail($result['payment_id']);

        try {
            $gatewayResult = $fastLipa->createTransaction($payload);

            $body = $gatewayResult['body'] ?? [];

            $gatewayData = $body['data'] ?? [];

            $tranId =
                $gatewayData['tranID'] ??
                $gatewayData['tranid'] ??
                null;

            /*
             * FastLipa rejected the initiation request.
             *
             * A successful HTTP response alone is not enough.
             * We also require FastLipa's application status
             * and a valid transaction ID.
             */
            if (
                !($gatewayResult['successful'] ?? false) ||
                !is_array($body) ||
                !($body['status'] ?? false) ||
                !is_string($tranId) ||
                $tranId === ''
            ) {
                DB::transaction(function () use (
                    $payment,
                    $gatewayResult,
                    $body
                ) {
                    $lockedPayment = Payment::whereKey($payment->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$lockedPayment) {
                        return;
                    }

                    /*
                     * Do not overwrite a successful payment.
                     * A webhook may already have processed it.
                     */
                    if ($lockedPayment->status === 'pending') {
                        $metadata = $lockedPayment->metadata ?? [];

                        $lockedPayment->update([
                            'status' => 'failed',
                            'metadata' => array_merge($metadata, [
                                'payment_initiated' => false,
                                'initiation_state' => 'rejected',
                                'gateway_http_status' =>
                                    $gatewayResult['http_status'] ?? null,
                                'gateway_response' => $body,
                            ]),
                        ]);

                        DB::table('subscriptions')
                            ->where('id', $lockedPayment->subscription_id)
                            ->where('status', 'pending')
                            ->update([
                                'status' => 'cancelled',
                                'updated_at' => now(),
                            ]);
                    }
                });

                $request->session()->forget($tokenSessionKey);

                Log::warning(
                    'FastLipa rejected payment initiation.',
                    [
                        'payment_reference' =>
                            $payment->internal_reference,
                        'http_status' =>
                            $gatewayResult['http_status'] ?? null,
                    ]
                );
            } else {
                /*
                 * FastLipa accepted the initiation request.
                 *
                 * This does NOT mean the customer has paid.
                 * It also does not independently prove that
                 * the USSD prompt reached the customer's phone.
                 */
                DB::transaction(function () use (
                    $payment,
                    $tranId,
                    $body,
                    $gatewayData
                ) {
                    $lockedPayment = Payment::whereKey($payment->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$lockedPayment) {
                        return;
                    }

                    /*
                     * Do not overwrite a payment already confirmed
                     * by the webhook.
                     */
                    if ($lockedPayment->status === 'pending') {
                        $metadata = $lockedPayment->metadata ?? [];

                        $lockedPayment->update([
                            'provider' => 'fastlipa',
                            'provider_reference' => $tranId,
                            'metadata' => array_merge($metadata, [
                                'payment_initiated' => true,
                                'initiation_state' => 'initiated',
                                'gateway_payment_status' =>
                                    $gatewayData['payment_status'] ?? 'PENDING',
                                'gateway_response' => $body,
                            ]),
                        ]);
                    }
                });
            }
        } catch (Throwable $exception) {
            /*
             * A timeout or connection error may happen after FastLipa
             * has already received the request.
             *
             * Never automatically retry or mark failed in this case.
             */
            try {
                DB::transaction(function () use (
                    $payment,
                    $exception
                ) {
                    $lockedPayment = Payment::whereKey($payment->id)
                        ->lockForUpdate()
                        ->first();

                    if (
                        !$lockedPayment ||
                        $lockedPayment->status !== 'pending'
                    ) {
                        return;
                    }

                    $metadata = $lockedPayment->metadata ?? [];

                    $lockedPayment->update([
                        'metadata' => array_merge($metadata, [
                            'payment_initiated' => false,
                            'initiation_state' => 'uncertain',
                            'initiation_uncertain' => true,
                            'error_type' => class_basename($exception),
                        ]),
                    ]);
                });
            } catch (Throwable $updateException) {
                Log::error(
                    'Could not update uncertain FastLipa payment state.',
                    [
                        'payment_reference' =>
                            $payment->internal_reference,
                        'exception' =>
                            class_basename($updateException),
                    ]
                );
            }

            Log::error(
                'FastLipa payment initiation result is uncertain.',
                [
                    'payment_reference' =>
                        $payment->internal_reference,
                    'exception' =>
                        class_basename($exception),
                ]
            );
        }

        return redirect()->route('portal.payment.pending', [
            'site' => $site->slug,
            'reference' => $result['payment_reference'],
        ]);
    }

    /**
     * Display payment status.
     *
     * Payment status comes from the database.
     * The original access token is never exposed to Inertia.
     */
    public function pending(
        Request $request,
        Site $site,
        string $reference
    ): Response {
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
                'payments.paid_at',

                'subscriptions.reference as subscription_reference',
                'subscriptions.package_name',
                'subscriptions.status as subscription_status',
            ])
            ->first();

        abort_unless($payment, 404);

        $metadata = json_decode(
            $payment->metadata ?? '{}',
            true
        ) ?: [];

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

                'payment_initiated' =>
                    (bool) ($metadata['payment_initiated'] ?? false),

                'gateway_payment_status' =>
                    $metadata['gateway_payment_status'] ?? null,

                'verified_by_fastlipa' =>
                    (bool) ($metadata['verified_by_fastlipa'] ?? false),

                'package_name' => $payment->package_name,

                'subscription_reference' =>
                    $payment->subscription_reference,

                'subscription_status' =>
                    $payment->subscription_status,

                'created_at' => $payment->created_at,
                'paid_at' => $payment->paid_at,
            ],
        ]);
    }
}
