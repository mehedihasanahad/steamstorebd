import { test, expect } from '@playwright/test';
import { PUBLIC_PAGES, watchForErrors, expectNoHorizontalOverflow, waitForAlpine } from './helpers.js';

/**
 * Every public page, in a real browser: does it render, does its JavaScript
 * boot without throwing, and does it stay inside the viewport.
 *
 * The feature suite proves the HTML is right. This proves the page actually
 * works once a browser has run it.
 */
test.describe('every public page', () => {
    for (const { path, name, heading } of PUBLIC_PAGES) {
        test(`${name} — ${path}`, async ({ page }) => {
            const errors = watchForErrors(page);

            const response = await page.goto(path);
            expect(response.status(), `${path} did not return 200`).toBe(200);

            // Exactly one h1, always: a page with none tells a screen reader
            // and a crawler nothing, and a page with several tells them the
            // wrong thing.
            await expect(page.locator('h1')).toHaveCount(1);
            await expect(page.locator('h1')).toContainText(heading);
            await expect(page).toHaveTitle(/Steam Store BD/);

            await waitForAlpine(page);
            await expectNoHorizontalOverflow(page);

            // x-cloak hides elements until Alpine takes over. Anything still
            // cloaked after boot means a component failed to initialise.
            const stuckCloaked = await page.locator('[x-cloak]:visible').count();
            expect(stuckCloaked, 'an element is still x-cloaked after Alpine booted').toBe(0);

            expect(errors, `JavaScript errors on ${path}`).toEqual([]);
        });
    }
});

test.describe('pages that should not exist', () => {
    test('an unknown product renders the branded 404', async ({ page }) => {
        const response = await page.goto('/product/no-such-product');

        expect(response.status()).toBe(404);
        await expect(page.locator('h1')).toContainText("This page doesn't exist");
        await expect(page.getByRole('link', { name: /Browse the catalog/i })).toBeVisible();
    });

    test('an unknown section renders the branded 404', async ({ page }) => {
        const response = await page.goto('/category/no-such-section');

        expect(response.status()).toBe(404);
    });
});

test.describe('the design system is actually applied', () => {
    test('the page paints the ash surface, not the old navy', async ({ page }) => {
        await page.goto('/');

        const background = await page.evaluate(() => getComputedStyle(document.body).backgroundColor);

        // --surface-0 is 15 15 17. The retired theme was 4 13 26.
        expect(background).toBe('rgb(15, 15, 17)');
    });

    test('the design tokens resolve at runtime', async ({ page }) => {
        await page.goto('/');

        const tokens = await page.evaluate(() => {
            const root = getComputedStyle(document.documentElement);

            return {
                surface0: root.getPropertyValue('--surface-0').trim(),
                surface1: root.getPropertyValue('--surface-1').trim(),
                textHi: root.getPropertyValue('--text-hi').trim(),
                accent: root.getPropertyValue('--accent').trim(),
                success: root.getPropertyValue('--success').trim(),
            };
        });

        expect(tokens).toEqual({
            surface0: '15 15 17',
            surface1: '23 23 26',
            textHi: '244 244 245',
            accent: '37 99 235',
            success: '34 197 94',
        });
    });

    test('Inter is the family in use', async ({ page }) => {
        await page.goto('/');

        const family = await page.evaluate(() => getComputedStyle(document.body).fontFamily);

        expect(family).toContain('Inter');
    });
});
