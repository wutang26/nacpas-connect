<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SaasPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class OrganizationSubscriptionController extends Controller
{
    private const STATUSES = [
        'trialing',
        'active',
        'past_due',
        'cancelled',
        'expired',
    ];

    private const BILLING_CYCLES = [
        'monthly',
        'annual',
    ];

    private function ensureNoCurrentSubscription(
        int $organizationId,
        ?int $exceptId = null
    ): void {
        $exists = OrganizationSubscription::query()
            ->where('organization_id', $organizationId)
            ->whereIn('status', ['trialing', 'active', 'past_due'])
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'organization_id' =>
                    'This organization already has a current subscription. '
                    . 'Update or end that subscription before creating another one.',
            ]);
        }
    }

    private function subscriptionAmount(
        SaasPlan $plan,
        string $billingCycle
    ): float {
        return (float) (
            $billingCycle === 'annual'
                ? $plan->price_annual
                : $plan->price_monthly
        );
    }

    public function index(Request $request): Response
    {
        $filters = $request->only([
            'q',
            'status',
            'billing_cycle',
        ]);

        $subscriptions = OrganizationSubscription::query()
            ->with([
                'organization:id,name,code,status',
                'plan:id,name,code,currency',
            ])
            ->withCount('payments')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim($request->string('q')->toString());

                $query->where(function ($search) use ($term) {
                    $search->whereHas('organization', function ($org) use ($term) {
                        $org->where('name', 'like', "%{$term}%")
                            ->orWhere('code', 'like', "%{$term}%");
                    })->orWhereHas('plan', function ($plan) use ($term) {
                        $plan->where('name', 'like', "%{$term}%")
                            ->orWhere('code', 'like', "%{$term}%");
                    });
                });
            })
            ->when(
                in_array($request->input('status'), self::STATUSES, true),
                fn ($query) => $query->where('status', $request->input('status'))
            )
            ->when(
                in_array($request->input('billing_cycle'), self::BILLING_CYCLES, true),
                fn ($query) => $query->where('billing_cycle', $request->input('billing_cycle'))
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'filters' => $filters,
            'canManage' => $request->user()->can('platform.subscriptions.manage'),
            'statistics' => [
                'total' => OrganizationSubscription::count(),
                'trialing' => OrganizationSubscription::where('status', 'trialing')->count(),
                'active' => OrganizationSubscription::where('status', 'active')->count(),
                'attention' => OrganizationSubscription::whereIn('status', [
                    'past_due',
                    'expired',
                ])->count(),
                'cancelled' => OrganizationSubscription::where('status', 'cancelled')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Subscriptions/Create', [
            'organizations' => Organization::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'plans' => SaasPlan::query()
                ->where('is_active', true)
                ->orderBy('price_monthly')
                ->get([
                    'id',
                    'name',
                    'code',
                    'price_monthly',
                    'price_annual',
                    'currency',
                    'max_sites',
                    'max_users',
                    'max_devices',
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => [
                'required',
                'integer',
                Rule::exists('organizations', 'id')->where('status', 'active'),
            ],
            'saas_plan_id' => [
                'required',
                'integer',
                Rule::exists('saas_plans', 'id')->where('is_active', true),
            ],
            'billing_cycle' => ['required', Rule::in(self::BILLING_CYCLES)],
            'status' => ['required', Rule::in(self::STATUSES)],
            'current_period_start' => ['nullable', 'date'],
            'current_period_end' => [
                'nullable',
                'date',
                'after_or_equal:current_period_start',
            ],
            'trial_ends_at' => ['nullable', 'date'],
            'grace_ends_at' => ['nullable', 'date'],
            'cancel_at_period_end' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($validated) {
            $organization = Organization::query()
                ->lockForUpdate()
                ->findOrFail($validated['organization_id']);

            $this->ensureNoCurrentSubscription(
                $organization->id
            );

            $plan = SaasPlan::findOrFail($validated['saas_plan_id']);

            $status = $validated['status'];

            OrganizationSubscription::create([
                ...$validated,
                'amount' => $this->subscriptionAmount(
                    $plan,
                    $validated['billing_cycle']
                ),
                'currency' => $plan->currency,
                'cancelled_at' => $status === 'cancelled' ? now() : null,
            ]);
        });

        return redirect()
            ->route('subscriptions.index')
            ->with('success', 'SaaS subscription created successfully.');
    }

    public function show(OrganizationSubscription $subscription): Response
    {
        $subscription->load([
            'organization:id,name,code,status,business_email,business_phone,billing_email',
            'plan',
        ]);

        $payments = $subscription->payments()
            ->latest()
            ->paginate(10);

        return Inertia::render('Subscriptions/Show', [
            'subscription' => $subscription,
            'payments' => $payments,
            'canManage' => request()->user()
                ->can('platform.subscriptions.manage'),
        ]);
    }

    public function edit(OrganizationSubscription $subscription): Response
    {
        $subscription->load([
            'organization:id,name,code,status',
            'plan:id,name,code',
        ]);

        return Inertia::render('Subscriptions/Edit', [
            'subscription' => $subscription,
            'organizations' => Organization::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'status']),
            'plans' => SaasPlan::query()
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'code',
                    'price_monthly',
                    'price_annual',
                    'currency',
                    'is_active',
                ]),
        ]);
    }

    public function update(
        Request $request,
        OrganizationSubscription $subscription
    ): RedirectResponse {
        $validated = $request->validate([
            'organization_id' => [
                'required',
                'integer',
                'exists:organizations,id',
            ],
            'saas_plan_id' => [
                'required',
                'integer',
                'exists:saas_plans,id',
            ],
            'billing_cycle' => ['required', Rule::in(self::BILLING_CYCLES)],
            'status' => ['required', Rule::in(self::STATUSES)],
            'current_period_start' => ['nullable', 'date'],
            'current_period_end' => [
                'nullable',
                'date',
                'after_or_equal:current_period_start',
            ],
            'trial_ends_at' => ['nullable', 'date'],
            'grace_ends_at' => ['nullable', 'date'],
            'cancel_at_period_end' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $subscription) {
            Organization::query()
                ->lockForUpdate()
                ->findOrFail($validated['organization_id']);

            if (in_array($validated['status'], [
                'trialing',
                'active',
                'past_due',
            ], true)) {
                $this->ensureNoCurrentSubscription(
                    (int) $validated['organization_id'],
                    $subscription->id
                );
            }

            $plan = SaasPlan::findOrFail($validated['saas_plan_id']);

            $subscription->update([
                ...$validated,
                'amount' => $this->subscriptionAmount(
                    $plan,
                    $validated['billing_cycle']
                ),
                'currency' => $plan->currency,
                'cancelled_at' => $validated['status'] === 'cancelled'
                    ? ($subscription->cancelled_at ?? now())
                    : null,
            ]);
        });

        return redirect()
            ->route('subscriptions.show', $subscription)
            ->with('success', 'SaaS subscription updated successfully.');
    }
}
