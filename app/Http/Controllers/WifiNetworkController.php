<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\WifiNetwork;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WifiNetworkController extends Controller
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

    /**
     * Hakikisha mtumiaji anaruhusiwa kusimamia Site.
     */
    private function authorizedSite(Request $request, int $siteId): Site
    {
        return $this->manageableSites($request)
            ->whereKey($siteId)
            ->firstOrFail();
    }

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

        $networks = $selectedSite
            ? WifiNetwork::query()
                ->where('site_id', $selectedSite->id)
                ->withCount('qrCodes')
                ->select([
                    'id',
                    'site_id',
                    'name',
                    'ssid',
                    'security_type',
                    'is_active',
                    'created_at',
                ])
                ->selectRaw(
                    'EXISTS (
                        SELECT 1 FROM wifi_networks AS wn
                        WHERE wn.id = wifi_networks.id
                        AND wn.password IS NOT NULL
                    ) AS password_configured'
                )
                ->orderByDesc('created_at')
                ->get()
            : collect();

        return Inertia::render('WifiNetworks/Index', [
            'sites' => $sites,
            'selectedSiteId' => $selectedSite?->id,
            'networks' => $networks,
        ]);
    }

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

        $selectedSiteId = $request->integer('site_id')
            ?: $sites->first()->id;

        abort_if(
            !$sites->contains('id', $selectedSiteId),
            403,
            'Huna ruhusa ya kusimamia Site hii.'
        );

        return Inertia::render('WifiNetworks/Create', [
            'sites' => $sites,
            'selectedSiteId' => $selectedSiteId,
        ]);
    }

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
            'ssid' => [
                'required',
                'string',
                'max:32',
            ],
            'security_type' => [
                'required',
                Rule::in(['open', 'wpa2']),
            ],
            'password' => [
                'nullable',
                'required_if:security_type,wpa2',
                'string',
                'min:8',
                'max:63',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
        ]);

        $site = $this->authorizedSite(
            $request,
            (int) $validated['site_id']
        );

        $request->validate([
            'ssid' => [
                Rule::unique('wifi_networks', 'ssid')
                    ->where('site_id', $site->id),
            ],
        ]);

        $site->wifiNetworks()->create([
            'name' => $validated['name'],
            'ssid' => $validated['ssid'],
            'security_type' => $validated['security_type'],
            'password' => $validated['security_type'] === 'wpa2'
                ? $validated['password']
                : null,
            'is_active' => $validated['is_active'],
        ]);

        return redirect()
            ->route('wifi-networks.index', [
                'site_id' => $site->id,
            ])
            ->with('success', 'WiFi Network imesajiliwa kikamilifu.');
    }

    public function edit(
        Request $request,
        WifiNetwork $wifiNetwork
    ): Response {
        $this->authorizedSite(
            $request,
            (int) $wifiNetwork->site_id
        );

        $sites = $this->manageableSites($request)
            ->orderBy('name')
            ->get(['id', 'organization_id', 'name', 'code']);

        return Inertia::render('WifiNetworks/Edit', [
            'network' => [
                'id' => $wifiNetwork->id,
                'site_id' => $wifiNetwork->site_id,
                'name' => $wifiNetwork->name,
                'ssid' => $wifiNetwork->ssid,
                'security_type' => $wifiNetwork->security_type,
                'is_active' => $wifiNetwork->is_active,
                'password_configured' => filled($wifiNetwork->password),
            ],
            'sites' => $sites,
        ]);
    }

    public function update(
        Request $request,
        WifiNetwork $wifiNetwork
    ): RedirectResponse {
        $this->authorizedSite(
            $request,
            (int) $wifiNetwork->site_id
        );

        $validated = $request->validate([
            'site_id' => [
                'required',
                'integer',
                Rule::in([$wifiNetwork->site_id]),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'ssid' => [
                'required',
                'string',
                'max:32',
                Rule::unique('wifi_networks', 'ssid')
                    ->where('site_id', $wifiNetwork->site_id)
                    ->ignore($wifiNetwork->id),
            ],
            'security_type' => [
                'required',
                Rule::in(['open', 'wpa2']),
            ],
            'password' => [
                'nullable',
                Rule::requiredIf(
                    fn () =>
                        $request->input('security_type') === 'wpa2'
                        && !filled($wifiNetwork->password)
                ),
                'string',
                'min:8',
                'max:63',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
        ]);

        $password = null;

        if ($validated['security_type'] === 'wpa2') {
            $password = filled($validated['password'] ?? null)
                ? $validated['password']
                : $wifiNetwork->password;
        }

        $wifiNetwork->update([
            'name' => $validated['name'],
            'ssid' => $validated['ssid'],
            'security_type' => $validated['security_type'],
            'password' => $password,
            'is_active' => $validated['is_active'],
        ]);

        return redirect()
            ->route('wifi-networks.index', [
                'site_id' => $wifiNetwork->site_id,
            ])
            ->with('success', 'WiFi Network imehaririwa kikamilifu.');
    }

    public function destroy(
        Request $request,
        WifiNetwork $wifiNetwork
    ): RedirectResponse {
        $this->authorizedSite(
            $request,
            (int) $wifiNetwork->site_id
        );

        if ($wifiNetwork->qrCodes()->exists()) {
            return back()->withErrors([
                'network' => 'WiFi Network hii ina QR Codes. Futa au badilisha QR Codes kwanza.',
            ]);
        }

        $siteId = $wifiNetwork->site_id;

        $wifiNetwork->delete();

        return redirect()
            ->route('wifi-networks.index', [
                'site_id' => $siteId,
            ])
            ->with('success', 'WiFi Network imefutwa kikamilifu.');
    }
}
