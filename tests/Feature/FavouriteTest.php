<?php

/**
 * Favourites: a signed-in shopper's saved products.
 */

use App\Models\Favourite;
use App\Models\User;

describe('access', function () {
    it('sends a guest to sign in', function () {
        $product = sellableProduct();

        $this->get(route('favourites'))->assertRedirect(route('login'));
        $this->post(route('favourites.toggle', $product))->assertRedirect(route('login'));
    });

    it('is not indexed', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('favourites'))
            ->assertSuccessful()
            ->assertSee('name="robots" content="noindex', false);
    });
});

describe('toggling', function () {
    it('saves a product', function () {
        $product = sellableProduct();
        $user    = User::factory()->create();

        $this->actingAs($user)
            ->from(route('product', $product->slug))
            ->post(route('favourites.toggle', $product))
            ->assertRedirect(route('product', $product->slug))
            ->assertSessionHas('success', 'Saved to favourites.');

        expect($user->hasFavourited($product->id))->toBeTrue();
    });

    it('removes a product that was already saved', function () {
        $product = sellableProduct();
        $user    = User::factory()->create();
        Favourite::create(['user_id' => $user->id, 'gift_card_category_id' => $product->id]);

        $this->actingAs($user)
            ->post(route('favourites.toggle', $product))
            ->assertSessionHas('success', 'Removed from favourites.');

        expect($user->hasFavourited($product->id))->toBeFalse();
    });

    it('never stores the same product twice for one shopper', function () {
        $product = sellableProduct();
        $user    = User::factory()->create();

        $this->actingAs($user)->post(route('favourites.toggle', $product));
        $this->actingAs($user)->post(route('favourites.toggle', $product));
        $this->actingAs($user)->post(route('favourites.toggle', $product));

        expect(Favourite::where('user_id', $user->id)->count())->toBe(1);
    });

    it('keeps two shoppers\' lists apart', function () {
        $product = sellableProduct();
        $mine    = User::factory()->create();
        $theirs  = User::factory()->create();

        $this->actingAs($mine)->post(route('favourites.toggle', $product));

        expect($mine->hasFavourited($product->id))->toBeTrue()
            ->and($theirs->hasFavourited($product->id))->toBeFalse();
    });

    it('404s on a product that does not exist', function () {
        $this->actingAs(User::factory()->create())
            ->post('/favourites/9999')
            ->assertNotFound();
    });
});

describe('the list', function () {
    it('shows the saved products with a link to each', function () {
        $product = sellableProduct(giftCardsSectionModel(), ['name' => 'Steam Wallet', 'slug' => 'steam-wallet']);
        $user    = User::factory()->create();
        Favourite::create(['user_id' => $user->id, 'gift_card_category_id' => $product->id]);

        $this->actingAs($user)
            ->get(route('favourites'))
            ->assertSuccessful()
            ->assertSee('Steam Wallet')
            ->assertSee(route('product', 'steam-wallet'), false);
    });

    it('leaves out a product that has since been switched off', function () {
        $product = sellableProduct();
        $user    = User::factory()->create();
        Favourite::create(['user_id' => $user->id, 'gift_card_category_id' => $product->id]);

        $product->update(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('favourites'))
            ->assertSuccessful()
            ->assertDontSee(route('product', $product->slug), false);
    });

    it('says so plainly when nothing is saved', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('favourites'))
            ->assertSuccessful()
            ->assertSee('Nothing saved yet');
    });

    it('goes away with the shopper\'s account', function () {
        $product = sellableProduct();
        $user    = User::factory()->create();
        Favourite::create(['user_id' => $user->id, 'gift_card_category_id' => $product->id]);

        $user->delete();

        expect(Favourite::count())->toBe(0);
    });

    it('goes away with the product', function () {
        $product = sellableProduct();
        $user    = User::factory()->create();
        Favourite::create(['user_id' => $user->id, 'gift_card_category_id' => $product->id]);

        $product->delete();

        expect(Favourite::count())->toBe(0);
    });
});
