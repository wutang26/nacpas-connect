<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Site;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Services\Vouchers\VoucherBatchService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VoucherBatchController extends Controller
{
    private function manageableSites(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            return Site::query()->with('organization');
        }

        $organizationIds = $user->ownedOrganizations()->pluck('id');

        $memberOrganizationIds = $user->organizations()
            ->wherePivot('status', 'active')
            ->wherePivotIn('role', ['owner', 'admin', 'manager'])
            ->pluck('organizations.id');

        $organizationIds = $organizationIds
            ->merge($memberOrganizationIds)
            ->unique()
            ->values();

        $siteIds = $user->sites()
            ->wherePivot('status', 'active')
            ->wherePivotIn('role', ['owner', 'manager'])
            ->pluck('sites.id');

        return Site::query()
            ->with('organization')
            ->where(function ($query) use ($organizationIds, $siteIds) {
                $query->whereIn('organization_id', $organizationIds)
                    ->orWhereIn('id', $siteIds);
            });
    }

    public function index(Request $request): Response
    {
        $sites = $this->manageableSites($request)
            ->orderBy('name')
            ->get(['id', 'organization_id', 'name', 'code']);

        $selectedSiteId = $request->integer('site_id')
            ?: $sites->first()?->id;

        abort_if(
            $selectedSiteId && !$sites->contains('id', $selectedSiteId),
            403,
            'Huna ruhusa ya kusimamia Site hii.'
        );

        $batches = VoucherBatch::query()
            ->with(['site:id,name', 'package:id,name'])
            ->withCount([
                'vouchers',
                'purchases',
                'redemptions',
            ])
            ->when($selectedSiteId, fn ($q) =>
                $q->where('site_id', $selectedSiteId)
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Vouchers/Batches/Index', [
            'sites' => $sites,
            'selectedSiteId' => $selectedSiteId,
            'batches' => $batches,
            'statistics' => [
                'batches' => VoucherBatch::query()
                    ->when($selectedSiteId, fn ($q) =>
                        $q->where('site_id', $selectedSiteId)
                    )->count(),
                'available' => Voucher::query()
                    ->where('status', 'available')
                    ->when($selectedSiteId, fn ($q) =>
                        $q->where('site_id', $selectedSiteId)
                    )->count(),
                'assigned' => Voucher::query()
                    ->where('status', 'assigned')
                    ->when($selectedSiteId, fn ($q) =>
                        $q->where('site_id', $selectedSiteId)
                    )->count(),
                'redeemed' => Voucher::query()
                    ->where('status', 'redeemed')
                    ->when($selectedSiteId, fn ($q) =>
                        $q->where('site_id', $selectedSiteId)
                    )->count(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $sites = $this->manageableSites($request)
            ->orderBy('name')
            ->get(['id', 'organization_id', 'name', 'code']);

        abort_if($sites->isEmpty(), 403, 'Huna Site unayoruhusiwa kusimamia.');

        $selectedSiteId = $request->integer('site_id')
            ?: $sites->first()->id;

        abort_if(
            !$sites->contains('id', $selectedSiteId),
            403,
            'Huna ruhusa ya kusimamia Site hii.'
        );

        $packages = Package::query()
            ->where('site_id', $selectedSiteId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'site_id',
                'name',
                'price',
                'currency',
                'duration_minutes',
            ]);

        return Inertia::render('Vouchers/Batches/Create', [
            'sites' => $sites,
            'selectedSiteId' => $selectedSiteId,
            'packages' => $packages,
        ]);
    }

    public function store(
        Request $request,
        VoucherBatchService $service
    ): RedirectResponse {
        $validated = $request->validate([
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:5000'],
            'wholesale_unit_price' => [
                'required', 'numeric', 'gt:0', 'max:9999999999.99',
            ],
            'retail_unit_price' => [
                'required', 'numeric', 'gt:0', 'max:9999999999.99',
            ],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $site = $this->manageableSites($request)
            ->whereKey($validated['site_id'])
            ->firstOrFail();

        $package = Package::query()
            ->whereKey($validated['package_id'])
            ->where('site_id', $site->id)
            ->firstOrFail();

        $result = $service->createBatch(
            $request->user(),
            $site->organization,
            $site,
            $package,
            $validated
        );

        // Codes are held in the creator's session and shown only once.
        $request->session()->put(
            'voucher_batch_codes_' . $result['batch']->id,
            $result['codes']
        );

        return redirect()
            ->route('vouchers.batches.show', $result['batch']->id)
            ->with('success', 'Voucher batch imetengenezwa kikamilifu.');
    }

    public function show(Request $request, VoucherBatch $batch): Response
    {
        $this->manageableSites($request)
            ->whereKey($batch->site_id)
            ->firstOrFail();

        $batch->load([
            'site:id,name',
            'organization:id,name',
            'package:id,name',
            'creator:id,name',
        ]);

        $vouchers = $batch->vouchers()
            ->select([
                'id',
                'voucher_batch_id',
                'code_suffix',
                'retail_price',
                'currency',
                'status',
                'voucher_agent_id',
                'voucher_agent_purchase_id',
                'assigned_at',
                'redeemed_at',
                'expires_at',
            ])
            ->with('agent.user:id,name')
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Vouchers/Batches/Show', [
            'batch' => $batch,
            'vouchers' => $vouchers,
            'generatedCodes' => $request->session()->pull(
                'voucher_batch_codes_' . $batch->id,
                []
            ),
            'statistics' => [
                'available' => $batch->vouchers()->where('status', 'available')->count(),
                'assigned' => $batch->vouchers()->where('status', 'assigned')->count(),
                'redeemed' => $batch->vouchers()->where('status', 'redeemed')->count(),
                'void' => $batch->vouchers()->where('status', 'void')->count(),
            ],
        ]);
    }

    public function close(Request $request, VoucherBatch $batch): RedirectResponse
    {
        $this->manageableSites($request)
            ->whereKey($batch->site_id)
            ->firstOrFail();

        abort_if($batch->status === 'closed', 422, 'Batch tayari imefungwa.');

        abort_if(
            $batch->purchases()->where('status', 'pending')->exists(),
            422,
            'Batch haiwezi kufungwa wakati kuna maombi ya agent yanayosubiri malipo.'
        );

        $batch->update(['status' => 'closed']);

        return back()->with('success', 'Voucher batch imefungwa.');
    }
}
