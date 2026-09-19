<?php

namespace App\Providers;

use App\Services\StorefrontCatalog;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Password::defaults(function () {
            return Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        // Canonical tags, og:url and sitemap entries are all built by the URL
        // generator. Pinning it to APP_URL stops www/http variants of a page
        // from each declaring themselves canonical.
        if ($this->app->isProduction()) {
            URL::forceRootUrl(config('app.url'));

            if (str_starts_with((string) config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }

        // The header menu and the footer brand list appear on every storefront
        // page. Both come from the cached catalog tree, so binding them here
        // costs one cache read per request rather than a query per page.
        View::composer(['layouts.storefront', 'errors.404'], function ($view) {
            $catalog = app(StorefrontCatalog::class);

            $view->with([
                'footerBrands' => $catalog->brands(),
                'catalogMenu'  => $catalog->menu(),
            ]);
        });
    }
}
