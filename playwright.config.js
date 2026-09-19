import { defineConfig, devices } from '@playwright/test';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.dirname(fileURLToPath(import.meta.url));

/**
 * The storefront runs against its own SQLite database on its own port, so the
 * browser suite never touches the developer's working database and can be run
 * at any time without a setup ritual.
 *
 * APP_URL has to match the served origin: asset() and route() build absolute
 * URLs from it, and a mismatch would have the page load its CSS from a port
 * nothing is listening on.
 */
const PORT = 8137;
const BASE_URL = `http://127.0.0.1:${PORT}`;

const appEnv = {
    APP_ENV: 'local',
    APP_DEBUG: 'true',
    APP_URL: BASE_URL,
    APP_CANONICAL_REDIRECT: 'false',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: path.join(root, 'storage', 'browser-tests.sqlite'),
    SESSION_DRIVER: 'file',
    CACHE_STORE: 'file',
    QUEUE_CONNECTION: 'sync',
    MAIL_MAILER: 'log',

    // Keep the developer's dev tooling out of the pages under test. Vite's
    // HMR client full-reloads the page whenever a watched file changes, and
    // Boost's browser-log watcher posts back to a server that answers one
    // request at a time; either one cancels a navigation mid-flight, which
    // reads as a flaky test rather than as the interference it is.
    STOREFRONT_IGNORE_VITE_HOT: 'true',
    BOOST_ENABLED: 'false',

    // PHP's built-in server handles one request at a time unless told
    // otherwise. Without this the parallel workers queue behind each other and
    // pages half-paint, which shows up as flaky screenshots rather than as the
    // throughput problem it actually is.
    // Honoured on POSIX, ignored on Windows — see the `workers` note above.
    PHP_CLI_SERVER_WORKERS: '10',
};

export default defineConfig({
    testDir: './tests/Browser',
    outputDir: './storage/browser-tests/results',
    fullyParallel: true,
    forbidOnly: !! process.env.CI,
    retries: 1,

    /*
     | One worker: `php artisan serve` is PHP's built-in server, which handles
     | a single request at a time on Windows (PHP_CLI_SERVER_WORKERS is a POSIX
     | fork feature). Parallel workers queue behind each other, pages
     | half-paint, and the result looks like flaky screenshots rather than the
     | throughput limit it is. Point the suite at a real web server and this
     | can go up.
     */
    workers: 1,

    /*
     | Generous timeouts for the same reason there is one worker: the dev
     | server answers one request at a time, so revealing a panel full of
     | lazily-loaded brand art queues a dozen image requests, and a click made
     | straight afterwards waits behind them. That is a property of
     | `artisan serve`, not of the page — behind a real web server these
     | interactions are immediate.
     */
    timeout: 60_000,
    expect: { timeout: 15_000 },

    reporter: [['list'], ['html', { outputFolder: './storage/browser-tests/report', open: 'never' }]],

    use: {
        baseURL: BASE_URL,
        navigationTimeout: 45_000,
        actionTimeout: 20_000,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'off',
    },

    projects: [
        {
            // Signs in once and saves the session the account pages reuse.
            name: 'setup',
            testMatch: /auth\.setup\.js/,
        },
        {
            name: 'desktop',
            use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } },

            // responsive.spec.js is about what happens below the lg breakpoint,
            // which a 1440px viewport never reaches.
            testIgnore: /responsive\.spec\.js/,
            dependencies: ['setup'],
        },
        {
            // A phone, because that is what most of this store's traffic is.
            name: 'mobile',
            use: { ...devices['Pixel 7'] },
            testMatch: /(responsive|smoke)\.spec\.js/,
            dependencies: ['setup'],
        },
    ],

    /*
     | The fixture is built as the first half of this command rather than in a
     | globalSetup: Playwright starts the web server BEFORE global setup runs,
     | so a setup that rebuilt the database would pull it out from under a
     | server already answering health checks.
     */
    webServer: {
        command: `node tests/Browser/prepare-database.js && php artisan serve --host=127.0.0.1 --port=${PORT}`,
        url: BASE_URL,
        reuseExistingServer: false,
        timeout: 120_000,
        stdout: 'ignore',
        stderr: 'pipe',
        env: appEnv,
    },
});

export { BASE_URL, PORT, appEnv };
