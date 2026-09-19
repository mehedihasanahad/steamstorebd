import { test, expect } from '@playwright/test';
import { watchForErrors, waitForAlpine, sectionTrigger, headerSearch, searchAndSettle, settleNetwork, addToCart } from './helpers.js';

/**
 * The header: the catalog mega-panel and the type-ahead search. Both are the
 * primary way into a catalog that now spans four verticals, and neither can be
 * proved by rendering HTML — they only exist once JavaScript runs.
 */
test.describe('the catalog mega-panel', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/faq');
        await waitForAlpine(page);
    });

    test('opens on hover and shows that section\'s brands', async ({ page }) => {
        const trigger = sectionTrigger(page, 'gift-cards');

        await expect(trigger).toHaveAttribute('aria-expanded', 'false');

        await trigger.hover();

        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await expect(page.getByRole('navigation', { name: 'Catalog sections' })).toBeVisible();
        await expect(page.locator('a[href$="/brand/steam"]').first()).toBeVisible();
    });

    test('swaps sections without a network request', async ({ page }) => {
        /*
         | The whole catalog tree is delivered with the page, so moving between
         | sections must not cost a round trip for data. Images are excluded:
         | the panel's brand art is lazy-loaded, so revealing a section quite
         | correctly fetches the pictures it just made visible.
         */
        const requests = [];
        page.on('request', (request) => {
            if (request.resourceType() === 'image') return;

            requests.push(request.url());
        });

        await sectionTrigger(page, 'gift-cards').hover();
        await expect(page.locator('a[href$="/brand/steam"]').first()).toBeVisible();

        requests.length = 0;

        await sectionTrigger(page, 'game-top-up').hover();
        await expect(page.locator('a[href$="/brand/pubg"]').first()).toBeVisible();

        expect(requests, 'hovering a section fetched something').toEqual([]);
    });

    test('lists the regions a section sells into, each a real link', async ({ page }) => {
        await sectionTrigger(page, 'gift-cards').hover();

        const region = page.locator('a[href*="region=HK"]').first();

        await expect(region).toBeVisible();

        // Opening the panel starts a dozen lazy brand-tile fetches; let them
        // finish before clicking, or the navigation queues behind them.
        await settleNetwork(page);
        await region.click();

        // waitForURL, not toHaveURL: a 5s assertion poll can expire first.
        await page.waitForURL(/\/category\/gift-cards\?region=HK/);
        await expect(page.getByRole('heading', { level: 1 })).toContainText('Gift Cards');
    });

    test('closes on Escape', async ({ page }) => {
        const trigger = sectionTrigger(page, 'gift-cards');

        await trigger.hover();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.keyboard.press('Escape');

        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('opens from the keyboard too', async ({ page }) => {
        const trigger = sectionTrigger(page, 'gift-cards');

        await trigger.focus();

        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await expect(page.getByRole('navigation', { name: 'Catalog sections' })).toBeVisible();
    });

    test('every trigger is a real link, so the menu works without JavaScript', async ({ browser }) => {
        const context = await browser.newContext({ javaScriptEnabled: false });
        const page = await context.newPage();

        await page.goto('/faq');

        await expect(page.locator('a[href$="/category/gift-cards"]').first())
            .toHaveAttribute('href', /\/category\/gift-cards$/);

        await context.close();
    });
});

test.describe('search', () => {
    test('suggests products as you type', async ({ page }) => {
        const errors = watchForErrors(page);

        await page.goto('/');
        await waitForAlpine(page);

        await headerSearch(page).fill('steam');

        const panel = page.getByRole('listbox', { name: 'Search suggestions' });

        await expect(panel).toBeVisible();
        await expect(panel.getByText('Steam Wallet Code HKD')).toBeVisible();
        await expect(panel.getByText(/from ৳/).first()).toBeVisible();

        expect(errors).toEqual([]);
    });

    test('groups brands above products', async ({ page }) => {
        await page.goto('/');
        await waitForAlpine(page);

        await headerSearch(page).fill('pubg');

        const panel = page.getByRole('listbox', { name: 'Search suggestions' });

        await expect(panel.getByText('Brands')).toBeVisible();
        await expect(panel.getByText('Products')).toBeVisible();
    });

    test('says so when nothing matches', async ({ page }) => {
        await page.goto('/');
        await waitForAlpine(page);

        // A term nothing in the catalog could match — Nintendo is a real brand
        // here, so it would be the wrong kind of miss.
        await headerSearch(page).fill('zzzznothing');

        await expect(page.getByText(/Nothing matched/)).toBeVisible();
    });

    test('suggests nothing below two characters', async ({ page }) => {
        await page.goto('/');
        await waitForAlpine(page);

        await headerSearch(page).fill('s');
        await page.waitForTimeout(500);

        await expect(page.getByRole('listbox', { name: 'Search suggestions' })).toBeHidden();
    });

    test('a suggestion goes to the product', async ({ page }) => {
        await page.goto('/');
        await waitForAlpine(page);

        const panel = await searchAndSettle(page, 'HKD');

        await panel.getByText('Steam Wallet Code HKD').click();

        await page.waitForURL(/\/product\/steam-wallet-hkd/);
    });

    test('the magnifier submits the search', async ({ page }) => {
        await page.goto('/');
        await waitForAlpine(page);

        await searchAndSettle(page, 'steam');
        await page.getByRole('button', { name: 'Search' }).first().click();

        await page.waitForURL(/[?&]q=steam/);
        await expect(page.getByRole('heading', { level: 1 })).toContainText('Results for');
    });

    test('submitting the form lands on the results page', async ({ page }) => {
        await page.goto('/');
        await waitForAlpine(page);

        await searchAndSettle(page, 'steam');
        await headerSearch(page).press('Enter');

        await page.waitForURL(/\/search\?q=steam/);
        await expect(page.getByRole('heading', { level: 1 })).toContainText('Results for');
    });
});

test.describe('the cart badge', () => {
    test('counts what is in the cart on every page', async ({ page }) => {
        await addToCart(page, '/product/steam-wallet-usa', 2);

        await page.goto('/faq');

        await expect(page.getByRole('link', { name: /Cart — 2 item/i })).toBeVisible();
    });
});

test.describe('the footer', () => {
    test('links the catalog, help and policy pages', async ({ page }) => {
        await page.goto('/');

        const footer = page.locator('footer');

        await expect(footer.getByRole('link', { name: 'Steam', exact: true })).toBeVisible();
        await expect(footer.getByRole('link', { name: 'How to Redeem' })).toBeVisible();
        await expect(footer.getByRole('link', { name: 'Refund Policy' })).toBeVisible();
        await expect(footer.getByText(/independent reseller/)).toBeVisible();
    });
});
