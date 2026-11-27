<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\VoucherAgentPurchase;
use App\Services\Vouchers\VoucherAgentPurchaseService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VoucherAgentPurchaseController extends Controller
{
    private function manageableSites(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            return Site::query();
        }

        $organizationIds = $user->ownedOrganizations()->pluck('id')
            ->merge(
                $user->organizations()
                    ->wherePivot('status', 'active')
                    ->wherePivotIn('role', ['owner', 'admin', 'manager'])
                    ->pluck('organizations.id')
            )->unique();

        $siteIds = $user->sites()
            ->wherePivot('status', 'active')
            ->wherePivotIn('role', ['owner', 'manager'])
            ->pluck('sites.id');

        return Site::query()->where(function ($q) use ($organizationIds, $siteIds) {
            $q->whereIn('organization_id', $organizationIds)
                ->orWhereIn('id', $siteIds);
        });
    }

    public function index(Request $request): Response
    {
        $siteIds = $this->manageableSites($request)->select('id');

        $purchases = VoucherAgentPurchase::query()
            ->with([
                'agent.user:id,name,email',
                'batch:id,batch_number',
                'site:id,name',
                'confirmer:id,name',
            ])
            ->whereIn('site_id', $siteIds)
            ->when($request->filled('status'), fn ($q) =>
                $q->where('status', $request->input('status'))
            )
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim($request->string('q')->toString());
                $q->where(function ($sub) use ($term) {
                    $sub->where('reference', 'like', "%{$term}%")
                        ->orWhere('payment_reference', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Vouchers/Purchases/Index', [
            'purchases' => $purchases,
            'filters' => $request->only(['status', 'q']),
        ]);
    }

    public function confirm(
        Request $request,
        VoucherAgentPurchase $purchase,
        VoucherAgentPurchaseService $service
    ): RedirectResponse {
        $this->manageableSites($request)
            ->whereKey($purchase->site_id)
            ->firstOrFail();

        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'max:50'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
        ]);

        $service->confirmPayment(
            $purchase,
            $request->user(),
            $validated['payment_method'],
            $validated['payment_reference'] ?? null
        );

        return back()->with(
            'success',
            'Malipo yamethibitishwa na vouchers zimegawiwa kwa agent.'
        );
    }

    public function cancel(
        Request $request,
        VoucherAgentPurchase $purchase,
        VoucherAgentPurchaseService $service
    ): RedirectResponse {
        $this->manageableSites($request)
            ->whereKey($purchase->site_id)
            ->firstOrFail();

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->cancelPurchase($purchase, $validated['reason'] ?? null);

        return back()->with('success', 'Ombi la ununuzi limefutwa.');
    }
}
