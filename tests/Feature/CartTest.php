<?php

/**
 * The cart: grouping, per-line selection, quantities, and the promise that a
 * cart built by the previous release still checks out after a deploy.
 */

use App\Models\GiftCard;
use App\Services\Cart;
use Illuminate\Support\Facades\Session;

describe('display', function () {
    it('groups lines under the product they belong to', function () {
        $product = sellableProduct(giftCardsSectionModel(), ['name' => 'Steam Wallet', 'slug' => 'steam-wallet']);
        $ten     = GiftCard::where('slug', 'steam-wallet-10')->first();
        $twenty  = seoCard($product, ['name' => 'Steam Wallet $20', 'slug' => 'steam-20', 'price_bdt' => 2450], codes: 2);

        $this->post(route('cart.add'), ['gift_card_id' => $ten->id, 'quantity' => 1]);
        $this->post(route('cart.add'), ['gift_card_id' => $twenty->id, 'quantity' => 1]);

        $this->get(route('cart'))
            ->assertSuccessful()
            ->assertSee('Steam Wallet')
            ->assertSee('Steam Wallet $10')
            ->assertSee('Steam Wallet $20');
    });

    it('counts the selected lines', function () {
        $card = seoCard(seoProduct(seoBrand()), [], 5);

        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 2]);

        $this->get(route('cart'))
            ->assertSuccessful()
            ->assertSee('item(s) selected');
    });

    it('shows the per-line discount against a struck price', function () {
        $card = seoCard(seoProduct(seoBrand()), ['price_bdt' => 699, 'compare_at_price_bdt' => 720], 3);

        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 1]);

        $this->get(route('cart'))
            ->assertSuccessful()
            ->assertSee('Discount: 3%')
            ->assertSee('৳ 720', false);
    });

    it('offers a way back to shopping when it is empty', function () {
        $this->get(route('cart'))
            ->assertSuccessful()
            ->assertSee('Your cart is empty')
            ->assertSee(route('home'), false);
    });

    it('is not indexed', function () {
        $this->get(route('cart'))->assertSee('name="robots" content="noindex', false);
    });
});

describe('selection', function () {
    it('selects a newly added line by default', function () {
        $card = seoCard(seoProduct(seoBrand()), [], 3);

        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 1]);

        expect(app(Cart::class)->selectedCount())->toBe(1);
    });

    it('unticks a line without removing it', function () {
        $card = seoCard(seoProduct(seoBrand()), [], 3);
        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 1]);

        $this->postJson(route('cart.update-selection'), ['key' => (string) $card->id, 'selected' => false])
            ->assertSuccessful()
            ->assertJsonPath('selected', 0);

        expect(app(Cart::class)->resolve())->toHaveCount(1)
            ->and(app(Cart::class)->selectedCount())->toBe(0);
    });

    it('totals only the selected lines', function () {
        $a = seoCard(seoProduct(seoBrand()), ['slug' => 'a', 'price_bdt' => 1000], 3);
        $b = seoCard(seoProduct(seoBrand(['slug' => 'b-brand', 'name' => 'B']), ['slug' => 'b-product', 'name' => 'B']), ['slug' => 'b', 'price_bdt' => 500], 3);

        $this->post(route('cart.add'), ['gift_card_id' => $a->id, 'quantity' => 1]);
        $this->post(route('cart.add'), ['gift_card_id' => $b->id, 'quantity' => 1]);

        expect(app(Cart::class)->subtotal())->toBe(1500.0);

        $this->postJson(route('cart.update-selection'), ['key' => (string) $b->id, 'selected' => false]);

        expect(app(Cart::class)->subtotal())->toBe(1000.0);
    });

    it('checks out only the selected lines', function () {
        $a = seoCard(seoProduct(seoBrand()), ['slug' => 'a', 'price_bdt' => 1000], 3);
        $b = seoCard(seoProduct(seoBrand(['slug' => 'b-brand', 'name' => 'B']), ['slug' => 'b-product', 'name' => 'B']), ['slug' => 'b'], 3);

        $this->post(route('cart.add'), ['gift_card_id' => $a->id, 'quantity' => 1]);
        $this->post(route('cart.add'), ['gift_card_id' => $b->id, 'quantity' => 1]);
        $this->postJson(route('cart.update-selection'), ['key' => (string) $b->id, 'selected' => false]);

        $items = app(Cart::class)->checkoutItems();

        expect($items)->toHaveCount(1)
            ->and($items[0]['gift_card_id'])->toBe($a->id);
    });

    it('refuses to check out with nothing selected', function () {
        $card = seoCard(seoProduct(seoBrand()), [], 3);
        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 1]);
        $this->postJson(route('cart.update-selection'), ['key' => (string) $card->id, 'selected' => false]);

        expect(app(Cart::class)->checkoutItems())->toBeNull();
    });

    it('404s when asked to select a line that is not in the cart', function () {
        $this->postJson(route('cart.update-selection'), ['key' => '999', 'selected' => true])
            ->assertNotFound();
    });
});

