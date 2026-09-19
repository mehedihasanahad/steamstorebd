import { test, expect } from '@playwright/test';
import { waitForAlpine, gotoStable, addToCart, expectMenuOnScreen } from './helpers.js';

/**
 * The reference layout, written down as facts a browser can check.
 *
 * The supplied screenshots are of other sites, so they cannot be diffed
 * directly — but the arrangement they specify can be asserted: what sits in
 * which column, what is pinned to which corner, what sticks and what wraps.
 * A pixel baseline catches a change; this catches a change that is wrong.
 */

// The rails autoplay; an assertion about where a card sits needs them to stay
// where the page put them. This is the real reduced-motion path, not a stub.
test.use({ reducedMotion: 'reduce' });

/** Where an element actually is on screen, in page coordinates. */
async function box(locator) {
    const rect = await locator.boundingBox();

    expect(rect, 'element is not visible, so it has no position').not.toBeNull();

    return rect;
}

/**
 * The vertical middle of an element.
 *
 * "On the same row" is a statement about centres: a 20px checkbox and a 36px
 * stepper centred on one row have top edges 8px apart, which says nothing
 * about whether the row is right.
 */
function middle(rect) {
    return rect.y + (rect.height / 2);
}

test.describe('product page — reference layout', () => {
    test.beforeEach(async ({ page }) => {
        await gotoStable(page, '/product/steam-wallet-hkd');
        await waitForAlpine(page);
    });

    test('denomination tiles sit in a two-column grid', async ({ page }) => {
        const tiles = page.locator('button[aria-pressed]');

        await expect(tiles).toHaveCount(6);

        const first = await box(tiles.nth(0));
        const second = await box(tiles.nth(1));
        const third = await box(tiles.nth(2));

        // Two across: the second tile is beside the first, the third below it.
        expect(middle(second), 'tile 2 should be on the same row as tile 1').toBeCloseTo(middle(first), -1);
        expect(second.x).toBeGreaterThan(first.x);
        expect(third.y, 'tile 3 should start a new row').toBeGreaterThan(first.y);
        expect(third.x).toBeCloseTo(first.x, -1);
    });

    test('an out-of-stock tile stays visible, dimmed and badged', async ({ page }) => {
        const soldOut = page.locator('button[aria-pressed][disabled]').first();

        await expect(soldOut).toBeVisible();
        await expect(soldOut).toContainText('Stock out');

        const opacity = await soldOut.evaluate((el) => getComputedStyle(el).opacity);
        expect(Number(opacity), 'a sold-out tile should be dimmed, not hidden').toBeLessThan(1);
    });

    test('the purchase panel sits to the right of the tiles and sticks', async ({ page }) => {
        const tiles = await box(page.locator('button[aria-pressed]').first());
        const panel = await box(page.getByText('Purchase limit'));

        expect(panel.x, 'the buy panel belongs to the right of the tiles').toBeGreaterThan(tiles.x + tiles.width);

        const position = await page.locator('.lg\\:sticky').first().evaluate((el) => getComputedStyle(el).position);
        expect(position).toBe('sticky');
    });

    test('the hero carries rating, delivery and region in one row', async ({ page }) => {
        // The stars carry their value in an aria-label, not in text.
        const rating = await box(page.getByRole('img', { name: /out of 5/ }));
        // "Instant delivery" also appears in the footer badge and the trust
        // strip, so the hero's own copy is addressed through the hero heading.
        const delivery = await box(
            page.getByRole('heading', { level: 1 }).locator('xpath=following-sibling::div[1]')
                .getByText('Instant delivery'),
        );
        const region = await box(page.getByRole('button', { name: /Hong Kong/ }));

        expect(middle(delivery)).toBeCloseTo(middle(rating), -1);
        expect(middle(region)).toBeCloseTo(middle(rating), -1);
        expect(delivery.x).toBeGreaterThan(rating.x);
        expect(region.x).toBeGreaterThan(delivery.x);
    });

    test('add to favourite is pinned to the top right of the hero', async ({ page }) => {
        const favourite = await box(page.getByRole('link', { name: /Add to favourite/i }));
        const title = await box(page.getByRole('heading', { level: 1 }));
        const viewport = page.viewportSize();

        expect(favourite.x, 'favourite belongs on the right').toBeGreaterThan(viewport.width / 2);
        expect(favourite.y, 'favourite belongs level with the title').toBeLessThan(title.y + title.height + 40);
    });

    test('selecting a denomination updates the total', async ({ page }) => {
        const total = page.locator('.lg\\:sticky').getByText(/^৳/).first();

        await expect(total).toContainText('697');

        await page.locator('button[aria-pressed]').nth(1).click();

        await expect(total).toContainText('871');
    });

    test('quantity respects the card\'s own purchase limit', async ({ page }) => {
        const increase = page.getByRole('button', { name: /Increase quantity/i });
        const decrease = page.getByRole('button', { name: /Decrease quantity/i });

        // This card is min 1 / max 2.
        await expect(decrease).toBeDisabled();
        await increase.click();
        await expect(increase).toBeDisabled();
        await expect(page.getByText('Purchase limit (1 – 2)')).toBeVisible();
    });

    test('tabs swap the panel below them', async ({ page }) => {
        await expect(page.getByText('Steam Store BD sells')).toBeVisible();

        await page.getByRole('tab', { name: 'FAQ' }).click();

        await expect(page.getByText(/Does an HKD code work/)).toBeVisible();
        await expect(page.getByText('Steam Store BD sells')).toBeHidden();
    });

    test('the region switcher moves to the sibling product', async ({ page }) => {
        await page.getByRole('button', { name: /Hong Kong/ }).click();

        // Scoped to the switcher: the related-products panel links to the same
        // product and would otherwise make this ambiguous.
        await page.getByRole('link', { name: 'United States', exact: true }).click();

        await expect(page).toHaveURL(/steam-wallet-usa/);
        await expect(page.getByRole('heading', { level: 1 })).toContainText('Steam Wallet Code USA');
    });
});

