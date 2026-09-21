import { test, expect } from '@playwright/test';
import { waitForAlpine, gotoStable } from './helpers.js';

/**
 * The Copy buttons on a delivered order.
 *
 * The clipboard is the whole point of the page: a gift card code the buyer
 * cannot get out of the browser has not really been delivered. None of this
 * can be proved from markup — it is an async browser API behind a click, and
 * the API is missing entirely outside a secure context.
 *
 * Buttons are located by the row they sit in, never by their label: the label
 * is what these tests assert on, and a locator that reads it would stop
 * matching the moment it changed to "Copied".
 */

/** The nth code row on the page, and the button that copies it. */
function codeRow(page, index = 0) {
    return page.locator('li').filter({ has: page.locator('code') }).nth(index);
}
test.describe('copying a delivered code', () => {
    test.use({ storageState: 'storage/browser-tests/shopper.json' });

    test.beforeEach(async ({ context }) => {
        await context.grantPermissions(['clipboard-read', 'clipboard-write']);
    });

    test('puts the code on the clipboard', async ({ page }) => {
        await gotoStable(page, '/orders/BD2026-100001');
        await waitForAlpine(page);

        const code = await page.locator('code').first().innerText();

        expect(code, 'the order shows no code to copy').toBeTruthy();

        await codeRow(page).getByRole('button').click();

        const clipboard = await page.evaluate(() => navigator.clipboard.readText());

        expect(clipboard).toBe(code.trim());
    });

    test('says Copied, and goes back to Copy', async ({ page }) => {
        await gotoStable(page, '/orders/BD2026-100001');
        await waitForAlpine(page);

        const button = codeRow(page).getByRole('button');

        // Asserted on which label is visible, not on the button's text: all
        // three labels live in the button and only one of them is shown.
        await button.click();

        await expect(button.getByText('Copied')).toBeVisible();

        await expect(button.getByText('Copy', { exact: true })).toBeVisible({ timeout: 5_000 });
        await expect(button.getByText('Copied')).toBeHidden();
    });

    test('never silently claims success', async ({ page }) => {
        await gotoStable(page, '/orders/BD2026-100001');
        await waitForAlpine(page);

        // Take the modern API away, as an insecure origin does. The fallback
        // has to carry it — and if even that fails, the button must say so
        // rather than flashing "Copied" over an empty clipboard.
        await page.evaluate(() => {
            Object.defineProperty(navigator, 'clipboard', { value: undefined, configurable: true });
        });

        const button = codeRow(page).getByRole('button');

        await button.click();

        // Exactly one outcome label shows: either it copied, or it admits it
        // did not. What it must never do is sit there still saying "Copy".
        await expect(button.getByText(/Copied|Failed/).filter({ visible: true })).toHaveCount(1);
        await expect(button.getByText('Copy', { exact: true })).toBeHidden();
    });

    test('copies the code, not the one below it', async ({ page }) => {
        await gotoStable(page, '/orders/BD2026-100001');
        await waitForAlpine(page);

        const rows = page.locator('li').filter({ has: page.locator('code') });

        if (await rows.count() < 2) test.skip(true, 'this order has a single code');

        const second = (await page.locator('code').nth(1).innerText()).trim();

        await codeRow(page, 1).getByRole('button').click();

        expect(await page.evaluate(() => navigator.clipboard.readText())).toBe(second);
    });
});
