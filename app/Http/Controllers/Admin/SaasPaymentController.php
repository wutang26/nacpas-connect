<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaasPayment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaasPaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'provider' => (string) $request->query('provider', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
        ];

        $query = SaasPayment::query()
            ->with([
                'organizationSubscription.organization:id,name',
                'organizationSubscription.plan:id,name,code',
            ]);

        if ($filters['q'] !== '') {
            $search = $filters['q'];

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('internal_reference', 'like', "%{$search}%")
                    ->orWhere('provider_reference', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('provider', 'like', "%{$search}%")
                    ->orWhereHas(
                        'organizationSubscription.organization',
                        fn ($organization) => $organization->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                    )
                    ->orWhereHas(
                        'organizationSubscription.plan',
                        fn ($plan) => $plan->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                    );
            });
        }

        if (in_array($filters['status'], [
            'pending',
            'successful',
            'failed',
            'cancelled',
            'refunded',
        ], true)) {
            $query->where('status', $filters['status']);
        }

        if ($filters['provider'] !== '') {
            $query->where('provider', $filters['provider']);
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $payments = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $statistics = [
            'total' => SaasPayment::count(),

            'successful' => SaasPayment::where(
                'status',
                'successful'
            )->count(),

            'pending' => SaasPayment::where(
                'status',
                'pending'
            )->count(),

            'failed' => SaasPayment::whereIn(
                'status',
                ['failed', 'cancelled']
            )->count(),

            'refunded' => SaasPayment::where(
                'status',
                'refunded'
            )->count(),

            'revenue' => (float) SaasPayment::where(
                'status',
                'successful'
            )->sum('amount'),
        ];

        $providers = SaasPayment::query()
            ->whereNotNull('provider')
            ->where('provider', '!=', '')
            ->distinct()
            ->orderBy('provider')
            ->pluck('provider');

        return Inertia::render('SaasPayments/Index', [
            'payments' => $payments,
            'filters' => $filters,
            'statistics' => $statistics,
            'providers' => $providers,
        ]);
    }

    public function show(SaasPayment $saasPayment): Response
    {
        $saasPayment->load([
            'organizationSubscription.organization',
            'organizationSubscription.plan',
        ]);

        return Inertia::render('SaasPayments/Show', [
            'payment' => $saasPayment,
        ]);
    }
}
