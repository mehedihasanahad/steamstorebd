import { expect } from '@playwright/test';

/**
 * Every public page, with what proves it rendered the right one.
 *
 * `heading` is matched against the page's own h1, so a route that silently
 * fell through to another page fails here rather than passing on a 200.
 */
export const PUBLIC_PAGES = [
    // With a campaign loaded the homepage h1 is visually hidden behind the
    // slider; without one it is the visible hero headline. Either is valid.
    { path: '/', name: 'home', heading: /Gift cards, (game top-ups|top-ups and keys)/i },
    { path: '/category/gift-cards', name: 'section', heading: 'Gift Cards' },
    { path: '/category/game-top-up', name: 'section (top-up)', heading: 'Games Top Up' },
    { path: '/brand/steam', name: 'brand', heading: 'Steam' },
    { path: '/brand/pubg', name: 'brand (top-up)', heading: 'PUBG' },
    { path: '/product/steam-wallet-hkd', name: 'product', heading: 'Steam Wallet Code HKD' },
    { path: '/product/steam-wallet-usa', name: 'product (sibling)', heading: 'Steam Wallet Code USA' },
    { path: '/product/pubg-uc', name: 'product (buyer input)', heading: 'Pubg Mobile UC' },
    { path: '/search?q=steam', name: 'search results', heading: /Results for/i },
    { path: '/search', name: 'search empty', heading: /Search the catalog/i },
    { path: '/cart', name: 'cart (empty)', heading: 'Shopping cart' },
    { path: '/faq', name: 'faq', heading: 'Frequently asked questions' },
    { path: '/how-to-redeem', name: 'how to redeem', heading: 'How to redeem your code' },
    { path: '/contact', name: 'contact', heading: 'Get in touch' },
    { path: '/reseller', name: 'reseller', heading: /./ },
    { path: '/orders', name: 'order lookup', heading: 'Track your order' },
    { path: '/about', name: 'about', heading: 'About Steam Store BD' },
    { path: '/refund-policy', name: 'refund policy', heading: /Refund/i },
    { path: '/privacy-policy', name: 'privacy policy', heading: /Privacy/i },
    { path: '/terms', name: 'terms', heading: /Terms/i },
    { path: '/login', name: 'login', heading: 'Welcome back' },
    { path: '/register', name: 'register', heading: 'Create account' },
    { path: '/forgot-password', name: 'forgot password', heading: /Forgot password/i },
];

/**
 * Collect page errors and console errors for the life of a test.
 *
 * Returns an array that fills as the page runs; assert on it at the end of the
 * test rather than at navigation time, so an error thrown by a late-booting
 * Alpine component is still caught.
 */
export function watchForErrors(page) {
    const errors = [];

    page.on('pageerror', (error) => errors.push(`pageerror: ${error.message}`));
    page.on('console', (message) => {
        if (message.type() !== 'error') return;

        const text = message.text();

        // Favicon and image 404s come from fixture data with no uploaded
        // artwork, and third-party analytics aborts on every navigation.
        // Neither says anything about whether the page works.
        if (/favicon|Failed to load resource.*(png|jpg|jpeg|svg|webp|ico)/i.test(text)) return;
        if (/google-analytics|googletagmanager|gtag/i.test(text)) return;

        errors.push(`console: ${text}`);
    });

    return errors;
}

/** A page must never scroll sideways — not at any width, and least of all on a phone. */
export async function expectNoHorizontalOverflow(page) {
    const overflow = await page.evaluate(() => ({
        scrollWidth: document.documentElement.scrollWidth,
        clientWidth: document.documentElement.clientWidth,
        culprits: [...document.querySelectorAll('body *')]
            .filter((el) => el.getBoundingClientRect().right > document.documentElement.clientWidth + 1)
            .slice(0, 5)
            .map((el) => `${el.tagName.toLowerCase()}.${String(el.className).slice(0, 60)}`),
    }));

    expect(
        overflow.scrollWidth,
        `page scrolls sideways by ${overflow.scrollWidth - overflow.clientWidth}px. First offenders: ${overflow.culprits.join(' | ')}`,
    ).toBeLessThanOrEqual(overflow.clientWidth + 1);
}

