<?php

/**
 * The bKash package ships six sample routes and registers them itself.
 *
 * They were live on every deployment under the `web` group alone -- no auth,
 * no throttle -- which handed anyone a transaction lookup against the real
 * merchant account and a way to open payment sessions on it. App\Providers\
 * BkashTokenizeServiceProvider replaces the vendor provider and leaves the
 * routes out.
 *
 * If the package is ever re-discovered, or that provider is dropped from
 * bootstrap/providers.php, these fail rather than the endpoints quietly
 * coming back.
 */

use Illuminate\Support\Facades\Route;

it('publishes none of the package sample routes', function (string $uri) {
    expect(collect(Route::getRoutes())->contains(fn ($route) => $route->uri() === $uri))
        ->toBeFalse("The bKash package route `{$uri}` is registered again.");
})->with([
    'bkash/payment',
    'bkash/create-payment',
    'bkash/search/{trxID}',
    'bkash/refund',
    'bkash/refund/status',
]);

it('deletes the published sample controller with them', function () {
    expect(file_exists(app_path('Http/Controllers/BkashTokenizePaymentController.php')))->toBeFalse();
});

it('keeps the store\'s own callback, which settles real payments', function () {
    expect(route('bkash.callback'))->toContain('/bkash/callback');
});

it('still binds the payment services the checkout uses', function () {
    // The provider swap must not cost us the package itself -- BkashService
    // reaches for these through the facades on every bKash order.
    expect(app()->bound('tbpayment'))->toBeTrue()
        ->and(app()->bound('tbrefund'))->toBeTrue()
        ->and(config('bkash'))->not->toBeNull();
});
