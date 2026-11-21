<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Global search behind the Ctrl+K palette: a few matches per kind of record.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q'));

        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
        $results = collect();

        Order::query()->with('customer')
            ->where(fn ($query) => $query->where('number', 'like', $like)
                ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', $like)->orWhere('phone', 'like', $like)))
            ->latest('placed_at')->limit(5)->get()
            ->each(fn (Order $order) => $results->push([
                'label' => $order->number.' · '.$order->customer->name,
                'hint' => 'Commande · '.Money::format($order->total).' · '.$order->status->label(),
                'url' => route('admin.orders.show', $order),
            ]));

        Product::query()
            ->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('sku', 'like', $like)->orWhere('variant', 'like', $like))
            ->orderBy('name')->limit(5)->get()
            ->each(fn (Product $product) => $results->push([
                'label' => $product->name.' — '.$product->variant,
                'hint' => 'Produit · '.$product->sku.' · stock '.$product->stock,
                'url' => route('admin.products.edit', $product),
            ]));

        Customer::query()
            ->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('phone', 'like', $like)->orWhere('email', 'like', $like))
            ->orderBy('name')->limit(5)->get()
            ->each(fn (Customer $customer) => $results->push([
                'label' => $customer->name,
                'hint' => 'Client · '.$customer->phone,
                'url' => route('admin.customers.show', $customer),
            ]));

        Coupon::query()->where(fn ($query) => $query->where('code', 'like', $like)->orWhere('label', 'like', $like))->limit(3)->get()
            ->each(fn (Coupon $coupon) => $results->push([
                'label' => $coupon->code,
                'hint' => 'Code promo · '.$coupon->benefit(),
                'url' => route('admin.coupons.index'),
            ]));

        Page::query()->where('title', 'like', $like)->limit(3)->get()
            ->each(fn (Page $page) => $results->push([
                'label' => $page->title,
                'hint' => $page->type->label(),
                'url' => route('admin.pages.edit', $page),
            ]));

        return response()->json(['results' => $results->values()]);
    }
}
