<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\OrderStatus;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        Paginator::defaultView('admin.partials.pagination');

        View::composer('admin.layouts.app', function ($view): void {
            $view->with([
                'shopName' => Setting::get('shop_name'),
                'pendingOrders' => Order::query()->where('status', OrderStatus::Pending)->count(),
                'lowStockCount' => Product::query()->active()->lowStock()->count(),
            ]);
        });
    }
}
