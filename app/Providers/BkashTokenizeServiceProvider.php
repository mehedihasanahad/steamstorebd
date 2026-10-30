<?php

namespace App\Providers;

use Karim007\LaravelBkashTokenize\BkashTokenizeServiceProvider as VendorProvider;

/**
 * The bKash package, minus the routes it ships with.
 *
 * The vendor provider calls loadRoutesFrom() unconditionally, which published
 * six endpoints on every deployment under the `web` group alone -- no auth and
 * no throttle:
 *
 *   GET /bkash/payment, /bkash/create-payment, /bkash/callback,
 *       /bkash/search/{trxID}, /bkash/refund, /bkash/refund/status
 *
 * They are sample code. `/bkash/search/{trxID}` returned a live transaction
 * lookup to anyone who asked -- the same proof the send-money checkout accepts
 * -- and `/bkash/create-payment` opened payment sessions against the merchant
 * account. The scaffold controller they pointed at has been deleted with them.
 *
 * Everything else the package provides is kept: the config merge and the
 * `tbpayment` / `tbrefund` bindings behind the facades BkashService uses, so
 * the store's own bKash flow is untouched and needs no change to come back.
 *
 * This provider is wired up by hand in bootstrap/providers.php, and the
 * package is listed under extra.laravel.dont-discover in composer.json so its
 * own provider never boots. Undoing either brings the six routes back --
 * tests/Feature/BkashRoutesTest.php fails if that happens.
 */
class BkashTokenizeServiceProvider extends VendorProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../vendor/karim007/laravel-bkash-tokenize/config/bkash.php' => config_path('bkash.php'),
        ], 'config');

        $this->loadViewsFrom(
            __DIR__ . '/../../vendor/karim007/laravel-bkash-tokenize/src/Views',
            'bkashT',
        );
    }
}
