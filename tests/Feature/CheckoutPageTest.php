<?php

/**
 * The checkout page itself: which payment methods it offers, what it asks for,
 * and what it shows in the summary.
 *
 * What happens after Place Order is pinned by CheckoutCharacterisationTest;
 * this is about the screen.
 */

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Cart;

function checkoutWithCard(int $codes = 5, array $cardOverrides = []): \App\Models\GiftCard
{
    $card = seoCard(seoProduct(seoBrand()), $cardOverrides, $codes);

    test()->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 1]);

    return $card;
}

describe('access', function () {
    it('sends a guest to sign in', function () {
        $this->get(route('checkout'))->assertRedirect(route('login'));
    });

    it('sends a signed-in shopper with an empty cart back to the cart', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('checkout'))
            ->assertRedirect(route('cart'))
            ->assertSessionHas('error', 'Your cart is empty.');
    });

    it('sends a shopper back when nothing is selected', function () {
        $card = checkoutWithCard();
        $this->postJson(route('cart.update-selection'), ['key' => (string) $card->id, 'selected' => false]);

        $this->actingAs(User::factory()->create())
            ->get(route('checkout'))
            ->assertRedirect(route('cart'));
    });

    it('turns away an address that has never been confirmed', function () {
        // The gate on bot-placed orders: signing up is open to anyone, but a
        // send-money order reserves real stock the moment it is placed.
        checkoutWithCard();

        $this->actingAs(User::factory()->unverified()->create())
            ->get(route('checkout'))
            ->assertRedirect(route('verification.notice'));
    });

    it('lets a confirmed address straight through', function () {
        // Every account that existed before the gate shipped was back-filled
        // by 2026_10_30_141151, so this is what a current customer sees.
        checkoutWithCard();

        $this->actingAs(User::factory()->create())
            ->get(route('checkout'))
            ->assertSuccessful();
    });

    it('is not indexed', function () {
        checkoutWithCard();

        $this->actingAs(User::factory()->create())
            ->get(route('checkout'))
            ->assertSuccessful()
            ->assertSee('name="robots" content="noindex', false);
    });
});

describe('payment methods', function () {
    it('offers no chooser when only one method is enabled', function () {
        checkoutWithCard();

        $this->actingAs(User::factory()->create())
            ->get(route('checkout'))
            ->assertSuccessful()
            ->assertDontSee('Payment method</legend>', false)
            ->assertSee('Pay with bKash');
    });

    it('offers every enabled method with its own instructions', function () {
        SiteSetting::set('payment_bkash_send_money_enabled', '1', 'payment');
        SiteSetting::set('payment_nagad_send_money_enabled', '1', 'payment');
        checkoutWithCard();

        $this->actingAs(User::factory()->create())
            ->get(route('checkout'))
            ->assertSuccessful()
            ->assertSee('bKash Online')
            ->assertSee('bKash Send Money')
            ->assertSee('Nagad Send Money')
            ->assertSee('*247#')
            ->assertSee('*167#')
            ->assertSee('name="send_money_trx_id"', false);
    });

    it('never offers a method that is switched off', function () {
        SiteSetting::set('payment_rocket_send_money_enabled', '0', 'payment');
        checkoutWithCard();

        $this->actingAs(User::factory()->create())
            ->get(route('checkout'))
            ->assertSuccessful()
            ->assertDontSee('*322#');
    });

    it('rejects a send-money order paid by a disabled method', function () {
        checkoutWithCard();

        $this->actingAs(User::factory()->create())
            ->post(route('checkout.manual'), [
                'name'              => 'Buyer',
                'email'             => 'buyer@example.com',
                'payment_method'    => 'nagad_send_money',
                'send_money_trx_id' => 'TRX1',
            ])
            ->assertSessionHasErrors('payment_method');
    });

    it('closes the bKash online route while that method is switched off', function () {
        // The method is picked in the browser, so hiding the button is not
        // enough: a direct POST used to open a payment session, reserve codes
        // and leave the order stranded in `payment_initiated` for good.
        SiteSetting::set('payment_bkash_online_enabled', '0', 'payment');
        SiteSetting::set('payment_nagad_send_money_enabled', '1', 'payment');
        checkoutWithCard();

        $this->actingAs(User::factory()->create())
            ->post(route('checkout.initiate'), [
                'name'  => 'Buyer',
                'email' => 'buyer@example.com',
            ])
            ->assertNotFound();

        expect(\App\Models\Order::count())->toBe(0);
    });

    it('keeps the bKash online route open when nothing else is enabled', function () {
        // enabledPaymentMethods() falls back to bkash_online so a store with
        // every switch off can still take money. The guard must respect that.
        SiteSetting::set('payment_bkash_online_enabled', '0', 'payment');
        checkoutWithCard();

        $this->actingAs(User::factory()->create())
            ->post(route('checkout.initiate'), ['name' => 'Buyer', 'email' => 'buyer@example.com'])
            ->assertStatus(302);
    });
});

