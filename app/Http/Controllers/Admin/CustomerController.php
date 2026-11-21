<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::query()
            ->withCount('orders')
            ->withSum(['orders as spent' => fn ($query) => $query->whereIn('status', OrderStatus::billable())], 'total')
            ->withMax('orders as last_order_at', 'placed_at')
            ->when($request->query('q'), fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($request->query('city'), fn ($query, $city) => $query->where('city', $city))
            ->when($request->query('segment'), fn ($query, $segment) => match ($segment) {
                'new' => $query->where('created_at', '>=', now()->subDays(30)),
                'repeat' => $query->has('orders', '>=', 2),
                'account' => $query->whereNotNull('user_id'),
                'guest' => $query->whereNull('user_id'),
                'inactive' => $query->whereDoesntHave('orders', fn ($orders) => $orders->where('placed_at', '>=', now()->subDays(90))),
                default => $query,
            })
            ->orderByDesc($request->query('sort') === 'spent' ? 'spent' : 'last_order_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.customers.index', [
            'stats' => [
                'total' => Customer::query()->count(),
                'recent' => Customer::query()->where('created_at', '>=', now()->subDays(30))->count(),
                'repeat' => Customer::query()->has('orders', '>=', 2)->count(),
                'accounts' => Customer::query()->whereNotNull('user_id')->count(),
            ],
            'customers' => $customers,
            'cities' => Customer::query()->whereNotNull('city')->distinct()->orderBy('city')->pluck('city'),
        ]);
    }

    public function show(Customer $customer): View
    {
        $customer->load(['orders' => fn ($query) => $query->withSum('items as units', 'quantity')->latest('placed_at')]);
        $billable = $customer->orders->filter(fn ($order): bool => in_array($order->status, OrderStatus::billable(), true));

        return view('admin.customers.show', [
            'customer' => $customer,
            'spent' => (float) $billable->sum('total'),
            'average' => $billable->count() ? (float) $billable->avg('total') : 0.0,
        ]);
    }
}
