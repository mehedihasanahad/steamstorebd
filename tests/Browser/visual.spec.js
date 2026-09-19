import { test, expect } from '@playwright/test';
import { waitForAlpine, addToCart, gotoStable } from './helpers.js';

/**
 * Pixel baselines for every storefront page.
 *
 * The first run writes the baseline; every run after it fails if a pixel moves.
 * That is the part of "does it still look right" a DOM assertion cannot cover —
 * a broken grid, a collapsed panel or a lost background changes no markup.
 *
 * Update a baseline deliberately, never reflexively:
 *
 *     npx playwright test visual.spec.js --update-snapshots
 */

/** Things that legitimately differ run to run and would make every diff fail. */
async function freezePage(page) {
    await waitForAlpine(page);

    await page.addStyleTag({
        content: `
            *, *::before, *::after {
                animation-duration: 0s !important;
                animation-delay: 0s !important;
                transition-duration: 0s !important;
                transition-delay: 0s !important;
                caret-color: transparent !important;
            }
            /* The hero autoplays; a baseline has to catch it on slide one. */
            .hero-rail { scroll-behavior: auto !important; }
        `,
    });

    await page.evaluate(() => window.scrollTo(0, 0));
    await page.waitForTimeout(400);
}

const PAGES = [
    ['home', '/'],
    ['section-gift-cards', '/category/gift-cards'],
    ['section-game-top-up', '/category/game-top-up'],
    ['section-empty', '/category/utility'],
    ['brand-steam', '/brand/steam'],
    ['product-steam-hkd', '/product/steam-wallet-hkd'],
    ['product-pubg-uc', '/product/pubg-uc'],
    ['product-subscription', '/product/hoichoi-premium'],
    ['search-results', '/search?q=steam'],
    ['search-empty', '/search'],
    ['cart-empty', '/cart'],
    ['faq', '/faq'],
    ['how-to-redeem', '/how-to-redeem'],
    ['contact', '/contact'],
    ['reseller', '/reseller'],
    ['order-lookup', '/orders'],
    ['about', '/about'],
    ['terms', '/terms'],
    ['login', '/login'],
    ['register', '/register'],
    ['not-found', '/product/does-not-exist'],
];

test.describe('guest pages', () => {
    for (const [name, path] of PAGES) {
        test(name, async ({ page }) => {
            await gotoStable(page, path);
            await freezePage(page);

            await expect(page).toHaveScreenshot(`${name}.png`, {
                fullPage: true,
                maxDiffPixelRatio: 0.01,
                animations: 'disabled',
            });
        });
    }
});

test.describe('pages that need a cart', () => {
    test('cart with lines from two products', async ({ page }) => {
        await addToCart(page, '/product/steam-wallet-hkd', 2);
        await addToCart(page, '/product/steam-wallet-usa', 1);

        await gotoStable(page, '/cart');
        await freezePage(page);

        await expect(page).toHaveScreenshot('cart-filled.png', {
            fullPage: true,
            maxDiffPixelRatio: 0.01,
            animations: 'disabled',
        });
    });
});

test.describe('pages that need a signed-in shopper', () => {
    test.use({ storageState: 'storage/browser-tests/shopper.json' });

    const ACCOUNT_PAGES = [
        ['account-orders', '/orders'],
        ['account-order-delivered', '/orders/BD2026-100001'],
        ['account-order-processing', '/orders/BD2026-100002'],
        ['account-order-credentials', '/orders/BD2026-100003'],
        ['account-favourites', '/favourites'],
        ['account-profile', '/profile'],
        ['account-referral', '/referral'],
    ];

    for (const [name, path] of ACCOUNT_PAGES) {
        test(name, async ({ page }) => {
            await gotoStable(page, path);
            await freezePage(page);

            await expect(page).toHaveScreenshot(`${name}.png`, {
                fullPage: true,
                maxDiffPixelRatio: 0.01,
                animations: 'disabled',
            });
        });
    }

    test('checkout', async ({ page }) => {
        await addToCart(page, '/product/steam-wallet-hkd', 1);

        await gotoStable(page, '/checkout');
        await freezePage(page);

        await expect(page).toHaveScreenshot('checkout.png', {
            fullPage: true,
            maxDiffPixelRatio: 0.01,
            animations: 'disabled',
        });
    });
});
