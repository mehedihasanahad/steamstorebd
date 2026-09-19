import { test as setup, expect } from '@playwright/test';

const SHOPPER_STATE = 'storage/browser-tests/shopper.json';

/**
 * Sign in once and save the session, so the account-page tests do not each pay
 * for a login — and so a broken login fails here, loudly, instead of showing
 * up as a dozen confusing redirects somewhere else.
 *
 * The credentials come from DemoStorefrontSeeder.
 */
setup('sign in as the demo shopper', async ({ page }) => {
    await page.goto('/login');

    await page.getByLabel('Email address').fill('shopper@steamstorebd.test');
    await page.getByLabel('Password', { exact: true }).fill('password');
    await page.getByRole('button', { name: /Sign in to account/i }).click();

    // Breeze sends /login to /dashboard, which sends on to /.
    await page.waitForURL((url) => url.pathname === '/');

    /*
     | Retried as a unit rather than navigated once: the post-login redirect
     | chain is still settling, and a goto issued into it is either aborted or
     | overtaken by the redirect that was already in flight. Retrying the
     | navigate-and-check together rides that out, while still failing loudly
     | if the session genuinely is not signed in.
     |
     | domcontentloaded, not load: the page holds analytics and font
     | connections open, so waiting for quiet never settles.
     */
    await expect(async () => {
        await page.goto('/favourites', { waitUntil: 'domcontentloaded' });
        await expect(page.getByRole('heading', { level: 1 })).toContainText('Favourites');
    }).toPass({ timeout: 20_000 });

    await page.context().storageState({ path: SHOPPER_STATE });
});

export { SHOPPER_STATE };
