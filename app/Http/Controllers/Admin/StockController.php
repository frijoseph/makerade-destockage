<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockAdjustmentRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\StockMovementType;
use App\Support\Inventory;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        $movements = StockMovement::query()
            ->with(['product', 'order', 'user'])
            ->when($request->query('type'), fn ($query, $type) => $query->where('type', $type))
            ->when($request->query('product'), fn ($query, $product) => $query->where('product_id', $product))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $products = Product::query()->orderBy('name')->get(['id', 'name', 'variant', 'sku', 'stock', 'low_stock_threshold']);

        return view('admin.stock.index', [
            'movements' => $movements,
            'products' => $products,
            'lowStock' => $products->filter(fn (Product $product): bool => $product->isLowStock())->sortBy('stock')->values(),
            'stockValue' => (float) Product::query()->selectRaw('COALESCE(SUM(stock * COALESCE(cost_price, price)), 0) as value')->value('value'),
            'units' => (int) $products->sum('stock'),
            'manualTypes' => StockAdjustmentRequest::MANUAL_TYPES,
            'selectedProduct' => $request->integer('product') ?: null,
        ]);
    }

    public function store(StockAdjustmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $product = Product::query()->findOrFail($data['product_id']);
        $type = StockMovementType::from($data['type']);
        $quantity = (int) $data['quantity'];

        try {
            $movement = match ($type) {
                StockMovementType::Adjustment => Inventory::setTo($product, $quantity, $data['reason'] ?? null, $request->user()),
                StockMovementType::Loss => Inventory::move($product, $type, -$quantity, $data['reason'] ?? null, user: $request->user()),
                default => Inventory::move($product, $type, $quantity, $data['reason'] ?? null, user: $request->user()),
            };
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['quantity' => $exception->getMessage()]);
        }

        $message = $movement
            ? "Stock de « {$product->name} » : {$product->stock} unité(s)."
            : "Aucun changement : « {$product->name} » a déjà {$product->stock} unité(s).";

        return back()->with('status', $message);
    }
}
