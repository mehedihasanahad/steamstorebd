<?php

namespace App\Providers;

use App\Services\StorefrontCatalog;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // See config/storefront.php: the browser suite must be served the
        // built assets, not a developer's live HMR session.
        if (config('storefront.ignore_vite_hot_file')) {
            Vite::useHotFile(storage_path('framework/vite-hot-disabled'));
        }

        // Paces bulk sending to whatever the mail provider will accept. The
        // queue middleware releases a limited job back instead of failing it,
        // so a campaign slows down rather than losing recipients.
        RateLimiter::for('email-campaign', fn () => Limit::perMinute(
            max(1, (int) config('mail.campaign.rate_per_minute', 60)),
        ));

        /*
         | A queue worker is a long-running process, and Laravel caches the
         | resolved mailer -- so every job after the first reuses one SMTP
         | connection. Orders arrive minutes apart, by which time the provider
         | has closed its side, and the next job gets
         | "451 4.4.2 Timeout waiting for data from client" on MAIL FROM.
         |
         | Symfony's own keepalive does not catch it: it pings with NOOP and
         | only reconnects if the NOOP throws, and this server answers the NOOP
         | quite happily before refusing the transaction that follows.
         |
         | So the mailer is dropped before every job and each one dials afresh.
         | The handshake costs a few hundred milliseconds against a customer
         | otherwise waiting a full retry backoff for the code they paid for.
         */
        Queue::before(fn () => Mail::forgetMailers());

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