describe('quantities', function () {
    it('updates a line and reports success', function () {
        $card = seoCard(seoProduct(seoBrand()), [], 5);
        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 1]);

        $this->postJson(route('cart.update-quantity'), ['gift_card_id' => $card->id, 'quantity' => 3])
            ->assertSuccessful()
            ->assertJson(['ok' => true]);

        expect(session('cart')[$card->id]['quantity'])->toBe(3);
    });

    it('refuses to go past the card\'s own maximum', function () {
        $card = seoCard(seoProduct(seoBrand()), ['max_quantity' => 2], 10);
        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 1]);

        $this->postJson(route('cart.update-quantity'), ['gift_card_id' => $card->id, 'quantity' => 3])
            ->assertStatus(422);
    });

    it('refuses to go past the stock actually available', function () {
        $card = seoCard(seoProduct(seoBrand()), [], 2);
        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 1]);

        $this->postJson(route('cart.update-quantity'), ['gift_card_id' => $card->id, 'quantity' => 5])
            ->assertStatus(422)
            ->assertJson(['error' => 'Only 2 available in stock.']);
    });
});

describe('removal', function () {
    it('removes one line and keeps the others', function () {
        $a = seoCard(seoProduct(seoBrand()), ['slug' => 'a'], 3);
        $b = seoCard(seoProduct(seoBrand(['slug' => 'b-brand', 'name' => 'B']), ['slug' => 'b-product', 'name' => 'B']), ['slug' => 'b'], 3);

        $this->post(route('cart.add'), ['gift_card_id' => $a->id, 'quantity' => 1]);
        $this->post(route('cart.add'), ['gift_card_id' => $b->id, 'quantity' => 1]);

        $this->delete(route('cart.remove', $a->id))->assertRedirect();

        expect(session('cart'))->not->toHaveKey($a->id)
            ->and(session('cart'))->toHaveKey($b->id);
    });
});

describe('carts that outlive a deploy', function () {
    it('reads a line written before selection existed', function () {
        // SESSION_DRIVER=database, so a cart built by the previous release is
        // handed to this one. It must behave exactly as it did.
        $card = seoCard(seoProduct(seoBrand()), [], 3);

        Session::put('cart', [
            $card->id => ['gift_card_id' => $card->id, 'quantity' => 2, 'price' => $card->price_bdt],
        ]);

        $line = app(Cart::class)->resolve()->first();

        expect($line['selected'])->toBeTrue()
            ->and($line['quantity'])->toBe(2)
            ->and($line['buyer_inputs'])->toBe([]);
    });

    it('checks out a pre-deploy cart unchanged', function () {
        $card = seoCard(seoProduct(seoBrand()), ['price_bdt' => 1000], 5);

        Session::put('cart', [
            $card->id => ['gift_card_id' => $card->id, 'quantity' => 2, 'price' => $card->price_bdt],
        ]);

        $items = app(Cart::class)->checkoutItems();

        expect($items)->toHaveCount(1)
            ->and($items[0]['quantity'])->toBe(2)
            ->and(app(Cart::class)->subtotal())->toBe(2000.0);
    });

    it('drops one dead line instead of voiding the whole cart', function () {
        $live = seoCard(seoProduct(seoBrand()), ['slug' => 'live'], 3);
        $dead = seoCard(seoProduct(seoBrand(['slug' => 'dead-brand', 'name' => 'Dead']), ['slug' => 'dead-product', 'name' => 'Dead']), ['slug' => 'dead'], 3);

        Session::put('cart', [
            $live->id => ['gift_card_id' => $live->id, 'quantity' => 1, 'price' => $live->price_bdt],
            $dead->id => ['gift_card_id' => $dead->id, 'quantity' => 1, 'price' => $dead->price_bdt],
        ]);

        $dead->update(['is_active' => false]);

        $lines = app(Cart::class)->resolve();

        expect($lines)->toHaveCount(1)
            ->and($lines->first()['gift_card_id'])->toBe($live->id);
    });

    it('keeps a short line visible so the shopper can fix it', function () {
        $card = seoCard(seoProduct(seoBrand()), [], 5);

        Session::put('cart', [
            $card->id => ['gift_card_id' => $card->id, 'quantity' => 5, 'price' => $card->price_bdt],
        ]);

        // Stock drops below what the cart asks for.
        $card->codes()->limit(3)->update(['status' => 'sold']);

        $line = app(Cart::class)->resolve()->first();

        expect($line)->not->toBeNull()
            ->and($line['in_stock'])->toBeFalse()
            ->and(app(Cart::class)->checkoutItems())->toBeNull();

        $this->get(route('cart'))
            ->assertSuccessful()
            ->assertSee('lower the quantity to check out');
    });
});
