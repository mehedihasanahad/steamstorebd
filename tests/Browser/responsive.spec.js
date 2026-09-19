import { test, expect } from '@playwright/test';
import { waitForAlpine, gotoStable, expectMenuOnScreen } from './helpers.js';

/**
 * What a phone gets that a desktop does not, and vice versa.
 *
 * Runs on the mobile project only — see `testIgnore` on the desktop project
 * in playwright.config.js. A layout assertion written at 1440px cannot say
 * anything about the breakpoint it never crosses.
 */

const categoriesToggle = (page) => page.getByRole('button', { name: /Categories/ });

test.describe('the catalog listing on a phone', () => {
    test('reaches the brands inside a section', async ({ page }) => {
        await gotoStable(page, '/category/gift-cards');
        await waitForAlpine(page);

        const tree = page.locator('#catalog-tree-mobile');

        // Closed to begin with: the listing is what the reader came for.
        await expect(tree).toBeHidden();
        await expect(categoriesToggle(page)).toHaveAttribute('aria-expanded', 'false');

        await categoriesToggle(page).click();

        await expect(tree).toBeVisible();
        await expect(tree.getByRole('link', { name: /Steam/ }).first()).toBeVisible();
    });

    test('a brand in the dropdown is a real link', async ({ page }) => {
        await gotoStable(page, '/category/gift-cards');
        await waitForAlpine(page);

        await categoriesToggle(page).click();

        const brand = page.locator('#catalog-tree-mobile').locator('a[href$="/brand/steam"]').first();

        await expect(brand).toBeVisible();
        await brand.click();

        await page.waitForURL(/\/brand\/steam/);
    });

    test('closes on Escape', async ({ page }) => {
        await gotoStable(page, '/category/gift-cards');
        await waitForAlpine(page);

        await categoriesToggle(page).click();
        await expect(page.locator('#catalog-tree-mobile')).toBeVisible();

        await page.keyboard.press('Escape');

        await expect(page.locator('#catalog-tree-mobile')).toBeHidden();
    });

    test('still offers the section chips for a one-tap switch', async ({ page }) => {
        await gotoStable(page, '/category/gift-cards');

        await expect(page.getByRole('link', { name: 'Games Top Up', exact: true })).toBeVisible();
    });

    test('never exposes both copies of the tree at once', async ({ page }) => {
        await gotoStable(page, '/category/gift-cards');
        await waitForAlpine(page);

        const categories = page.getByRole('navigation', { name: 'Categories' });

        // The tree is in the markup twice, once per breakpoint. On a phone the
        // desktop sidebar is display:none and the dropdown starts closed, so
        // neither is in the accessibility tree yet.
        await expect(categories).toHaveCount(0);

        await categoriesToggle(page).click();

        // Opening it exposes the phone's copy, and only the phone's copy.
        await expect(categories).toHaveCount(1);
    });
});

test.describe('dropdowns open on screen, not off the side', () => {
    test('the region filter', async ({ page }) => {
        await gotoStable(page, '/category/gift-cards');
        await waitForAlpine(page);

        await expectMenuOnScreen(page, 'region-filter-menu');
    });

    test('the sort menu', async ({ page }) => {
        await gotoStable(page, '/category/gift-cards');
        await waitForAlpine(page);

        await expectMenuOnScreen(page, 'sort-menu');
    });

    test('the product region switcher', async ({ page }) => {
        await gotoStable(page, '/product/steam-wallet-hkd');
        await waitForAlpine(page);

        await expectMenuOnScreen(page, 'region-switcher-menu');
    });
});

test.describe('the product page on a phone', () => {
    test('the title gets the width to be read', async ({ page }) => {
        await gotoStable(page, '/product/steam-wallet-hkd');

        const heading = await page.getByRole('heading', { level: 1 }).boundingBox();
        const width = page.viewportSize().width;

        // The favourite control used to sit beside the title as a
        // flex-shrink-0 sibling and leave it about a third of the row.
        expect(heading.width).toBeGreaterThan(width * 0.6);
    });

    test('the favourite control is on its own line, full width', async ({ page }) => {
        await gotoStable(page, '/product/steam-wallet-hkd');

        const heading = await page.getByRole('heading', { level: 1 }).boundingBox();
        const favourite = await page.getByRole('link', { name: /favourite/i }).boundingBox();

        expect(favourite.y, 'the favourite control still shares the title row').toBeGreaterThan(heading.y + heading.height);
        expect(favourite.width).toBeGreaterThan(page.viewportSize().width * 0.8);
    });

    test('the chat buttons do not cover the Buy now button', async ({ page }) => {
        await gotoStable(page, '/product/steam-wallet-hkd');
        await waitForAlpine(page);

        // Scoped to the sticky bar: the panel further up the page offers a
        // Buy now of its own.
        const buy = await page.locator('.mobile-buy-bar')
            .getByRole('button', { name: 'Buy now' })
            .boundingBox();

        expect(buy, 'the sticky purchase bar is not showing').not.toBeNull();

        for (const name of ['Chat on WhatsApp', 'Chat on Messenger']) {
            const chat = await page.getByRole('link', { name }).boundingBox();

            const overlaps = chat.x < buy.x + buy.width
                && chat.x + chat.width > buy.x
                && chat.y < buy.y + buy.height
                && chat.y + chat.height > buy.y;

            expect(overlaps, `${name} sits on top of the Buy now button`).toBe(false);
        }
    });

    test('the chat buttons stay in the corner on a page with no purchase bar', async ({ page }) => {
        await gotoStable(page, '/faq');

        const chat = await page.getByRole('link', { name: 'Chat on WhatsApp' }).boundingBox();
        const height = page.viewportSize().height;

        // 20px from the bottom: lifting it everywhere would look like a mistake.
        expect(height - (chat.y + chat.height)).toBeLessThan(40);
    });
});
