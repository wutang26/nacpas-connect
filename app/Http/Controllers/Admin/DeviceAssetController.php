<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceAsset;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DeviceAssetController extends Controller
{
    private const STATUSES = [
        'in_stock',
        'reserved',
        'sold',
        'installed',
        'maintenance',
        'damaged',
        'retired',
    ];

    private const CONDITIONS = [
        'new',
        'used',
        'refurbished',
        'damaged',
    ];

    public function index(Request $request): Response
    {
        $filters = $request->only([
            'q',
            'status',
            'condition',
            'product_id',
        ]);

        $assets = DeviceAsset::query()
            ->with([
                'product:id,sku,name,brand,model',
                'organization:id,name',
                'site:id,name',
            ])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim($request->string('q')->toString());

                $query->where(function ($subQuery) use ($term) {
                    $subQuery
                        ->where('asset_tag', 'like', "%{$term}%")
                        ->orWhere('serial_number', 'like', "%{$term}%")
                        ->orWhere('mac_address', 'like', "%{$term}%")
                        ->orWhereHas('product', function ($productQuery) use ($term) {
                            $productQuery
                                ->where('name', 'like', "%{$term}%")
                                ->orWhere('sku', 'like', "%{$term}%")
                                ->orWhere('brand', 'like', "%{$term}%")
                                ->orWhere('model', 'like', "%{$term}%");
                        });
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->when($request->filled('condition'), function ($query) use ($request) {
                $query->where('condition', $request->input('condition'));
            })
            ->when($request->filled('product_id'), function ($query) use ($request) {
                $query->where('product_id', $request->input('product_id'));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Hardware/Assets/Index', [
            'assets' => $assets,
            'filters' => $filters,
            'products' => Product::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'sku']),
            'statuses' => self::STATUSES,
            'conditions' => self::CONDITIONS,
            'statistics' => [
                'total' => DeviceAsset::count(),
                'in_stock' => DeviceAsset::where('status', 'in_stock')->count(),
                'sold' => DeviceAsset::where('status', 'sold')->count(),
                'installed' => DeviceAsset::where('status', 'installed')->count(),
                'maintenance' => DeviceAsset::where('status', 'maintenance')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Hardware/Assets/Create', [
            'products' => Product::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get([
                    'id',
                    'sku',
                    'name',
                    'brand',
                    'model',
                    'default_purchase_price',
                    'currency',
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where('is_active', true),
            ],
            'serial_number' => [
                'nullable',
                'string',
                'max:255',
                'unique:device_assets,serial_number',
            ],
            'mac_address' => [
                'nullable',
                'string',
                'max:50',
                'unique:device_assets,mac_address',
            ],
            'condition' => [
                'required',
                Rule::in(self::CONDITIONS),
            ],
            'purchase_date' => [
                'nullable',
                'date',
            ],
            'purchase_cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],
            'currency' => [
                'required',
                'string',
                'size:3',
            ],
            'warranty_expires_at' => [
                'nullable',
                'date',
                'after_or_equal:purchase_date',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $validated['serial_number'] = filled($validated['serial_number'] ?? null)
            ? trim($validated['serial_number'])
            : null;

        $validated['mac_address'] = filled($validated['mac_address'] ?? null)
            ? strtoupper(trim($validated['mac_address']))
            : null;

        $validated['currency'] = strtoupper($validated['currency']);

        $asset = DB::transaction(function () use ($validated) {
            $asset = DeviceAsset::create([
                ...$validated,
                'status' => 'in_stock',
                'organization_id' => null,
                'site_id' => null,
                'device_id' => null,
                'created_by' => Auth::id(),
            ]);

            // The database-generated primary key guarantees uniqueness.
            $asset->update([
                'asset_tag' => 'NACP-ASSET-' . str_pad(
                    (string) $asset->id,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),
            ]);

            return $asset;
        });

        return redirect()
            ->route('hardware.assets.index')
            ->with(
                'success',
                "Asset {$asset->asset_tag} registered successfully."
            );
    }
}
