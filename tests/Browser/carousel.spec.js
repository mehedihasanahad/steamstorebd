import { test, expect } from '@playwright/test';
import { waitForAlpine, gotoStable } from './helpers.js';

/**
 * The carousels: they move on their own, and they move under the hand.
 *
 * None of this is provable from rendered HTML — autoplay is a timer and a drag
 * is a stream of pointer events, so both only exist in a real browser.
 */

/** The scrolling element of the rail under a given section heading. */
function railUnder(page, heading) {
    return page.getByRole('region', { name: heading }).locator('.rail');
}

const heroRail = (page) => page.getByRole('region', { name: 'Promotions' }).locator('.rail');

const scrollLeft = (rail) => rail.evaluate((el) => el.scrollLeft);

/** Drag horizontally across an element with the mouse, as a person would. */
async function drag(page, locator, distance) {
    const box = await locator.boundingBox();

    expect(box, 'nothing to drag').not.toBeNull();

    const y = box.y + box.height / 2;

    // Start near the right edge and clamp the finish to the viewport: a card
    // sitting at the left of the rail has no 300px of room to its left.
    const viewport = page.viewportSize();
    const from = distance < 0
        ? Math.max(box.x + box.width * 0.2, 10)
        : Math.min(box.x + box.width * 0.8, viewport.width - 10);
    const to = Math.min(viewport.width - 10, Math.max(10, from - distance));

    await page.mouse.move(from, y);
    await page.mouse.down();
    await page.mouse.move(to, y, { steps: 12 });
    await page.mouse.up();
}

test.describe('autoplay', () => {
    test('the hero advances one slide at a time', async ({ page }) => {
        await gotoStable(page, '/');
        await waitForAlpine(page);

        const secondDot = page.getByRole('tab', { name: 'Go to slide 2' });

        await expect(secondDot).toHaveAttribute('aria-selected', 'false');

        // Left alone, the hero moves itself on to the next slide.
        await expect(secondDot).toHaveAttribute('aria-selected', 'true', { timeout: 20_000 });
    });

    test('a catalog rail advances on its own', async ({ page }) => {
        await gotoStable(page, '/');
        await waitForAlpine(page);

        const rail = railUnder(page, 'Exclusive Offers');

        expect(await scrollLeft(rail)).toBe(0);

        await expect.poll(() => scrollLeft(rail), { timeout: 20_000 }).toBeGreaterThan(0);
    });

    test('the reviews rail advances too', async ({ page }) => {
        await gotoStable(page, '/');
        await waitForAlpine(page);

        const rail = railUnder(page, 'What buyers say');

        await rail.scrollIntoViewIfNeeded();

        await expect.poll(() => scrollLeft(rail), { timeout: 20_000 }).toBeGreaterThan(0);
    });

    test('it holds still while the pointer is on the rail', async ({ page }) => {
        await gotoStable(page, '/');
        await waitForAlpine(page);

        const rail = railUnder(page, 'Exclusive Offers');

        await rail.hover();

        const before = await scrollLeft(rail);

        // Comfortably longer than the 5s step: a paused rail must not take one.
        await page.waitForTimeout(8_000);

        expect(await scrollLeft(rail)).toBe(before);
    });
});

test.describe('autoplay and reduced motion', () => {
    test.use({ reducedMotion: 'reduce' });

    test('nothing moves for a reader who asked for stillness', async ({ page }) => {
        await gotoStable(page, '/');
        await waitForAlpine(page);

        const rail = railUnder(page, 'Exclusive Offers');

        await page.waitForTimeout(8_000);

        expect(await scrollLeft(rail)).toBe(0);
        await expect(page.getByRole('tab', { name: 'Go to slide 1' })).toHaveAttribute('aria-selected', 'true');
    });
});