/**
 * Navigate, retrying if the browser aborts it.
 *
 * A goto issued while a form POST's redirect is still settling is cancelled by
 * Chromium with ERR_ABORTED. That is a race in the test, not a fault in the
 * page, so the right response is to ask again rather than to fail.
 */
export async function gotoStable(page, path, attempts = 3) {
    for (let attempt = 1; ; attempt++) {
        try {
            await page.goto(path, { waitUntil: 'domcontentloaded' });

            return;
        } catch (error) {
            if (attempt >= attempts || ! String(error).includes('ERR_ABORTED')) throw error;

            await page.waitForTimeout(250);
        }
    }
}

/** Wait until Alpine has booted, so an interaction is not sent to inert markup. */
export async function waitForAlpine(page) {
    await page.waitForFunction(() => window.Alpine !== undefined, null, { timeout: 15_000 });
    await page.waitForTimeout(150);
}

/** The section trigger in the header nav, not the same-named link inside the panel. */
export function sectionTrigger(page, slug) {
    return page.locator(`[x-ref="trigger-${slug}"]`);
}

/** The desktop header search input. The mobile one shares its markup but not its id. */
export function headerSearch(page) {
    return page.locator('#header-search-input');
}

/**
 * Wait until the page has stopped asking the server for things.
 *
 * `php artisan serve` handles one request at a time (PHP_CLI_SERVER_WORKERS is
 * POSIX-only, so there is no worker pool on Windows). A click or keypress that
 * navigates therefore queues behind whatever the page still has in flight —
 * typically a dozen lazily-loaded brand tiles — and can sit there past any
 * reasonable timeout without the navigation ever committing.
 *
 * Playwright discourages `networkidle` for good reasons in normal apps; here it
 * is precisely the condition we need, so we wait for it deliberately rather
 * than by padding timeouts and hoping.
 */
export async function settleNetwork(page) {
    await page.waitForLoadState('networkidle').catch(() => {
        // An idle window never opening is not itself a failure: the assertion
        // that follows is the one allowed to fail.
    });
}

/**
 * Type a term and wait for the suggestions to stop changing.
 *
 * The panel is rebuilt from scratch every time a debounced request lands, so a
 * click issued while one is still in flight lands on a node that is about to be
 * replaced — and goes nowhere. Waiting for the spinner to clear means the
 * element clicked is the element that stays.
 */
export async function searchAndSettle(page, term) {
    await headerSearch(page).fill(term);

    const panel = page.getByRole('listbox', { name: 'Search suggestions' });

    await expect(panel).toBeVisible();
    await expect(page.locator('.animate-spin').first()).toBeHidden();
    await settleNetwork(page);

    return panel;
}

/**
 * Put `quantity` of a stocked card in the cart through the real product page.
 *
 * Adding to the cart deliberately keeps the shopper on the product page — they
 * are usually about to add a second denomination — so this navigates to the
 * cart itself rather than expecting a redirect.
 */
export async function addToCart(page, productPath = '/product/steam-wallet-hkd', quantity = 1) {
    await gotoStable(page, productPath);
    await waitForAlpine(page);

    for (let i = 1; i < quantity; i++) {
        await page.getByRole('button', { name: /Increase quantity/i }).click();
    }

    await page.getByRole('button', { name: /^Add to cart$/i }).click();
    await expect(page.getByText('Added to cart!')).toBeVisible();
    await page.waitForLoadState('domcontentloaded');
}

/**
 * Open a dropdown by the id its trigger points at, and prove it landed on
 * screen.
 *
 * An absolutely-positioned menu anchors to one edge of its trigger, and which
 * edge is right depends on where that trigger sits at this width -- a panel
 * anchored to the right of a trigger that has wrapped to the left of the row
 * hangs off the side of the phone. Nothing else catches it: an element at a
 * negative x is invisible without widening the page, so the horizontal
 * overflow check sees nothing wrong.
 */
export async function expectMenuOnScreen(page, menu) {
    await page.locator(`[aria-controls="${menu}"]`).click();

    const panel = page.locator(`#${menu}`);

    await expect(panel).toBeVisible();

    const box = await panel.boundingBox();
    const viewport = page.viewportSize();

    expect(box.x, `#${menu} runs off the left edge`).toBeGreaterThanOrEqual(0);
    expect(
        box.x + box.width,
        `#${menu} runs off the right edge`,
    ).toBeLessThanOrEqual(viewport.width);
}