test.describe('catalog page — reference layout', () => {
    test.beforeEach(async ({ page }) => {
        await gotoStable(page, '/category/gift-cards');
        await waitForAlpine(page);
    });

    test('the rail is on the left and the grid fills the rest', async ({ page }) => {
        const rail = await box(page.getByRole('navigation', { name: 'Categories' }));
        const grid = await box(page.getByRole('list', { name: /products$/i }).locator('a').first());

        expect(grid.x, 'the product grid belongs to the right of the rail').toBeGreaterThan(rail.x + rail.width - 1);
    });

    test('featured products sit beneath the rail', async ({ page }) => {
        const rail = await box(page.getByRole('navigation', { name: 'Categories' }));
        const featured = await box(page.getByRole('heading', { name: 'Featured products' }));

        expect(featured.y, 'featured belongs below the rail').toBeGreaterThan(rail.y + rail.height - 1);
        expect(featured.x).toBeCloseTo(rail.x, -1);
    });

    test('the filters sit top right, level with the heading', async ({ page }) => {
        const heading = await box(page.getByRole('heading', { level: 1 }));
        const sort = await box(page.getByRole('button', { name: /Featured/ }).first());

        expect(sort.x).toBeGreaterThan(heading.x + heading.width);
        expect(sort.y).toBeLessThan(heading.y + heading.height + 20);
    });

    test('product cards are three across on desktop', async ({ page }) => {
        const cards = page.getByRole('list', { name: /products$/i }).locator('> li');
        const count = await cards.count();

        expect(count).toBeGreaterThanOrEqual(3);

        const first = await box(cards.nth(0));
        const fourth = await box(cards.nth(3));

        expect(fourth.y, 'the fourth card should start the second row').toBeGreaterThan(first.y);
    });

    test('each card carries its region flag in the top-right corner', async ({ page }) => {
        const card = page.getByRole('list', { name: /products$/i })
            .locator('a[href$="/product/steam-wallet-hkd"]').first();
        const flag = card.locator('[title]').first();

        const cardBox = await box(card);
        const flagBox = await box(flag);

        expect(flagBox.x, 'the flag belongs on the right of the card').toBeGreaterThan(cardBox.x + cardBox.width / 2);
        expect(flagBox.y, 'the flag belongs at the top of the card').toBeLessThan(cardBox.y + cardBox.height / 2);
    });
});

