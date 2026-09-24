<?php

use App\Http\Controllers\BkashController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\EmailUnsubscribeController;
use App\Http\Controllers\FavouriteController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderLookupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResellerController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

// SEO
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', function () {
    $lines = [
        'User-agent: *',
        'Disallow: /admin',
        'Disallow: /admin/',
        'Disallow: /dashboard',
        'Disallow: /profile',
        'Disallow: /checkout',
        'Disallow: /cart',
        'Disallow: /auth/',
        'Disallow: /favourites',
        'Disallow: /search',
        'Disallow: /bkash/',
        '',
        'Sitemap: ' . url('/sitemap.xml'),
    ];
    return response(implode("\n", $lines), 200)
        ->header('Content-Type', 'text/plain');
});

// Storefront
Route::get('/', [StorefrontController::class, 'home'])->name('home');

// Catalog. /category/ rather than a bare /{section} because that would collide
// with /faq, /about and every future static page, and rather than /shop
// because /shop is already bound to the legacy redirect handler below.
Route::get('/category/{sectionSlug}', [CatalogController::class, 'section'])->name('category');
Route::get('/brand/{mainCategorySlug}', [CatalogController::class, 'brand'])->name('brand');
Route::get('/product/{categorySlug}', [CatalogController::class, 'product'])->name('product');
Route::get('/cards/{slug}', [CatalogController::class, 'cardDetail'])->name('card.detail');

// Search
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/search/suggest', [SearchController::class, 'suggest'])
    ->middleware('throttle:60,1')
    ->name('search.suggest');
Route::get('/faq', [StorefrontController::class, 'faq'])->name('faq');
Route::get('/how-to-redeem', [StorefrontController::class, 'howToRedeem'])->name('how-to-redeem');
Route::get('/contact', [StorefrontController::class, 'contact'])->name('contact');
Route::post('/contact', [StorefrontController::class, 'contactSubmit'])
    ->middleware('throttle:5,1')
    ->name('contact.submit');

// Company & policy pages
Route::view('/about', 'storefront.pages.about')->name('about');
Route::view('/refund-policy', 'storefront.pages.refund-policy')->name('refund-policy');
Route::view('/privacy-policy', 'storefront.pages.privacy-policy')->name('privacy-policy');
Route::view('/terms', 'storefront.pages.terms')->name('terms');

// Reseller program (public — 404s while the program is switched off in Site Settings)
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/reseller', [ResellerController::class, 'show'])->name('reseller');
    Route::post('/reseller', [ResellerController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('reseller.submit');
});

// Legacy shop URLs (permanent, so search engines move rankings to the new pages)
Route::permanentRedirect('/shop', '/')->name('shop');
Route::get('/shop/{any}', [CatalogController::class, 'legacyShop'])->name('shop.category')->where('any', '.*');

// Cart & Checkout (guest accessible)
Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/cart', [CheckoutController::class, 'cart'])->name('cart');
    Route::post('/cart/add', [CheckoutController::class, 'addToCart'])->name('cart.add');
    Route::post('/cart/update-quantity', [CheckoutController::class, 'updateQuantity'])->name('cart.update-quantity');
    Route::post('/cart/update-selection', [CheckoutController::class, 'updateSelection'])->name('cart.update-selection');
    Route::delete('/cart/{cartKey}', [CheckoutController::class, 'removeFromCart'])->name('cart.remove');
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
        ->middleware('throttle:20,1')
        ->name('checkout.manual');
});

// bKash callback
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/bkash/callback', [BkashController::class, 'callback'])->name('bkash.callback');
});

// Order lookup (guest)
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/orders', [OrderLookupController::class, 'index'])->name('orders.lookup');
    Route::post('/orders', [OrderLookupController::class, 'lookup'])->name('orders.lookup.submit');
});

// Order detail (auth)
Route::middleware(['auth', 'throttle:30,1'])->group(function () {
    Route::get('/orders/{orderNumber}', [OrderLookupController::class, 'show'])->name('orders.show');
    Route::post('/orders/{orderNumber}/review', [ReviewController::class, 'store'])->name('reviews.store')->middleware('throttle:3,1');
});

// Favourites (auth)
Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::get('/favourites', [FavouriteController::class, 'index'])->name('favourites');
    Route::post('/favourites/{giftCardCategory}', [FavouriteController::class, 'toggle'])->name('favourites.toggle');
});

// Referral
Route::middleware(['auth', 'throttle:30,1'])->group(function () {
    Route::get('/referral', [ReferralController::class, 'dashboard'])->name('referral.dashboard');
    Route::post('/referral/apply', [ReferralController::class, 'applyCode'])->name('referral.apply')->middleware('throttle:20,1');
    Route::post('/referral/withdraw', [ReferralController::class, 'requestWithdrawal'])->name('referral.withdraw')->middleware('throttle:5,1');
});

// Unsubscribing from campaign e-mail. The link is signed, and the confirmation
// posts back to the same URL so link scanners cannot unsubscribe anyone.
Route::middleware(['signed', 'throttle:30,1'])->group(function () {
    Route::get('/email/unsubscribe', [EmailUnsubscribeController::class, 'show'])->name('email.unsubscribe');
    Route::post('/email/unsubscribe', [EmailUnsubscribeController::class, 'store'])->name('email.unsubscribe.confirm');
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
