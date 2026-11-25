<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    private const CATEGORIES = [
        'Access Points',
        'Routers',
        'Switches',
        'Gateways',
        'Antennas',
        'Network Cables',
        'Power Supplies',
        'Mounting Accessories',
        'Network Accessories',
        'Other',
    ];

    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'category', 'status']);

        $products = Product::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim($request->string('q')->toString());

                $query->where(function ($subQuery) use ($term) {
                    $subQuery
                        ->where('sku', 'like', "%{$term}%")
                        ->orWhere('name', 'like', "%{$term}%")
                        ->orWhere('brand', 'like', "%{$term}%")
                        ->orWhere('model', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->where('category', $request->input('category'));
            })
            ->when($request->input('status') === 'active', function ($query) {
                $query->where('is_active', true);
            })
            ->when($request->input('status') === 'inactive', function ($query) {
                $query->where('is_active', false);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Hardware/Products/Index', [
            'products' => $products,
            'categories' => self::CATEGORIES,
            'filters' => $filters,
            'statistics' => [
                'total' => Product::count(),
                'active' => Product::where('is_active', true)->count(),
                'inactive' => Product::where('is_active', false)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Hardware/Products/Create', [
            'categories' => self::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:80', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(self::CATEGORIES)],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'specifications' => ['nullable', 'string'],
            'default_purchase_price' => ['nullable', 'numeric', 'min:0'],
            'default_selling_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'unit_of_measure' => ['required', 'string', 'max:30'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['sku'] = strtoupper(trim($validated['sku']));
        $validated['currency'] = strtoupper($validated['currency']);

        Product::create($validated);

        return redirect()
            ->route('hardware.products.index')
            ->with('success', 'Product registered successfully.');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Hardware/Products/Edit', [
            'product' => $product,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => [
                'required',
                'string',
                'max:80',
                Rule::unique('products', 'sku')->ignore($product->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(self::CATEGORIES)],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'specifications' => ['nullable', 'string'],
            'default_purchase_price' => ['nullable', 'numeric', 'min:0'],
            'default_selling_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'unit_of_measure' => ['required', 'string', 'max:30'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['sku'] = strtoupper(trim($validated['sku']));
        $validated['currency'] = strtoupper($validated['currency']);

        $product->update($validated);

        return redirect()
            ->route('hardware.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()
            ->route('hardware.products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
