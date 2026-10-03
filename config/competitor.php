<?php

use App\Services\Competitor\BrowserPageFetcher;
use App\Services\Competitor\G2aPriceProvider;
use App\Services\Competitor\HttpPageFetcher;

return [

    /*
    |--------------------------------------------------------------------------
    | Nightly run
    |--------------------------------------------------------------------------
    |
    | Read in the application timezone, which is the shop timezone -- see
    | config/app.php. "10 PM" therefore means 10 PM in Dhaka with nothing
    | here to say so.
    |
    */
    'schedule' => [
        'enabled' => (bool) env('COMPETITOR_CHECK_ENABLED', true),
        'time'    => env('COMPETITOR_CHECK_TIME', '22:00'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Price sources
    |--------------------------------------------------------------------------
    |
    | Keyed by the value stored in `competitor_listings.provider`. Adding a
    | second shop means writing one class and naming it here; nothing else in
    | the pipeline knows which site a price came from.
    |
    */
    'providers' => [
        'g2a' => G2aPriceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | How pages are fetched
    |--------------------------------------------------------------------------
    |
    | "http" is a plain request: fast, cheap, and fine for a shop that will
    | serve one. G2A will not. It fingerprints the TLS and HTTP/2 handshake of
    | whatever connects and answers 403 to anything that is not a browser --
    | no combination of headers gets past it, which is why there is a second
    | option at all rather than a longer header list.
    |
    | "browser" drives Chromium through Playwright, which is already a
    | dependency of this project for its browser tests.
    |
    */
    'fetcher' => env('COMPETITOR_FETCHER', 'browser'),

    'fetchers' => [
        'http'    => HttpPageFetcher::class,
        'browser' => BrowserPageFetcher::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | The browser
    |--------------------------------------------------------------------------
    |
    | Headless is off because headless is exactly what the bot protection
    | detects: a headless Chromium has its connection reset before the page
    | loads, while a headed one is served normally. On a desktop or a Windows
    | server with a session that is all there is to it; on a headless Linux
    | box the scheduled command needs a virtual display in front of it, as in
    | "xvfb-run php artisan competitor:check-prices".
    |
    */
    'browser' => [
        'node'     => env('COMPETITOR_BROWSER_NODE', 'node'),
        'script'   => 'tools/competitor-browser.mjs',
        'headless' => (bool) env('COMPETITOR_BROWSER_HEADLESS', false),
        'timeout'  => (int) env('COMPETITOR_BROWSER_TIMEOUT', 45),

        /*
         | Where Playwright keeps its browsers. It defaults to the home
         | directory of whoever is running, which on a server is the web user
         | and not the person who ran the install -- so the browser is
         | downloaded to one place and looked for in another. Point both at a
         | shared directory and that stops being a question.
         |
         | Passed explicitly to the browser process rather than left to be
         | inherited: a value in .env is loaded into PHP, which is not the
         | same as being in the environment a child process is handed.
         */
        'browsers_path' => env('PLAYWRIGHT_BROWSERS_PATH'),

        /*
         | A home directory the web user can write to. xvfb-run needs one for
         | its X authority file, and fails confusingly without it.
         */
        'home' => env('COMPETITOR_BROWSER_HOME'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fetching
    |--------------------------------------------------------------------------
    |
    | A storefront answers a browser, not a script, and will stall or refuse
    | one that announces itself as neither. Timeouts are deliberately short:
    | a page that has not answered in ten seconds is not going to, and the run
    | has a whole catalogue to get through.
    |
    | `delay_ms` is the pause between consecutive requests. It is politeness
    | and self-preservation in equal measure -- a burst of a hundred requests
    | from one address is what gets an address blocked.
    |
    */
    'http' => [
        'timeout'         => (int) env('COMPETITOR_HTTP_TIMEOUT', 10),
        'connect_timeout' => (int) env('COMPETITOR_HTTP_CONNECT_TIMEOUT', 5),
        'retry_delays'    => [500, 2000],
        'delay_ms'        => (int) env('COMPETITOR_HTTP_DELAY_MS', 1500),
        'user_agent'      => env(
            'COMPETITOR_HTTP_USER_AGENT',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        ),
        // Set when the shop blocks datacentre addresses. Anything Guzzle
        // accepts as a proxy string works here.
        'proxy'           => env('COMPETITOR_HTTP_PROXY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | How many days of readings to keep. History is what turns a single price
    | into a trend, but it is not worth keeping forever for a decision that is
    | made the next morning.
    |
    */
    'retention_days' => (int) env('COMPETITOR_RETENTION_DAYS', 120),

];