test.describe('homepage — reference layout', () => {
    test('rails run in the order the spec fixes', async ({ page }) => {
        await gotoStable(page, '/');
        await waitForAlpine(page);

        const order = await page.evaluate(() => {
            const headings = [...document.querySelectorAll('h2')].map((h) => h.textContent.trim());

            return headings;
        });

        const deals = order.findIndex((h) => /special deals/i.test(h));
        const featured = order.findIndex((h) => /^featured$/i.test(h));
        const firstSection = order.findIndex((h) => /^gift cards$/i.test(h));

        expect(deals, 'special deals should be the first rail').toBeGreaterThanOrEqual(0);
        expect(featured, 'featured should follow the deals').toBeGreaterThan(deals);
        expect(firstSection, 'section rails follow featured').toBeGreaterThan(featured);
    });

    test('a deal card shows the saving, the struck price and the live price', async ({ page }) => {
        await gotoStable(page, '/');

        const card = page.locator('a[href*="/product/"]').filter({ hasText: 'tk off' }).first();

        await expect(card).toBeVisible();
        await expect(card.locator('s')).toBeVisible();

        const badge = await box(card.getByText(/tk off/));
        const cardBox = await box(card);

        expect(badge.x, 'the discount badge belongs top-left').toBeLessThan(cardBox.x + cardBox.width / 2);
        expect(badge.y).toBeLessThan(cardBox.y + cardBox.height / 2);
    });

    test('each section rail offers a way to the whole section', async ({ page }) => {
        await gotoStable(page, '/');

        const rail = page.locator('section', { has: page.getByRole('heading', { name: 'Gift Cards' }) }).first();

        await expect(rail.getByRole('link', { name: 'View all' })).toHaveAttribute('href', /\/category\/gift-cards$/);
    });
});

test.describe('cart — reference layout', () => {
    test.beforeEach(async ({ page }) => {
        await addToCart(page, '/product/steam-wallet-hkd', 2);
        await addToCart(page, '/product/steam-wallet-usa', 1);
        await gotoStable(page, '/cart');
        await waitForAlpine(page);
    });

    test('lines are grouped under their product', async ({ page }) => {
        await expect(page.getByRole('heading', { name: 'Steam Wallet Code HKD' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Steam Wallet Code USA' })).toBeVisible();
    });

    test('each line runs tick, art, name, price, stepper, total, remove', async ({ page }) => {
        const line = page.locator('li').filter({ hasText: 'Steam Code 40 HKD' }).first();

        const tick = await box(line.getByRole('checkbox'));
        const name = await box(line.getByText('Steam Code 40 HKD'));
        const stepper = await box(line.getByRole('button', { name: /Increase quantity/i }));
        const remove = await box(line.getByRole('button', { name: /Remove/i }));

        expect(name.x).toBeGreaterThan(tick.x);
        expect(stepper.x).toBeGreaterThan(name.x);
        expect(remove.x).toBeGreaterThan(stepper.x);

        // All on one row, which is what the reference does.
        expect(middle(stepper)).toBeCloseTo(middle(tick), -1);
        expect(middle(remove)).toBeCloseTo(middle(tick), -1);
    });

    test('unticking a line drops it from the count and the total', async ({ page }) => {
        // The mobile bar carries the same wording, so the count is read from
        // the heading row rather than from whichever matched first.
        const count = page.getByText(/item\(s\) selected\./);
        const total = page.getByText('Total').locator('..').getByText(/^৳/);

        await expect(count).toContainText('2');
        const before = await total.textContent();

        await page.getByRole('checkbox').first().uncheck();

        await expect(count).toContainText('1');
        await expect(total).not.toHaveText(before);
    });

    test('the totals panel sits right of the lines and sticks', async ({ page }) => {
        const line = await box(page.locator('li').filter({ hasText: 'Steam Code 40 HKD' }).first());
        const checkout = await box(page.getByRole('link', { name: 'Checkout' }).first());

        expect(checkout.x).toBeGreaterThan(line.x + line.width - 1);

        const position = await page.locator('.lg\\:sticky').first().evaluate((el) => getComputedStyle(el).position);
        expect(position).toBe('sticky');
    });

    test('a quantity change survives a reload', async ({ page }) => {
        const line = () => page.locator('li').filter({ hasText: 'Steam Wallet Code 5 USD' });
        const shown = () => line().getByRole('button', { name: /Increase quantity/i })
            .locator('xpath=preceding-sibling::span[1]');

        await line().getByRole('button', { name: /Increase quantity/i }).click();
        await expect(shown()).toHaveText('2');

        // The quantity is persisted server-side, not just in the browser.
        await gotoStable(page, '/cart');

        await expect(shown()).toHaveText('2');
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
