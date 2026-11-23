<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    /**
     * Create a pending checkout.
     *
     * This is a test checkout only.
     * No real payment request is sent.
     */
    public function checkout(Request $request, Site $site)
    {
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
            [
                'phone' => [
                    'required',
                    'regex:/^\+255[67][0-9]{8}$/',
                ],
            ],
            [
                'phone.regex' => 'Tafadhali ingiza namba sahihi ya simu ya Tanzania.',
            ]
        );

        if ($phoneValidation->fails()) {
            throw ValidationException::withMessages([
                'phone' => $phoneValidation->errors()->first('phone'),
            ]);
        }

        $result = DB::transaction(function () use ($site, $validated, $phone) {

            // Lock and verify the selected package.
            $package = $site->packages()
                ->where('id', $validated['package_id'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$package) {
                throw ValidationException::withMessages([
                    'package_id' => 'Kifurushi hiki hakipatikani kwa sasa.',
                ]);
            }

            // Find an existing customer within this site.
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
                    'phone' => 'Namba hii ya simu imezuiwa kutumia huduma.',
                ]);
            }

            // Generate internal references.
            $subscriptionReference = 'NCP-SUB-' . Str::upper(Str::random(12));
            $paymentReference = 'NCP-PAY-' . Str::upper(Str::random(12));

            // Preserve the package details at purchase time.
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

                // Test record only. No gateway has been selected.
                'provider' => 'other',

                'provider_reference' => null,
                'internal_reference' => $paymentReference,
                'phone' => $phone,
                'amount' => $package->price,
                'currency' => $package->currency ?: 'TZS',
                'status' => 'pending',
                'paid_at' => null,

                'metadata' => json_encode([
                    'mode' => 'test',
                    'payment_initiated' => false,
                    'note' => 'Checkout created. No real payment request was sent.',
                ]),

                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return [
                'payment_id' => $paymentId,
                'payment_reference' => $paymentReference,
            ];
        });

        return redirect()->route('portal.payment.pending', [
            'site' => $site->slug,
            'reference' => $result['payment_reference'],
        ]);
    }

    /**
     * Display checkout status.
     */
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
                'payments.amount',
                'payments.currency',
                'payments.phone',
                'payments.status',
                'payments.created_at',
                'subscriptions.reference as subscription_reference',
                'subscriptions.package_name',
                'subscriptions.status as subscription_status',
            ])
            ->first();

        abort_unless($payment, 404);

        return Inertia::render('Portal/PaymentPending', [
            'site' => [
                'name' => $site->name,
                'slug' => $site->slug,
            ],

            'payment' => [
                'reference' => $payment->internal_reference,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'phone' => $payment->phone,
                'status' => $payment->status,
                'package_name' => $payment->package_name,
                'subscription_reference' => $payment->subscription_reference,
                'subscription_status' => $payment->subscription_status,
            ],
        ]);
    }
}
