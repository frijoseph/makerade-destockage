<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
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
            ->when($request->query('stock') === 'low', fn ($query) => $query->lowStock())
            ->when($request->query('status') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($request->query('status') === 'active', fn ($query) => $query->where('is_active', true))
            ->orderBy($request->query('sort') === 'stock' ? 'stock' : 'name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('position')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', $this->formData(new Product([
            'is_active' => true,
            'kind' => 'bottle',
            'c1' => '#1f7a34',
            'c2' => '#ffffff',
            'low_stock_threshold' => 10,
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
        $last = Product::query()->where('sku', 'like', 'MK-%')->max('sku');
        $sequence = $last ? (int) substr($last, 3) + 1 : 1;

        return 'MK-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    private function deleteImage(Product $product): void
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
    }
}
