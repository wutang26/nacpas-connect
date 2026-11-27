<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class SiteController extends Controller
{
    private function manageableOrganizations(Request $request)
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
                            ->wherePivot('status', 'active')
                            ->wherePivotIn('role', ['owner', 'admin', 'manager']);
                    });
            });
    }

    private function visibleSites(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            return Site::query();
        }

        $organizationIds = $this->manageableOrganizations($request)->select('id');

        return Site::query()
            ->where(function ($query) use ($user, $organizationIds) {
                $query->whereIn('organization_id', $organizationIds)
                    ->orWhereHas('users', function ($members) use ($user) {
                        $members->where('users.id', $user->id)
                            ->wherePivot('status', 'active');
                    });
            });
    }

    private function manageableSites(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            return Site::query();
        }

        $organizationIds = $this->manageableOrganizations($request)->select('id');

        return Site::query()
            ->where(function ($query) use ($user, $organizationIds) {
                $query->whereIn('organization_id', $organizationIds)
                    ->orWhereHas('users', function ($members) use ($user) {
                        $members->where('users.id', $user->id)
                            ->wherePivot('status', 'active')
                            ->wherePivotIn('role', ['owner', 'manager']);
                    });
            });
    }

    public function index(Request $request): Response
    {
        $base = $this->visibleSites($request);

        $sites = (clone $base)
            ->with('organization:id,name,code')
            ->withCount(['packages', 'devices', 'users'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim($request->string('q')->toString());

                $query->where(function ($search) use ($term) {
                    $search->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%")
                        ->orWhere('region', 'like', "%{$term}%")
                        ->orWhereHas('organization', fn ($org) =>
                            $org->where('name', 'like', "%{$term}%")
                        );
                });
            })
            ->when($request->filled('organization_id'), fn ($query) =>
                $query->where('organization_id', $request->integer('organization_id'))
            )
            ->when($request->filled('status'), fn ($query) =>
                $query->where('status', $request->input('status'))
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Sites/Index', [
            'sites' => $sites,
            'filters' => $request->only(['q', 'status', 'organization_id']),
            'organizations' => $this->manageableOrganizations($request)
                ->orderBy('name')->get(['id', 'name']),
            'statistics' => [
                'total' => (clone $base)->count(),
                'active' => (clone $base)->where('status', 'active')->count(),
                'inactive' => (clone $base)->where('status', 'inactive')->count(),
                'suspended' => (clone $base)->where('status', 'suspended')->count(),
            ],
            'canCreate' => $request->user()->can('platform.sites.create')
                && $this->manageableOrganizations($request)->exists(),
            'canUpdate' => $request->user()->can('platform.sites.update'),
        ]);
    }

    public function create(Request $request): Response
    {
        $organizations = $this->manageableOrganizations($request)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        abort_if($organizations->isEmpty(), 403, 'Hakuna Organization active unayoweza kusimamia.');

        return Inertia::render('Sites/Create', [
            'organizations' => $organizations,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:1000'],
            'region' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'timezone' => ['required', 'timezone'],
        ]);

        $organization = $this->manageableOrganizations($request)
            ->whereKey($validated['organization_id'])
            ->where('status', 'active')
            ->firstOrFail();

        $site = DB::transaction(function () use ($validated, $organization, $request) {
            $baseSlug = Str::slug($validated['name']);
            $slug = $baseSlug;
            $counter = 1;

            while (Site::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }

            $site = $organization->sites()->create([
                ...$validated,
                'created_by' => $request->user()->id,
                'slug' => $slug,
                'code' => 'SITE-' . strtoupper(Str::random(8)),
                'status' => 'active',
            ]);

            $site->users()->syncWithoutDetaching([
                $request->user()->id => [
                    'role' => 'owner',
                    'status' => 'active',
                    'is_default' => true,
                ],
            ]);

            return $site;
        });

        return redirect()
            ->route('sites.show', $site)
            ->with('success', 'Site registered successfully.');
    }

    public function show(Request $request, Site $site): Response
    {
        abort_unless(
            $this->visibleSites($request)->whereKey($site->id)->exists(),
            404
        );

        $site->load([
            'organization:id,name,code',
            'creator:id,name,email',
            'packages' => fn ($query) => $query->latest(),
        ])->loadCount(['packages', 'devices', 'users', 'qrCodes']);

        return Inertia::render('Sites/Show', [
            'site' => $site,
            'canUpdate' => $request->user()->can('platform.sites.update')
                && $this->manageableSites($request)->whereKey($site->id)->exists(),
        ]);
    }

    public function edit(Request $request, Site $site): Response
    {
        abort_unless(
            $this->manageableSites($request)->whereKey($site->id)->exists(),
            404
        );

        return Inertia::render('Sites/Edit', [
            'site' => $site,
            'organizations' => $this->manageableOrganizations($request)
                ->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Site $site): RedirectResponse
    {
        abort_unless(
            $this->manageableSites($request)->whereKey($site->id)->exists(),
            404
        );

        $validated = $request->validate([
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:1000'],
            'region' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'timezone' => ['required', 'timezone'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
        ]);

        $organization = $this->manageableOrganizations($request)
            ->whereKey($validated['organization_id'])
            ->firstOrFail();

        $site->update([
            ...$validated,
            'organization_id' => $organization->id,
        ]);

        return redirect()
            ->route('sites.show', $site)
            ->with('success', 'Site updated successfully.');
    }
}
