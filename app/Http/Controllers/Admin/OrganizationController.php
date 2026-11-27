<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class OrganizationController extends Controller
{
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
                            ->where('organization_user.status', 'active')
                            ->whereIn('organization_user.role', ['owner', 'admin', 'manager']);
                    });
            });
    }

    public function index(Request $request): Response
    {
        $organizations = $this->visibleOrganizations($request)
            ->with('owner:id,name,email')
            ->withCount(['sites', 'users', 'subscriptions'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim($request->string('q')->toString());

                $query->where(function ($search) use ($term) {
                    $search->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%")
                        ->orWhere('business_email', 'like', "%{$term}%")
                        ->orWhere('business_phone', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('status'), fn ($query) =>
                $query->where('status', $request->input('status'))
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $base = $this->visibleOrganizations($request);

        return Inertia::render('Organizations/Index', [
            'organizations' => $organizations,
            'filters' => $request->only(['q', 'status']),
            'statistics' => [
                'total' => (clone $base)->count(),
                'active' => (clone $base)->where('status', 'active')->count(),
                'inactive' => (clone $base)->where('status', 'inactive')->count(),
                'suspended' => (clone $base)->where('status', 'suspended')->count(),
            ],
            'canCreate' => $request->user()->can('platform.organizations.create'),
            'canUpdate' => $request->user()->can('platform.organizations.update'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Organizations/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'business_email' => ['nullable', 'email', 'max:255'],
            'business_phone' => ['nullable', 'string', 'max:30'],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'region' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'timezone' => ['required', 'timezone'],
        ]);

        $organization = DB::transaction(function () use ($validated, $request) {
            $baseSlug = Str::slug($validated['name']);
            $slug = $baseSlug;
            $counter = 1;

            while (Organization::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }

            $organization = Organization::create([
                ...$validated,
                'owner_user_id' => $request->user()->id,
                'slug' => $slug,
                'code' => 'ORG-' . strtoupper(Str::random(8)),
                'status' => 'active',
            ]);

            $organization->users()->syncWithoutDetaching([
                $request->user()->id => [
                    'role' => 'owner',
                    'status' => 'active',
                    'joined_at' => now(),
                ],
            ]);

            return $organization;
        });

        return redirect()
            ->route('organizations.show', $organization)
            ->with('success', 'Organization registered successfully.');
    }

    public function show(Request $request, Organization $organization): Response
    {
        abort_unless(
            $this->visibleOrganizations($request)
                ->whereKey($organization->id)
                ->exists(),
            404
        );

        $organization->load([
            'owner:id,name,email',
            'sites' => fn ($query) => $query->withCount([
                'packages',
                'devices',
                'qrCodes',
            ])->latest(),
            'latestSubscription',
        ])->loadCount(['users', 'sites', 'subscriptions']);

        return Inertia::render('Organizations/Show', [
            'organization' => $organization,
            'canUpdate' => $request->user()->can('platform.organizations.update')
                && $this->manageableOrganizations($request)
                    ->whereKey($organization->id)->exists(),
        ]);
    }

    public function edit(Request $request, Organization $organization): Response
    {
        abort_unless(
            $this->manageableOrganizations($request)
                ->whereKey($organization->id)->exists(),
            404
        );

        return Inertia::render('Organizations/Edit', [
            'organization' => $organization,
        ]);
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless(
            $this->manageableOrganizations($request)
                ->whereKey($organization->id)->exists(),
            404
        );

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'business_email' => ['nullable', 'email', 'max:255'],
            'business_phone' => ['nullable', 'string', 'max:30'],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'region' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'timezone' => ['required', 'timezone'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
        ]);

        $organization->update($validated);

        return redirect()
            ->route('organizations.show', $organization)
            ->with('success', 'Organization updated successfully.');
    }
}
