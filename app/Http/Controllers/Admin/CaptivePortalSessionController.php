<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Site;
use App\Models\WifiPortalContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CaptivePortalSessionController extends Controller
{
    /**
     * Organizations visible to the authenticated user.
     */
    private function visibleOrganizations(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            return Organization::query();
        }

        return Organization::query()
            ->where(function ($query) use ($user) {
                $query->where('owner_user_id', $user->id)
                    ->orWhereHas('users', function ($members) use ($user) {
                        $members->where('users.id', $user->id)
                            ->where('organization_user.status', 'active');
                    });
            });
    }

    /**
     * Sites visible to the authenticated user.
     */
    private function visibleSites(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            return Site::query();
        }

        $organizationIds = $this->visibleOrganizations($request)
            ->select('id');

        return Site::query()
            ->where(function ($query) use ($user, $organizationIds) {
                $query->whereIn('organization_id', $organizationIds)
                    ->orWhereHas('users', function ($members) use ($user) {
                        $members->where('users.id', $user->id)
                            ->where('site_user.status', 'active');
                    });
            });
    }

    /**
     * Display captive portal contexts.
     */
    public function index(Request $request): Response
    {
        $visibleSites = $this->visibleSites($request);

        $siteIds = (clone $visibleSites)->select('id');

        $baseQuery = WifiPortalContext::query()
            ->whereIn('site_id', $siteIds);

        $contexts = (clone $baseQuery)
            ->with([
                'site:id,name,organization_id',
                'site.organization:id,name',
            ])
            ->when($request->filled('site_id'), function ($query) use ($request, $siteIds) {
                $query->where('site_id', $request->integer('site_id'))
                    ->whereIn('site_id', $siteIds);
            })
            ->when($request->filled('context_status'), function ($query) use ($request) {
                $query->where('context_status', $request->input('context_status'));
            })
            ->when($request->filled('authorization_status'), function ($query) use ($request) {
                $query->where('authorization_status', $request->input('authorization_status'));
            })
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim($request->string('q')->toString());

                $query->where(function ($search) use ($term) {
                    $search->where('client_mac', 'like', "%{$term}%")
                        ->orWhere('client_ip', 'like', "%{$term}%")
                        ->orWhere('ap_mac', 'like', "%{$term}%")
                        ->orWhere('ssid_name', 'like', "%{$term}%");
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(function (WifiPortalContext $context) {
                return [
                    'id' => $context->id,
                    'site' => $context->site?->name,
                    'organization' => $context->site?->organization?->name,
                    'client_mac' => $context->client_mac,
                    'client_ip' => $context->client_ip,
                    'ap_mac' => $context->ap_mac,
                    'ssid_name' => $context->ssid_name,
                    'radio_id' => $context->radio_id,
                    'context_status' => $context->context_status,
                    'authorization_status' => $context->authorization_status,
                    'authorization_attempts' => $context->authorization_attempts,
                    'authorized_at' => $context->authorized_at?->toIso8601String(),
                    'expires_at' => $context->expires_at?->toIso8601String(),
                    'created_at' => $context->created_at?->toIso8601String(),
                    'last_error' => $context->last_error,
                ];
            });

        $statistics = [
            'total' => (clone $baseQuery)->count(),

            'captured' => (clone $baseQuery)
                ->where('context_status', 'captured')
                ->count(),

            'attached' => (clone $baseQuery)
                ->where('context_status', 'attached')
                ->count(),

            'consumed' => (clone $baseQuery)
                ->where('context_status', 'consumed')
                ->count(),

            'expired' => (clone $baseQuery)
                ->where('context_status', 'expired')
                ->count(),

            'authorized' => (clone $baseQuery)
                ->where('authorization_status', 'authorized')
                ->count(),

            'pending' => (clone $baseQuery)
                ->whereIn('authorization_status', [
                    'not_started',
                    'queued',
                    'authorizing',
                ])
                ->count(),

            'failed' => (clone $baseQuery)
                ->where('authorization_status', 'failed')
                ->count(),
        ];

        return Inertia::render('CaptivePortalSessions/Index', [
            'contexts' => $contexts,

            'statistics' => $statistics,

            'sites' => $visibleSites
                ->orderBy('name')
                ->get(['id', 'name']),

            'filters' => $request->only([
                'q',
                'site_id',
                'context_status',
                'authorization_status',
            ]),
        ]);
    }
}