describe('what the rest of the site promises', function () {
    // The sign-in panel promised "Secured by bKash Tokenized Checkout" and the
    // FAQ listed tokenized checkout and Rocket, all written in by hand -- so
    // they went on saying it after those methods were switched off. Both are
    // built from PaymentMethods::enabled() now.
    it('names only the methods that are switched on', function () {
        SiteSetting::set('payment_bkash_online_enabled', '0', 'payment');
        SiteSetting::set('payment_bkash_send_money_enabled', '1', 'payment');
        SiteSetting::set('payment_nagad_send_money_enabled', '1', 'payment');
        SiteSetting::set('payment_rocket_send_money_enabled', '0', 'payment');

        $this->get(route('faq'))
            ->assertSuccessful()
            ->assertSee('bKash Send Money')
            ->assertSee('Nagad Send Money')
            ->assertDontSee('Rocket')
            ->assertDontSee('Tokenized');

        $this->get(route('login'))
            ->assertSuccessful()
            ->assertSee('Pay with bKash or Nagad')
            ->assertDontSee('Tokenized');
    });

    it('names tokenized checkout again once it is switched back on', function () {
        SiteSetting::set('payment_bkash_online_enabled', '1', 'payment');

        $this->get(route('faq'))
            ->assertSuccessful()
            ->assertSee('bKash Online');
    });
});

describe('the summary', function () {
    it('lists each line with its quantity and total', function () {
        checkoutWithCard(cardOverrides: ['price_bdt' => 1250]);

        $this->actingAs(User::factory()->create())
            ->get(route('checkout'))
            ->assertSuccessful()
            ->assertSee('Steam Wallet $10')
            ->assertSee('Qty: 1')
            ->assertSee('৳ 1,250', false);
    });

    it('shows the buyer inputs a line carries', function () {
        $product = seoProduct(seoBrand(), ['buyer_input_fields' => [['key' => 'player_id', 'label' => 'Player ID']]]);
        $card    = seoCard($product, [], 3);

        $this->post(route('cart.add'), [
            'gift_card_id' => $card->id,
            'quantity'     => 1,
            'buyer_inputs' => ['player_id' => '5123456789'],
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('checkout'))
            ->assertSuccessful()
            ->assertSee('Player Id: 5123456789');
    });

    it('only totals what the shopper actually selected', function () {
        $keep = seoCard(seoProduct(seoBrand()), ['name' => 'Kept Card', 'slug' => 'keep', 'price_bdt' => 1000], 3);
        $drop = seoCard(
            seoProduct(seoBrand(['slug' => 'b', 'name' => 'B']), ['slug' => 'b-product', 'name' => 'B']),
            ['name' => 'Unticked Card', 'slug' => 'drop', 'price_bdt' => 500],
            3,
        );

        $this->post(route('cart.add'), ['gift_card_id' => $keep->id, 'quantity' => 1]);
        $this->post(route('cart.add'), ['gift_card_id' => $drop->id, 'quantity' => 1]);
        $this->postJson(route('cart.update-selection'), ['key' => (string) $drop->id, 'selected' => false]);

        $this->actingAs(User::factory()->create())
            ->get(route('checkout'))
            ->assertSuccessful()
            ->assertSee('Kept Card')
            ->assertSee('৳ 1,000', false)
            ->assertDontSee('Unticked Card');
    });
});

describe('discounts', function () {
    it('offers the referral field only while the programme is on', function () {
        checkoutWithCard();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('checkout'))->assertDontSee('Referral code');

        SiteSetting::set('referral_enabled', true, 'referral');

        $this->actingAs($user)
            ->get(route('checkout'))
            ->assertSuccessful()
            ->assertSee('Referral code')
            ->assertSee('name="referral_code"', false);
    });

    it('offers the wallet only to a shopper who has a balance', function () {
        checkoutWithCard();

        $this->actingAs(User::factory()->create(['wallet_balance' => 0]))
            ->get(route('checkout'))
            ->assertDontSee('Use wallet balance');

        $this->actingAs(User::factory()->create(['wallet_balance' => 250]))
            ->get(route('checkout'))
            ->assertSuccessful()
            ->assertSee('Use wallet balance')
            ->assertSee('৳ 250', false);
    });
});

describe('after the order', function () {
    it('keeps an unselected line in the cart when the rest is ordered', function () {
        SiteSetting::set('payment_bkash_send_money_enabled', '1', 'payment');

        $ordered = seoCard(seoProduct(seoBrand()), ['slug' => 'ordered'], 3);
        $kept    = seoCard(seoProduct(seoBrand(['slug' => 'b', 'name' => 'B']), ['slug' => 'b-product', 'name' => 'B']), ['slug' => 'kept'], 3);

        $this->post(route('cart.add'), ['gift_card_id' => $ordered->id, 'quantity' => 1]);
        $this->post(route('cart.add'), ['gift_card_id' => $kept->id, 'quantity' => 1]);
        $this->postJson(route('cart.update-selection'), ['key' => (string) $kept->id, 'selected' => false]);

        $this->actingAs(User::factory()->create())
            ->post(route('checkout.manual'), [
                'name'              => 'Buyer',
                'email'             => 'buyer@example.com',
                'payment_method'    => 'bkash_send_money',
                'send_money_trx_id' => 'TRX-KEEP',
            ])
            ->assertRedirectContains('/checkout/pending/');

        $remaining = app(Cart::class)->resolve();

        expect($remaining)->toHaveCount(1)
            ->and($remaining->first()['gift_card_id'])->toBe($kept->id);
    });
});
