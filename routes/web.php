<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\Auth;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ShopController::class, 'home'])->name('home');
Route::get('/shop', [ShopController::class, 'shop'])->name('shop');
Route::get('/best-deals', [ShopController::class, 'deals'])->name('deals');
Route::get('/new-arrivals', [ShopController::class, 'arrivals'])->name('arrivals');
Route::get('/brands', [ShopController::class, 'brands'])->name('brands');
Route::get('/blogs', [ShopController::class, 'blogs'])->name('blogs');
Route::get('/blog/{slug}', [ShopController::class, 'article'])->name('blog.show');
Route::get('/contact', [ShopController::class, 'contact'])->name('contact');
Route::post('/contact', [ContactController::class, 'message'])->middleware('throttle:5,1')->name('contact.store');
Route::post('/newsletter', [ContactController::class, 'subscribe'])->middleware('throttle:5,1')->name('newsletter.store');
Route::post('/track-order', [ContactController::class, 'track'])->middleware('throttle:10,1')->name('track.lookup');
Route::get('/product/{slug}', [ShopController::class, 'product'])->name('product');
Route::get('/cart', [ShopController::class, 'cart'])->name('cart');
Route::get('/wishlist', [ShopController::class, 'wishlist'])->name('wishlist');
Route::get('/account', [AccountController::class, 'show'])->name('account');

Route::prefix('account')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::post('/login', [Auth\CustomerAuthController::class, 'login'])->middleware('throttle:8,1')->name('account.login');
        Route::post('/register', [Auth\CustomerAuthController::class, 'register'])->middleware('throttle:6,1')->name('account.register');
        Route::get('/forgot-password', [Auth\PasswordResetController::class, 'request'])->name('password.request');
        Route::post('/forgot-password', [Auth\PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
        Route::get('/reset-password/{token}', [Auth\PasswordResetController::class, 'reset'])->name('password.reset');
        Route::post('/reset-password', [Auth\PasswordResetController::class, 'update'])->middleware('throttle:6,1')->name('password.update');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [Auth\CustomerAuthController::class, 'logout'])->name('account.logout');
        Route::put('/profile', [AccountController::class, 'profile'])->name('account.profile');
        Route::put('/password', [AccountController::class, 'password'])->name('account.password');
        Route::post('/claim-order', [AccountController::class, 'claim'])->name('account.claim');
    });
});

Route::post('/promo/coupon', [PromoController::class, 'coupon'])->middleware('throttle:40,1')->name('promo.coupon');
Route::post('/popups/{popup}/event', [PromoController::class, 'event'])->middleware('throttle:60,1')->name('popups.event');
Route::post('/wheel/spin', [PromoController::class, 'spin'])->middleware('throttle:10,1')->name('wheel.spin');
Route::get('/track-order', [ShopController::class, 'track'])->name('track');
Route::get('/page/{slug}', [ShopController::class, 'info'])->name('info');

Route::post('/assistant/message', [AssistantController::class, 'message'])->middleware('throttle:assistant')->name('assistant.message');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
Route::get('/commande/{order:number}', [CheckoutController::class, 'show'])->middleware('signed')->name('checkout.show');
Route::get('/commande/{order:number}/recu', [CheckoutController::class, 'receipt'])->middleware('signed')->name('checkout.receipt');

/*
|--------------------------------------------------------------------------
| Back office
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [Admin\AuthController::class, 'create'])->name('login');
    Route::post('/login', [Admin\AuthController::class, 'store'])->middleware('throttle:6,1')->name('login.store');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout', [Admin\AuthController::class, 'destroy'])->name('logout');

        Route::get('/', Admin\DashboardController::class)->name('dashboard');
        Route::get('/search', Admin\SearchController::class)->name('search');

        Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/create', [Admin\OrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [Admin\OrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::get('/orders/{order}/preview', [Admin\OrderController::class, 'preview'])->name('orders.preview');
        Route::get('/orders/{order}/print/{document}', [Admin\OrderController::class, 'print'])->whereIn('document', ['delivery', 'receipt', 'ticket'])->name('orders.print');
        Route::patch('/orders/{order}/status', [Admin\OrderController::class, 'updateStatus'])->name('orders.status');
        Route::patch('/orders/{order}/payment', [Admin\OrderController::class, 'updatePayment'])->name('orders.payment');

        Route::get('/products/export', [Admin\ProductImportController::class, 'export'])->name('products.export');
        Route::get('/products/template', [Admin\ProductImportController::class, 'template'])->name('products.template');
        Route::get('/products/import', [Admin\ProductImportController::class, 'create'])->name('products.import');
        Route::post('/products/import', [Admin\ProductImportController::class, 'preview'])->middleware('throttle:20,1')->name('products.import.preview');
        Route::post('/products/import/apply', [Admin\ProductImportController::class, 'store'])->middleware('throttle:10,1')->name('products.import.store');
        Route::resource('products', Admin\ProductController::class)->except('show');
        Route::resource('categories', Admin\CategoryController::class)->except('show');
        Route::resource('brands', Admin\BrandController::class)->except('show');

        Route::get('/customers', [Admin\CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [Admin\CustomerController::class, 'show'])->name('customers.show');

        Route::get('/stock', [Admin\StockController::class, 'index'])->name('stock.index');
        Route::post('/stock', [Admin\StockController::class, 'store'])->name('stock.store');

        Route::resource('pages', Admin\PageController::class)->except('show');
        Route::resource('popups', Admin\PopupController::class)->except('show');
        Route::get('/coupons', [Admin\CouponController::class, 'index'])->name('coupons.index');
        Route::post('/coupons', [Admin\CouponController::class, 'store'])->name('coupons.store');
        Route::put('/coupons/{coupon}', [Admin\CouponController::class, 'update'])->name('coupons.update');
        Route::delete('/coupons/{coupon}', [Admin\CouponController::class, 'destroy'])->name('coupons.destroy');

        Route::get('/assistant', [Admin\AssistantController::class, 'index'])->name('assistant.index');
        Route::post('/assistant/test', [Admin\AssistantController::class, 'test'])->middleware('throttle:20,1')->name('assistant.test');
        Route::post('/assistant/purge', [Admin\AssistantController::class, 'purge'])->name('assistant.purge');
        Route::get('/assistant/{conversation}', [Admin\AssistantController::class, 'show'])->name('assistant.show');
        Route::delete('/assistant/{conversation}', [Admin\AssistantController::class, 'destroy'])->name('assistant.destroy');

        Route::get('/messages', [Admin\MessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/{message}', [Admin\MessageController::class, 'show'])->name('messages.show');
        Route::delete('/messages/{message}', [Admin\MessageController::class, 'destroy'])->name('messages.destroy');
        Route::get('/subscribers', [Admin\MessageController::class, 'subscribers'])->name('subscribers.index');
        Route::get('/subscribers/export', [Admin\MessageController::class, 'export'])->name('subscribers.export');

        Route::middleware('admin:admin')->group(function () {
            Route::post('/zones', [Admin\DeliveryZoneController::class, 'store'])->name('zones.store');
            Route::put('/zones/{zone}', [Admin\DeliveryZoneController::class, 'update'])->name('zones.update');
            Route::delete('/zones/{zone}', [Admin\DeliveryZoneController::class, 'destroy'])->name('zones.destroy');

            Route::get('/settings/{group?}', [Admin\SettingController::class, 'edit'])->name('settings.edit');
            Route::put('/settings/{group}', [Admin\SettingController::class, 'update'])->name('settings.update');
        });
    });
});
