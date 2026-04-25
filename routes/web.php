<?php

use App\Http\Controllers\BkashController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderLookupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

// SEO
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Storefront
Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/product/{categorySlug}', [StorefrontController::class, 'product'])->name('product');
Route::get('/cards/{slug}', [StorefrontController::class, 'cardDetail'])->name('card.detail');
Route::get('/faq', [StorefrontController::class, 'faq'])->name('faq');
Route::get('/contact', [StorefrontController::class, 'contact'])->name('contact');
Route::post('/contact', [StorefrontController::class, 'contactSubmit'])->name('contact.submit');

// Legacy shop redirect
Route::get('/shop', fn() => redirect()->route('home'))->name('shop');
Route::get('/shop/{any}', fn() => redirect()->route('home'))->name('shop.category')->where('any', '.*');

// Cart & Checkout (guest accessible)
Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/cart', [CheckoutController::class, 'cart'])->name('cart');
    Route::post('/cart/add', [CheckoutController::class, 'addToCart'])->name('cart.add');
    Route::delete('/cart/{giftCardId}', [CheckoutController::class, 'removeFromCart'])->name('cart.remove');
    Route::get('/checkout/success/{orderNumber}', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/pending/{orderNumber}', [CheckoutController::class, 'pending'])->name('checkout.pending');
    Route::get('/checkout/failed', [CheckoutController::class, 'failed'])->name('checkout.failed');
});

// Checkout (login required)
Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout/initiate', [CheckoutController::class, 'initiate'])
        ->middleware('throttle:30,1')
        ->name('checkout.initiate');
    Route::post('/checkout/manual', [CheckoutController::class, 'placeManualOrder'])
        ->middleware('throttle:10,1')
        ->name('checkout.manual');
});

// bKash callback
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/bkash/callback', [BkashController::class, 'callback'])->name('bkash.callback');
});

// Order lookup (guest)
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/orders', [OrderLookupController::class, 'index'])->name('orders.lookup');
    Route::post('/orders', [OrderLookupController::class, 'lookup'])->name('orders.lookup.submit');
});

// Order detail (auth)
Route::middleware(['auth', 'throttle:30,1'])->group(function () {
    Route::get('/orders/{orderNumber}', [OrderLookupController::class, 'show'])->name('orders.show');
});

// Google OAuth
Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Auth routes (Breeze)
Route::get('/dashboard', fn() => redirect()->route('home'))->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
