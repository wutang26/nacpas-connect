<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\User;
use App\Models\VoucherAgent;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VoucherAgentController extends Controller
{
    private function manageableSites(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            return Site::query()->with('organization');
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

        return Site::query()
            ->with('organization')
            ->where(function ($q) use ($organizationIds, $siteIds) {
                $q->whereIn('organization_id', $organizationIds)
                    ->orWhereIn('id', $siteIds);
            });
    }

    public function index(Request $request): Response
    {
        $sites = $this->manageableSites($request)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $selectedSiteId = $request->integer('site_id')
            ?: $sites->first()?->id;

        abort_if(
            $selectedSiteId && !$sites->contains('id', $selectedSiteId),
            403
        );

        $agents = VoucherAgent::query()
            ->with(['user:id,name,email', 'site:id,name'])
            ->withCount(['purchases', 'vouchers', 'redemptions'])
            ->when($selectedSiteId, fn ($q) =>
                $q->where('site_id', $selectedSiteId)
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Vouchers/Agents/Index', [
            'sites' => $sites,
            'selectedSiteId' => $selectedSiteId,
            'agents' => $agents,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'max:100'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $site = $this->manageableSites($request)
            ->whereKey($validated['site_id'])
            ->firstOrFail();

        abort_unless($site->status === 'active', 422, 'Site haipo active.');

        DB::transaction(function () use ($validated, $site) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => strtolower(trim($validated['email'])),
                'password' => $validated['password'],
            ]);

            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();

            // This role must exist after the voucher permissions migration.
            $user->assignRole('voucher_agent');

            do {
                $agentCode = 'NCP-AG-' . Str::upper(Str::random(10));
            } while (VoucherAgent::where('agent_code', $agentCode)->exists());

            VoucherAgent::create([
                'user_id' => $user->id,
                'site_id' => $site->id,
                'status' => 'active',
                'agent_code' => $agentCode,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return redirect()
            ->route('vouchers.agents.index', ['site_id' => $site->id])
            ->with('success', 'Voucher agent amesajiliwa. Mpe credentials zake kwa njia salama.');
    }

    public function update(Request $request, VoucherAgent $agent): RedirectResponse
    {
        $this->manageableSites($request)
            ->whereKey($agent->site_id)
            ->firstOrFail();

        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended'])],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $agent->update($validated);

        return back()->with('success', 'Taarifa za agent zimebadilishwa.');
    }
}
