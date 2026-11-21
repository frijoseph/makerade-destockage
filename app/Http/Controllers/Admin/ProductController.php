<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\StockMovementType;
use App\Support\Inventory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with(['category', 'brand'])
            ->when($request->query('q'), fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('variant', 'like', "%{$search}%")))
            ->when($request->query('category'), fn ($query, $category) => $query->where('category_id', $category))
            ->when($request->query('brand'), fn ($query, $brand) => $brand === 'none' ? $query->whereNull('brand_id') : $query->where('brand_id', $brand))
            ->when($request->query('promo') === '1', fn ($query) => $query->whereNotNull('old_price')->whereColumn('old_price', '>', 'price'))
            ->when($request->query('stock') === 'low', fn ($query) => $query->lowStock())
            ->when($request->query('status') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($request->query('status') === 'active', fn ($query) => $query->where('is_active', true))
            ->when(true, fn ($query) => match ($request->query('sort')) {
                'stock' => $query->orderBy('stock'),
                'price_asc' => $query->orderBy('price'),
                'price_desc' => $query->orderByDesc('price'),
                'recent' => $query->latest('id'),
                default => $query->orderBy('name'),
            })
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', [
            'stats' => [
                'active' => Product::query()->where('is_active', true)->count(),
                'hidden' => Product::query()->where('is_active', false)->count(),
                'low' => Product::query()->active()->lowStock()->where('stock', '>', 0)->count(),
                'out' => Product::query()->active()->where('stock', 0)->count(),
            ],
            'products' => $products,
            'categories' => Category::query()->orderBy('position')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', $this->formData(new Product([
            'is_active' => true,
            'kind' => Product::KINDS[0],
            'c1' => Setting::get('theme_color'),
            'c2' => '#ffffff',
            'low_stock_threshold' => (int) Setting::get('default_low_stock_threshold'),
            'stock' => 0,
            'sku' => $this->nextSku(),
        ])));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image', 'stock']);
        $initialStock = (int) $request->validated('stock');

        $product = DB::transaction(function () use ($request, $data, $initialStock): Product {
            $product = Product::query()->create($data + ['stock' => 0]);

            if ($request->hasFile('image')) {
                $product->update(['image' => $request->file('image')->store('products', 'public')]);
            }

            if ($initialStock > 0) {
                Inventory::move($product, StockMovementType::Restock, $initialStock, 'Stock initial', user: $request->user());
            }

            return $product;
        });

        return redirect()->route('admin.products.index')->with('status', "Produit « {$product->name} » créé.");
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', $this->formData($product));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image', 'stock']);

        if ($request->boolean('remove_image') || $request->hasFile('image')) {
            $this->deleteImage($product);
            $data['image'] = $request->hasFile('image') ? $request->file('image')->store('products', 'public') : null;
        }

        $product->update($data);

        return redirect()->route('admin.products.index')->with('status', "Produit « {$product->name} » mis à jour.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->orderItems()->exists()) {
            $product->update(['is_active' => false]);

            return back()->with('status', "« {$product->name} » a déjà été commandé : il a été désactivé au lieu d’être supprimé.");
        }

        $this->deleteImage($product);
        $product->delete();

        return back()->with('status', "Produit « {$product->name} » supprimé.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Product $product): array
    {
        return [
            'product' => $product,
            'categories' => Category::query()->orderBy('position')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
        ];
    }

    private function nextSku(): string
    {
        $prefix = mb_strtoupper((string) Setting::get('sku_prefix')).'-';
        $last = Product::query()->where('sku', 'like', $prefix.'%')->max('sku');
        $sequence = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    private function deleteImage(Product $product): void
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
    }
}
