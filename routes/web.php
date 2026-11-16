<?php

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
