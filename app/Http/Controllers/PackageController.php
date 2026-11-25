<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Site;
use App\Support\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PackageController extends Controller
{
    /**
     * Sites ambazo mtumiaji ana ruhusa ya kusimamia.
     */
    private function manageableSites(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            return Site::query()->with('organization');
        }

        $organizationIds = $user->ownedOrganizations()
            ->pluck('id');

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

    /**
     * Hakikisha Site inaruhusiwa kusimamiwa na mtumiaji.
     */
    private function authorizedSite(Request $request, int $siteId): Site
    {
        return $this->manageableSites($request)
            ->whereKey($siteId)
            ->firstOrFail();
    }

    /**
     * Pata default currency ya platform.
     */
    private function platformCurrency(): string
    {
        $currency = strtoupper(
            (string) PlatformSettings::get('currency', 'TZS')
        );

        return in_array($currency, ['TZS', 'USD', 'KES', 'UGX'], true)
            ? $currency
            : 'TZS';
    }

    /**
     * Display packages for an authorized site.
     */
    public function index(Request $request): Response
    {
        $sites = $this->manageableSites($request)
            ->orderBy('name')
            ->get(['id', 'organization_id', 'name', 'code']);

        $selectedSiteId = $request->integer('site_id');

        if (!$selectedSiteId) {
            $selectedSiteId = $sites->first()?->id;
        }

        $selectedSite = $selectedSiteId
            ? $sites->firstWhere('id', $selectedSiteId)
            : null;

        abort_if(
            $selectedSiteId && !$selectedSite,
            403,
            'Huna ruhusa ya kusimamia Site hii.'
        );

        $packages = $selectedSite
            ? Package::where('site_id', $selectedSite->id)
                ->withCount('subscriptions')
                ->orderByDesc('created_at')
                ->get()
            : collect();

        return Inertia::render('Packages/Index', [
            'sites' => $sites,
            'selectedSiteId' => $selectedSite?->id,
            'packages' => $packages,
            'platformCurrency' => $this->platformCurrency(),
        ]);
    }

    /**
     * Show package creation form.
     */
    public function create(Request $request): Response
    {
        $sites = $this->manageableSites($request)
            ->orderBy('name')
            ->get(['id', 'organization_id', 'name', 'code']);

        abort_if(
            $sites->isEmpty(),
            403,
            'Huna Site unayoruhusiwa kusimamia.'
        );

        $requestedSiteId = $request->integer('site_id');

        $selectedSite = $requestedSiteId
            ? $sites->firstWhere('id', $requestedSiteId)
            : $sites->first();

        abort_if(
            !$selectedSite,
            403,
            'Huna ruhusa ya kusimamia Site hii.'
        );

        return Inertia::render('Packages/Create', [
            'sites' => $sites,
            'selectedSiteId' => $selectedSite->id,
            'platformCurrency' => $this->platformCurrency(),
        ]);
    }

    /**
     * Store a new package.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_id' => [
                'required',
                'integer',
                'exists:sites,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'duration_minutes' => [
                'required',
                'integer',
                'min:1',
                'max:525600',
            ],

            'download_speed_kbps' => [
                'required',
                'integer',
                'min:1',
                'max:100000000',
            ],

            'upload_speed_kbps' => [
                'required',
                'integer',
                'min:1',
                'max:100000000',
            ],

            'data_limit_mb' => [
                'nullable',
                'numeric',
                'gt:0',
                'max:1000000000',
            ],

            'device_limit' => [
                'required',
                'integer',
                'min:1',
                'max:1000',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ]);

        // Verify that the user can manage the selected site.
        $site = $this->authorizedSite(
            $request,
            (int) $validated['site_id']
        );

        // Convert MB to bytes before saving.
        $dataLimitBytes = isset($validated['data_limit_mb'])
            && $validated['data_limit_mb'] !== ''
                ? (int) round(
                    (float) $validated['data_limit_mb'] * 1024 * 1024
                )
                : null;

        $site->packages()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],

            // New packages use the current platform default currency.
            'currency' => $this->platformCurrency(),

            'duration_minutes' => $validated['duration_minutes'],
            'download_speed_kbps' => $validated['download_speed_kbps'],
            'upload_speed_kbps' => $validated['upload_speed_kbps'],
            'data_limit_bytes' => $dataLimitBytes,
            'device_limit' => $validated['device_limit'],
            'is_active' => $validated['is_active'],
        ]);

        return redirect()
            ->route('packages.index', [
                'site_id' => $site->id,
            ])
            ->with('success', 'Kifurushi kimesajiliwa kikamilifu.');
    }

    /**
     * Show package editing form.
     */
    public function edit(Request $request, Package $package): Response
    {
        // Verify access to the package's site.
        $this->authorizedSite(
            $request,
            (int) $package->site_id
        );

        $sites = $this->manageableSites($request)
            ->orderBy('name')
            ->get(['id', 'organization_id', 'name', 'code']);

        return Inertia::render('Packages/Edit', [
            'package' => [
                ...$package->toArray(),

                'data_limit_mb' => $package->data_limit_bytes !== null
                    ? round(
                        $package->data_limit_bytes / 1024 / 1024,
                        2
                    )
                    : null,
            ],

            'sites' => $sites,

            // Existing package currency is preserved.
            'platformCurrency' => $this->platformCurrency(),
        ]);
    }

    /**
     * Update an existing package.
     */
    public function update(
        Request $request,
        Package $package
    ): RedirectResponse {
        // Verify access to the package's current site.
        $this->authorizedSite(
            $request,
            (int) $package->site_id
        );

        $validated = $request->validate([
            'site_id' => [
                'required',
                'integer',
                Rule::in([$package->site_id]),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'duration_minutes' => [
                'required',
                'integer',
                'min:1',
                'max:525600',
            ],

            'download_speed_kbps' => [
                'required',
                'integer',
                'min:1',
                'max:100000000',
            ],

            'upload_speed_kbps' => [
                'required',
                'integer',
                'min:1',
                'max:100000000',
            ],

            'data_limit_mb' => [
                'nullable',
                'numeric',
                'gt:0',
                'max:1000000000',
            ],

            'device_limit' => [
                'required',
                'integer',
                'min:1',
                'max:1000',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ]);

        $dataLimitBytes = isset($validated['data_limit_mb'])
            && $validated['data_limit_mb'] !== ''
                ? (int) round(
                    (float) $validated['data_limit_mb'] * 1024 * 1024
                )
                : null;

        $package->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'duration_minutes' => $validated['duration_minutes'],
            'download_speed_kbps' => $validated['download_speed_kbps'],
            'upload_speed_kbps' => $validated['upload_speed_kbps'],
            'data_limit_bytes' => $dataLimitBytes,
            'device_limit' => $validated['device_limit'],
            'is_active' => $validated['is_active'],

            // Do not overwrite the existing package currency.
        ]);

        return redirect()
            ->route('packages.index', [
                'site_id' => $package->site_id,
            ])
            ->with('success', 'Kifurushi kimehaririwa kikamilifu.');
    }

    /**
     * Delete a package without subscription history.
     */
    public function destroy(
        Request $request,
        Package $package
    ): RedirectResponse {
        $this->authorizedSite(
            $request,
            (int) $package->site_id
        );

        if ($package->subscriptions()->exists()) {
            return back()->withErrors([
                'package' => 'Kifurushi hiki kina historia ya subscriptions. Huwezi kukifuta. Unaweza kukizima badala yake.',
            ]);
        }

        $siteId = $package->site_id;

        $package->delete();

        return redirect()
            ->route('packages.index', [
                'site_id' => $siteId,
            ])
            ->with('success', 'Kifurushi kimefutwa kikamilifu.');
    }
}
