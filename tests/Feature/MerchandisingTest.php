<?php

use App\Models\GiftCard;
use App\Models\GiftCardCategory;

describe('deals', function () {
    it('is not a deal without a compare-at price', function () {
        $card = seoCard(seoProduct(seoBrand()), ['price_bdt' => 1000]);

        expect($card->isDeal())->toBeFalse()
            ->and($card->discountPercent())->toBeNull()
            ->and($card->discountAmount())->toBeNull();
    });

    it('is a deal when the compare-at price is higher', function () {
        $card = seoCard(seoProduct(seoBrand()), [
            'price_bdt'            => 699,
            'compare_at_price_bdt' => 720,
        ]);

        expect($card->isDeal())->toBeTrue()
            ->and($card->discountAmount())->toBe(21.0)
            ->and($card->discountPercent())->toBe(3);
    });

    it('is not a deal when the compare-at price is equal or lower', function () {
        $equal = seoCard(seoProduct(seoBrand()), [
            'slug' => 'equal', 'price_bdt' => 1000, 'compare_at_price_bdt' => 1000,
        ]);

        expect($equal->isDeal())->toBeFalse();
    });

    it('rounds the discount percentage to a whole number', function () {
        $card = seoCard(seoProduct(seoBrand()), [
            'price_bdt' => 750, 'compare_at_price_bdt' => 800,
        ]);

        expect($card->discountPercent())->toBe(6);
    });

    it('finds deals through the scope and leaves plain cards out', function () {
        $brand = seoBrand();
        seoCard(seoProduct($brand, ['slug' => 'p1']), [
            'slug' => 'deal-card', 'price_bdt' => 699, 'compare_at_price_bdt' => 720,
        ]);
        seoCard(seoProduct($brand, ['slug' => 'p2']), ['slug' => 'plain-card', 'price_bdt' => 500]);
        seoCard(seoProduct($brand, ['slug' => 'p3']), [
            'slug' => 'not-a-deal', 'price_bdt' => 500, 'compare_at_price_bdt' => 400,
        ]);

        expect(GiftCard::deals()->pluck('slug')->all())->toBe(['deal-card']);
    });
});

describe('purchase limits', function () {
    it('defaults to the cap the checkout used to hardcode', function () {
        $card = seoCard(seoProduct(seoBrand()));

        expect($card->fresh()->min_quantity)->toBe(1)
            ->and($card->fresh()->max_quantity)->toBe(10);
    });

    it('still refuses eleven on a default card', function () {
        $card = seoCard(seoProduct(seoBrand()), [], 20);

        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 11])
            ->assertSessionHasErrors('quantity');
    });

    it('accepts ten on a default card', function () {
        $card = seoCard(seoProduct(seoBrand()), [], 20);

        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 10])
            ->assertSessionHasNoErrors();
    });

    it('enforces a tighter per-card maximum', function () {
        $card = seoCard(seoProduct(seoBrand()), ['max_quantity' => 2], 20);

        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 3])
            ->assertSessionHasErrors('quantity');
    });

    it('allows a card to be sold in larger batches', function () {
        $card = seoCard(seoProduct(seoBrand()), ['max_quantity' => 50], 60);

        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 25])
            ->assertSessionHasNoErrors();
    });

    it('enforces a minimum above one', function () {
        $card = seoCard(seoProduct(seoBrand()), ['min_quantity' => 2], 20);

        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 1])
            ->assertSessionHasErrors('quantity');
    });

    it('caps the orderable quantity at what is actually in stock', function () {
        $card = seoCard(seoProduct(seoBrand()), ['max_quantity' => 10], 3);

        expect($card->fresh()->maxOrderableQuantity())->toBe(3);
    });

    it('applies the same limits when updating a cart line', function () {
        $card = seoCard(seoProduct(seoBrand()), ['max_quantity' => 2], 20);
        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 2]);

        $this->postJson(route('cart.update-quantity'), ['gift_card_id' => $card->id, 'quantity' => 5])
            ->assertStatus(422);
    });
});

describe('featured products', function () {
    it('lists only featured active products, in order', function () {
        $brand = seoBrand();
        seoProduct($brand, ['slug' => 'plain']);
        seoProduct($brand, ['slug' => 'second', 'is_featured' => true, 'featured_sort' => 2]);
        seoProduct($brand, ['slug' => 'first', 'is_featured' => true, 'featured_sort' => 1]);
        seoProduct($brand, ['slug' => 'hidden', 'is_featured' => true, 'is_active' => false]);

        expect(GiftCardCategory::featured()->pluck('slug')->all())->toBe(['first', 'second']);
    });

    it('features nothing by default', function () {
        seoProduct(seoBrand());

        expect(GiftCardCategory::featured()->count())->toBe(0);
    });
});
