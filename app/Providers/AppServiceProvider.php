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

        View::composer(['layouts.storefront', 'errors.404'], function ($view) {
            $view->with('footerBrands', app(StorefrontCatalog::class)->brands());
        });
    }
}