test.describe('the hero slide', () => {
    // A click is a click; autoplay sliding underneath it is a different test.
    test.use({ reducedMotion: 'reduce' });

    test('opens what it advertises', async ({ page }) => {
        await gotoStable(page, '/');
        await waitForAlpine(page);

        const slide = page.getByRole('region', { name: 'Promotions' }).getByRole('link').first();
        const href = await slide.getAttribute('href');

        expect(href, 'a slide that advertises something must link somewhere').toBeTruthy();

        await slide.click();

        await page.waitForURL(href);
    });

    test('is not opened by a drag that happens to end on it', async ({ page }) => {
        await gotoStable(page, '/');
        await waitForAlpine(page);

        await drag(page, heroRail(page), 600);

        await expect(page).toHaveURL(/\/$/);
    });

    test('takes the 4:3 shape of the phone artwork', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await gotoStable(page, '/');

        const image = heroRail(page).locator('img').first();
        const box = await image.boundingBox();

        // The box follows the picture rather than cropping it: every fixture
        // slide has a 4:3 phone cut, so the slider is 4:3 here.
        expect(box.width / box.height).toBeCloseTo(4 / 3, 1);
    });

    test('is 16:5 on a desktop, where the desktop artwork is shown', async ({ page }) => {
        await gotoStable(page, '/');

        const box = await heroRail(page).locator('img').first().boundingBox();

        expect(box.width / box.height).toBeCloseTo(16 / 5, 1);
    });
});

test.describe('dragging', () => {
    // A drag is a deliberate gesture; autoplay would muddy what moved it.
    test.use({ reducedMotion: 'reduce' });

    test('a catalog rail follows the mouse', async ({ page }) => {
        await gotoStable(page, '/');
        await waitForAlpine(page);

        const rail = railUnder(page, 'Exclusive Offers');

        expect(await scrollLeft(rail)).toBe(0);

        await drag(page, rail, 300);

        await expect.poll(() => scrollLeft(rail)).toBeGreaterThan(0);
    });

    test('the hero follows the mouse', async ({ page }) => {
        await gotoStable(page, '/');
        await waitForAlpine(page);

        const rail = heroRail(page);

        await drag(page, rail, 600);

        await expect(page.getByRole('tab', { name: 'Go to slide 2' })).toHaveAttribute('aria-selected', 'true');
    });

    test('a small hero drag finishes the slide instead of springing back', async ({ page }) => {
        await gotoStable(page, '/');
        await waitForAlpine(page);

        const rail = heroRail(page);

        // A nudge, far less than half a slide. Mandatory snap left to itself
        // would return to the slide it came from, because that is the nearest
        // snap point.
        await drag(page, rail, 40);

        await expect(page.getByRole('tab', { name: 'Go to slide 2' }))
            .toHaveAttribute('aria-selected', 'true');

        // And it arrives squarely, not resting between two slides.
        const railBox = await rail.boundingBox();
        const slide = await rail.locator('[aria-roledescription="slide"]').nth(1).boundingBox();

        const railMiddle = railBox.x + railBox.width / 2;
        const slideMiddle = slide.x + slide.width / 2;

        expect(Math.abs(slideMiddle - railMiddle)).toBeLessThan(4);
    });

    test('a drag too small to be meant settles back', async ({ page }) => {
        await gotoStable(page, '/');
        await waitForAlpine(page);

        // Under the commit threshold: a twitch is not a gesture.
        await drag(page, heroRail(page), 12);

        await expect(page.getByRole('tab', { name: 'Go to slide 1' }))
            .toHaveAttribute('aria-selected', 'true');
    });

    test('dragging the hero back returns to the previous slide', async ({ page }) => {
        await gotoStable(page, '/');
        await waitForAlpine(page);

        const rail = heroRail(page);

        await drag(page, rail, 40);
        await expect(page.getByRole('tab', { name: 'Go to slide 2' })).toHaveAttribute('aria-selected', 'true');

        await drag(page, rail, -40);

        await expect(page.getByRole('tab', { name: 'Go to slide 1' }))
            .toHaveAttribute('aria-selected', 'true');
    });

    test('a catalog rail keeps the distance dragged, not whole cards', async ({ page }) => {
        await gotoStable(page, '/');
        await waitForAlpine(page);

        const rail = railUnder(page, 'Exclusive Offers');

        await drag(page, rail, 250);

        // Free scrolling is the point of a rail of cards: it settles near
        // where it was dragged rather than jumping a fixed step.
        await expect.poll(() => scrollLeft(rail)).toBeGreaterThan(100);
    });

    test('dragging off a product card does not open it', async ({ page }) => {
        await gotoStable(page, '/');
        await waitForAlpine(page);

        const rail = railUnder(page, 'Exclusive Offers');
        const card = rail.locator('a').first();

        await drag(page, card, 300);

        await expect(page).toHaveURL(/\/$/);
    });

    test('a plain click on a product card still opens it', async ({ page }) => {
        await gotoStable(page, '/');
        await waitForAlpine(page);

        const card = railUnder(page, 'Exclusive Offers').locator('a').first();
        const href = await card.getAttribute('href');

        await card.click();

        await page.waitForURL(href);
    });
});
