<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ShopController::class, 'home'])->name('home');
Route::get('/shop', [ShopController::class, 'shop'])->name('shop');
Route::get('/best-deals', [ShopController::class, 'deals'])->name('deals');
Route::get('/new-arrivals', [ShopController::class, 'arrivals'])->name('arrivals');
Route::get('/brands', [ShopController::class, 'brands'])->name('brands');
Route::get('/blogs', [ShopController::class, 'blogs'])->name('blogs');
Route::get('/contact', [ShopController::class, 'contact'])->name('contact');
Route::get('/product/{slug}', [ShopController::class, 'product'])->name('product');
Route::get('/cart', [ShopController::class, 'cart'])->name('cart');
Route::get('/wishlist', [ShopController::class, 'wishlist'])->name('wishlist');
Route::get('/account', [ShopController::class, 'account'])->name('account');
Route::get('/track-order', [ShopController::class, 'track'])->name('track');
Route::get('/page/{slug}', [ShopController::class, 'info'])->name('info');

Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
Route::get('/commande/{order:number}', [CheckoutController::class, 'show'])->middleware('signed')->name('checkout.show');

/*
|--------------------------------------------------------------------------
| Back office
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [Admin\AuthController::class, 'create'])->name('login');
        Route::post('/login', [Admin\AuthController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout', [Admin\AuthController::class, 'destroy'])->name('logout');

        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/create', [Admin\OrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [Admin\OrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}/status', [Admin\OrderController::class, 'updateStatus'])->name('orders.status');
        Route::patch('/orders/{order}/payment', [Admin\OrderController::class, 'updatePayment'])->name('orders.payment');

        Route::resource('products', Admin\ProductController::class)->except('show');
        Route::resource('categories', Admin\CategoryController::class)->except('show');
        Route::resource('brands', Admin\BrandController::class)->except('show');

        Route::get('/customers', [Admin\CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [Admin\CustomerController::class, 'show'])->name('customers.show');

        Route::get('/stock', [Admin\StockController::class, 'index'])->name('stock.index');
        Route::post('/stock', [Admin\StockController::class, 'store'])->name('stock.store');

        Route::middleware('admin:admin')->group(function () {
            Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
            Route::put('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
        });
    });
});
