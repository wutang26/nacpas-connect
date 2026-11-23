<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentCallback;
use App\Models\Subscription;
use App\Services\Payments\FastLipaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class FastLipaWebhookController extends Controller
{
    public function handle(
        Request $request,
        FastLipaService $fastLipa
    ): JsonResponse {
        /*
         * 1. Verify webhook signature.
         */
        $secret = config('services.fastlipa.webhook_secret');

        if (!$secret) {
            Log::critical(
                'FastLipa webhook secret is not configured.'
            );

            return response()->json([
                'success' => false,
                'message' => 'Webhook is not configured.',
            ], 503);
        }

        $signature = $request->header(
            'X-FastLipa-Signature'
        );

        if (
            !$signature ||
            !str_starts_with($signature, 'sha256=')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Missing or invalid signature.',
            ], 401);
        }

        $rawBody = $request->getContent();

        $expectedSignature = 'sha256=' . hash_hmac(
            'sha256',
            $rawBody,
            $secret
        );

        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning(
                'FastLipa webhook signature verification failed.'
            );

            return response()->json([
                'success' => false,
                'message' => 'Invalid signature.',
            ], 401);
        }

        /*
         * 2. Validate FastLipa payload.
         */
        $payload = $request->json()->all();

        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? null;

        if (!is_array($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook data.',
            ], 422);
        }

        $tranId = $data['tranid']
            ?? $data['tranID']
            ?? null;

        $reference = $data['reference'] ?? null;

        $callbackStatus = strtoupper(
            (string) ($data['payment_status'] ?? '')
        );

        if (
            !is_string($tranId) ||
            $tranId === '' ||
            !is_string($reference) ||
            $reference === '' ||
            !in_array($event, [
                'payment.completed',
                'payment.cancelled',
            ], true)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid callback payload.',
            ], 422);
        }

        /*
         * Check callback status against the event.
         */
        if (
            $event === 'payment.completed' &&
            $callbackStatus !== 'COMPLETED'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Callback status does not match event.',
            ], 422);
        }

        if (
            $event === 'payment.cancelled' &&
            !in_array(
                $callbackStatus,
                ['CANCELLED', 'FAILED'],
                true
            )
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Callback status does not match event.',
            ], 422);
        }

        /*
         * 3. Generate deterministic event ID.
         *
         * FastLipa sample payload does not include event_id.
         * The same transaction and event therefore produce
         * the same ID when FastLipa retries delivery.
         */
        $eventId = $payload['event_id']
            ?? ($tranId . ':' . $event . ':' . $callbackStatus);

        if (
            !is_string($eventId) ||
            $eventId === '' ||
            strlen($eventId) > 255
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid event ID.',
            ], 422);
        }

        /*
         * 4. Find local payment.
         */
        $payment = Payment::query()
            ->where('provider', 'fastlipa')
            ->where('internal_reference', $reference)
            ->first();

        /*
         * firstOrCreate handles concurrent insertion
         * using the unique provider/event_id index.
         */
        $callback = PaymentCallback::firstOrCreate(
            [
                'provider' => 'fastlipa',
                'event_id' => $eventId,
            ],
            [
                'payment_id' => $payment?->id,
                'reference' => $reference,
                'payload' => $payload,
                'signature_verified' => true,
                'processing_status' => 'pending',
            ]
        );

        if ($callback->processing_status === 'processed') {
            return response()->json([
                'success' => true,
                'message' => 'Callback already processed.',
            ]);
        }

        if (!$payment) {
            $callback->update([
                'processing_status' => 'failed',
            ]);

            Log::warning(
                'FastLipa callback has no matching payment.',
                [
                    'reference' => $reference,
                    'tranid' => $tranId,
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Payment reference not found.',
            ], 404);
        }

        /*
         * 5. Prevent transaction ID reuse.
         */
        $anotherPayment = Payment::query()
            ->where('provider', 'fastlipa')
            ->where('provider_reference', $tranId)
            ->where('id', '!=', $payment->id)
            ->exists();

        if ($anotherPayment) {
            $callback->update([
                'processing_status' => 'failed',
            ]);

            Log::critical(
                'FastLipa transaction ID belongs to another payment.',
                [
                    'reference' => $reference,
                    'tranid' => $tranId,
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Transaction reference conflict.',
            ], 409);
        }

        /*
         * 6. Verify directly with FastLipa.
         * Webhook data alone is not enough to activate a package.
         */
        try {
            $gatewayResult = $fastLipa->transactionStatus(
                $tranId
            );

            $gatewayBody = $gatewayResult['body'] ?? null;

            if (
                !($gatewayResult['successful'] ?? false) ||
                !is_array($gatewayBody)
            ) {
                throw new RuntimeException(
                    'FastLipa status request was unsuccessful.'
                );
            }

            $gatewayData = $gatewayBody['data'] ?? null;

            if (!is_array($gatewayData)) {
                throw new RuntimeException(
                    'FastLipa returned invalid transaction data.'
                );
            }

            $verifiedTranId = $gatewayData['tranid']
                ?? $gatewayData['tranID']
                ?? null;

            $verifiedStatus = strtoupper(
                (string) (
                    $gatewayData['payment_status'] ?? ''
                )
            );

            $verifiedAmount = $gatewayData['amount'] ?? null;

            if (
                !is_string($verifiedTranId) ||
                !hash_equals($tranId, $verifiedTranId)
            ) {
                throw new RuntimeException(
                    'FastLipa transaction ID mismatch.'
                );
            }

            if (
                !is_numeric($verifiedAmount) ||
                !is_finite((float) $verifiedAmount)
            ) {
                throw new RuntimeException(
                    'FastLipa returned an invalid amount.'
                );
            }

            /*
             * Compare amounts in cents.
             */
            $gatewayAmountCents = (int) round(
                (float) $verifiedAmount * 100
            );

            $localAmountCents = (int) round(
                (float) $payment->amount * 100
            );

            if ($gatewayAmountCents !== $localAmountCents) {
                throw new RuntimeException(
                    'FastLipa transaction amount mismatch.'
                );
            }

            /*
             * Verify final status.
             */
            if (
                $event === 'payment.completed' &&
                $verifiedStatus !== 'COMPLETED'
            ) {
                throw new RuntimeException(
                    'FastLipa has not confirmed payment as COMPLETED.'
                );
            }

            if (
                $event === 'payment.cancelled' &&
                !in_array(
                    $verifiedStatus,
                    ['CANCELLED', 'FAILED'],
                    true
                )
            ) {
                throw new RuntimeException(
                    'FastLipa has not confirmed payment cancellation.'
                );
            }

            /*
             * 7. Apply result atomically.
             */
            DB::transaction(function () use (
                $callback,
                $payment,
                $tranId,
                $event,
                $verifiedStatus,
                $gatewayData
            ) {
                $lockedCallback = PaymentCallback::query()
                    ->lockForUpdate()
                    ->findOrFail($callback->id);

                /*
                 * A concurrent request may have finished
                 * processing this callback already.
                 */
                if (
                    $lockedCallback->processing_status === 'processed'
                ) {
                    return;
                }

                $lockedPayment = Payment::query()
                    ->lockForUpdate()
                    ->findOrFail($payment->id);

                if (
                    $lockedPayment->provider_reference &&
                    $lockedPayment->provider_reference !== $tranId
                ) {
                    throw new RuntimeException(
                        'Payment is linked to another FastLipa transaction.'
                    );
                }

                $subscription = Subscription::query()
                    ->where('id', $lockedPayment->subscription_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                 * COMPLETED: activate subscription only after
                 * FastLipa confirms payment and amount.
                 */
                if ($event === 'payment.completed') {
                    if ($subscription->duration_minutes <= 0) {
                        throw new RuntimeException(
                            'Subscription duration must be greater than zero.'
                        );
                    }

                    /*
                     * Do not reset an already active subscription
                     * or extend its expiry on duplicate callbacks.
                     */
                    if ($lockedPayment->status !== 'successful') {
                        $now = now();

                        $subscription->update([
                            'status' => 'active',
                            'starts_at' => $now,
                            'expires_at' => $now->copy()->addMinutes(
                                (int) $subscription->duration_minutes
                            ),
                            'activated_at' => $now,
                        ]);

                        $lockedPayment->update([
                            'status' => 'successful',
                            'provider_reference' => $tranId,
                            'paid_at' => $now,
                            'metadata' => array_merge(
                                $lockedPayment->metadata ?? [],
                                [
                                    'payment_initiated' => true,
                                    'initiation_state' => 'completed',
                                    'verified_by_fastlipa' => true,
                                    'verified_payment_status' => $verifiedStatus,
                                    'verified_transaction' => $gatewayData,
                                ]
                            ),
                        ]);
                    }
                }

                /*
                 * CANCELLED: never downgrade a successful payment.
                 */
                if (
                    $event === 'payment.cancelled' &&
                    $lockedPayment->status === 'pending'
                ) {
                    $lockedPayment->update([
                        'status' => 'failed',
                        'provider_reference' => $tranId,
                        'metadata' => array_merge(
                            $lockedPayment->metadata ?? [],
                            [
                                'initiation_state' => 'cancelled',
                                'verified_by_fastlipa' => true,
                                'verified_payment_status' => $verifiedStatus,
                            ]
                        ),
                    ]);

                    if ($subscription->status === 'pending') {
                        $subscription->update([
                            'status' => 'cancelled',
                        ]);
                    }
                }

                /*
                 * Mark callback processed in the same transaction.
                 */
                $lockedCallback->update([
                    'payment_id' => $lockedPayment->id,
                    'signature_verified' => true,
                    'processing_status' => 'processed',
                    'processed_at' => now(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Callback processed successfully.',
            ]);
        } catch (Throwable $exception) {
            /*
             * Keep failed callbacks retryable.
             */
            $callback->update([
                'processing_status' => 'failed',
            ]);

            Log::error(
                'FastLipa webhook processing failed.',
                [
                    'reference' => $reference,
                    'tranid' => $tranId,
                    'error' => $exception->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Payment verification or processing failed.',
            ], 503);
        }
    }
}
