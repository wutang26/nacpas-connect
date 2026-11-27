<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaasPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SaasPlanController extends Controller
{
    private const CURRENCIES = ['TZS', 'USD', 'KES', 'UGX'];

    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'status']);

        $plans = SaasPlan::query()
            ->withCount('organizationSubscriptions')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim($request->string('q')->toString());

                $query->where(function ($subQuery) use ($term) {
                    $subQuery->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->when($request->input('status') === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->input('status') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('SaasPlans/Index', [
            'plans' => $plans,
            'filters' => $filters,
            'canManage' => $request->user()->can('platform.saas_plans.manage'),
            'statistics' => [
                'total' => SaasPlan::count(),
                'active' => SaasPlan::where('is_active', true)->count(),
                'inactive' => SaasPlan::where('is_active', false)->count(),
                'subscribed' => SaasPlan::whereHas('organizationSubscriptions')->count(),
            ],
        ]);
    }


// Show FUnctionality
public function show(SaasPlan $saasPlan): Response
{
    $saasPlan->loadCount('organizationSubscriptions');

    $subscriptions = $saasPlan->organizationSubscriptions()
        ->with('organization:id,name,code,status')
        ->latest()
        ->paginate(10);

    return Inertia::render('SaasPlans/Show', [
        'plan' => $saasPlan,
        'subscriptions' => $subscriptions,
        'canManage' => request()->user()
            ->can('platform.saas_plans.manage'),
    ]);
}

    public function create(): Response
    {
        return Inertia::render('SaasPlans/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:saas_plans,code'],
            'description' => ['nullable', 'string'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_annual' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', Rule::in(self::CURRENCIES)],
            'max_sites' => ['required', 'integer', 'min:1'],
            'max_users' => ['required', 'integer', 'min:1'],
            'max_devices' => ['required', 'integer', 'min:1'],
            'features_text' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['features'] = $this->parseFeatures($validated['features_text'] ?? '');
        unset($validated['features_text']);

        SaasPlan::create($validated);

        return redirect()
            ->route('saas-plans.index')
            ->with('success', 'SaaS plan created successfully.');
    }

    public function edit(SaasPlan $saasPlan): Response
    {
        return Inertia::render('SaasPlans/Edit', [
            'plan' => [
                ...$saasPlan->toArray(),
                'features_text' => implode("\n", $saasPlan->features ?? []),
            ],
        ]);
    }

    public function update(Request $request, SaasPlan $saasPlan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('saas_plans', 'code')->ignore($saasPlan->id),
            ],
            'description' => ['nullable', 'string'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_annual' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', Rule::in(self::CURRENCIES)],
            'max_sites' => ['required', 'integer', 'min:1'],
            'max_users' => ['required', 'integer', 'min:1'],
            'max_devices' => ['required', 'integer', 'min:1'],
            'features_text' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['features'] = $this->parseFeatures($validated['features_text'] ?? '');
        unset($validated['features_text']);

        $saasPlan->update($validated);

        return redirect()
            ->route('saas-plans.index')
            ->with('success', 'SaaS plan updated successfully.');
    }

    private function parseFeatures(string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $text))
            ->map(fn ($feature) => trim($feature))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
