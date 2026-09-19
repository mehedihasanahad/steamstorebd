<?php

/**
 * The product page: denomination tiles, the region switcher, purchase limits,
 * content tabs, ratings and the favourites control.
 */

use App\Models\Review;
use App\Models\SiteSetting;
use App\Models\User;

/**
 * The denomination payload the purchase panel is booted with.
 *
 * Decoded rather than string-matched: the panel is handed JSON, and asserting
 * on the JSON says what the test means instead of pinning today's escaping.
 *
 * @return array<int, array<string, mixed>>
 */
function productPanelCards(string $html): array
{
    expect($html)->toMatch("/productPage\(JSON\.parse\('/");

    preg_match("/productPage\(JSON\.parse\('(.+?)'\)/s", $html, $matches);

    // Blade's @js emits a JavaScript string literal whose quotes are \u escaped.
    // Decoding it as a JSON string first turns those back into real quotes;
    // what is left is the JSON the browser would parse.
    $json = json_decode('"' . $matches[1] . '"');

    return json_decode($json, true) ?? [];
}

describe('denomination tiles', function () {
    it('lists every active denomination with its price', function () {
        $product = sellableProduct();
        seoCard($product, ['name' => 'Steam Wallet $20', 'slug' => 'steam-20', 'price_bdt' => 2450], codes: 2);

        $this->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertSee('Steam Wallet $10')
            ->assertSee('Steam Wallet $20')
            ->assertSee('৳ 2,450', false);
    });

    it('keeps an out-of-stock denomination visible but badged', function () {
        // Hiding it is what makes a product page look empty the moment one
        // popular value sells out.
        $product = sellableProduct(codes: 0);

        $this->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertSee('Steam Wallet $10')
            ->assertSee('Stock out');
    });

    it('leaves out a denomination the admin switched off', function () {
        $product = sellableProduct();
        seoCard($product, ['name' => 'Retired 50', 'slug' => 'retired-50', 'is_active' => false]);

        $this->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertDontSee('Retired 50');
    });

    it('says so when a product has nothing on sale', function () {
        $brand = sectionBrand(giftCardsSectionModel());
        seoProduct($brand, ['name' => 'Coming Soon', 'slug' => 'coming-soon']);

        $this->get(route('product', 'coming-soon'))
            ->assertSuccessful()
            ->assertSee('Currently unavailable');
    });
});

describe('purchase limits', function () {
    it('publishes each card\'s own min and max to the panel', function () {
        sellableProduct(cardOverrides: ['min_quantity' => 1, 'max_quantity' => 2], codes: 5);

        $response = $this->get(route('product', 'steam-wallet'))->assertSuccessful();
        $response->assertSee('Purchase limit');

        expect(productPanelCards($response->getContent())[0])
            ->toMatchArray(['min' => 1, 'max' => 2]);
    });

    it('never offers more than is actually in stock', function () {
        sellableProduct(cardOverrides: ['max_quantity' => 10], codes: 3);

        $html = $this->get(route('product', 'steam-wallet'))->assertSuccessful()->getContent();

        expect(productPanelCards($html)[0])->toMatchArray(['stock' => 3, 'max' => 3]);
    });

    it('offers nothing orderable on a card that is out of stock', function () {
        sellableProduct(codes: 0);

        $html = $this->get(route('product', 'steam-wallet'))->assertSuccessful()->getContent();

        expect(productPanelCards($html)[0]['max'])->toBe(0);
    });
});

describe('delivery promise', function () {
    it('promises instant delivery for a stocked code-pool card', function () {
        sellableProduct(codes: 2);

        $this->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertSee('Instant delivery');
    });

    it('shows the card\'s own eta when it has one', function () {
        $product = sellableProduct(codes: 0);
        manualCard($product, 4, ['delivery_eta_label' => '5-30 minutes']);

        $this->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertSee('5-30 minutes');
    });

    it('says restocking when nothing is in stock', function () {
        sellableProduct(codes: 0);

        $this->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertSee('Restocking');
    });
});

describe('region switcher', function () {
    it('offers the same product in its other regions', function () {
        $brand = sectionBrand(giftCardsSectionModel());

        seoCard(seoProduct($brand, ['name' => 'Steam HKD', 'slug' => 'steam-hkd', 'region' => 'HK', 'region_group' => 'steam-wallet']), ['slug' => 'hkd-40']);
        seoCard(seoProduct($brand, ['name' => 'Steam USA', 'slug' => 'steam-usa', 'region' => 'US', 'region_group' => 'steam-wallet']), ['slug' => 'usa-10']);

        $this->get(route('product', 'steam-hkd'))
            ->assertSuccessful()
            ->assertSee('Hong Kong')
            ->assertSee('United States')
            ->assertSee(route('product', 'steam-usa'), false);
    });

    it('shows a plain region chip when the product has no siblings', function () {
        sellableProduct(productOverrides: ['region' => 'BD']);

        $this->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertSee('Bangladesh');
    });

    it('offers no switcher at all when the product declares no region group', function () {
        sellableProduct();

        $this->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertDontSee('Choose region');
    });

    it('never offers a switched-off sibling', function () {
        $brand = sectionBrand(giftCardsSectionModel());

        seoCard(seoProduct($brand, ['name' => 'Steam HKD', 'slug' => 'steam-hkd', 'region' => 'HK', 'region_group' => 'steam-wallet']), ['slug' => 'hkd-40']);
        seoProduct($brand, ['name' => 'Steam Turkey', 'slug' => 'steam-tr', 'region' => 'TR', 'region_group' => 'steam-wallet', 'is_active' => false]);

        $this->get(route('product', 'steam-hkd'))
            ->assertSuccessful()
            ->assertDontSee(route('product', 'steam-tr'), false);
    });
});

describe('content tabs', function () {
    it('shows only the tabs that have content', function () {
        sellableProduct(productOverrides: ['long_description' => '<p>All about this card.</p>']);

        $this->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertSee('Description')
            ->assertSee('All about this card.', false)
            // The footer links to the site FAQ, so the tab is identified by
            // the panel it controls rather than by its label.
            ->assertDontSee('tab-faq', false);
    });

    it('falls back to the brand\'s redemption steps for the instructions tab', function () {
        $brand = sectionBrand(giftCardsSectionModel(), ['how_to_redeem' => '<p>Redeem on Steam.</p>']);
        seoCard(seoProduct($brand), ['slug' => 'steam-10']);

        $this->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertSee('Instructions')
            ->assertSee('Redeem on Steam.', false);
    });

    it('prefers the product\'s own instructions over the brand\'s', function () {
        $brand = sectionBrand(giftCardsSectionModel(), ['how_to_redeem' => '<p>Brand steps.</p>']);
        seoCard(seoProduct($brand, ['instructions' => '<p>Product steps.</p>']), ['slug' => 'steam-10']);

        $this->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertSee('Product steps.', false)
            ->assertDontSee('Brand steps.', false);
    });

    it('renders the FAQ tab and its schema together', function () {
        sellableProduct(productOverrides: ['faq' => [
            ['question' => 'Does it work in Bangladesh?', 'answer' => 'Yes, on a BD account.'],
        ]]);

        $this->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertSee('Does it work in Bangladesh?')
            ->assertSee('"@type":"FAQPage"', false);
    });
});

describe('ratings', function () {
    it('hides the rating block until a product has been rated', function () {
        sellableProduct();

        $this->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertDontSee('out of 5');
    });

    it('shows the mean of approved ratings and the count', function () {
        $product = sellableProduct();

        Review::create(['gift_card_category_id' => $product->id, 'rating' => 5, 'comment' => 'Great', 'status' => 'approved']);
        Review::create(['gift_card_category_id' => $product->id, 'rating' => 4, 'comment' => 'Good', 'status' => 'approved']);
        Review::create(['gift_card_category_id' => $product->id, 'rating' => 1, 'comment' => 'Pending', 'status' => 'pending']);

        $this->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertSee('4.5')
            ->assertSee('(2)');
    });

    it('puts the rating into the product schema when it has one', function () {
        $product = sellableProduct();
        Review::create(['gift_card_category_id' => $product->id, 'rating' => 5, 'comment' => 'Great', 'status' => 'approved']);

        $this->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertSee('"@type":"AggregateRating"', false);
    });

    it('leaves aggregateRating out of the schema on an unrated product', function () {
        sellableProduct();

        $this->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertDontSee('AggregateRating', false);
    });
});

describe('favourites control', function () {
    it('asks a guest to sign in', function () {
        sellableProduct();

        $this->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertSee('Add to favourite')
            ->assertSee(route('login'), false);
    });

    it('offers a signed-in shopper the toggle', function () {
        $product = sellableProduct();

        $this->actingAs(User::factory()->create())
            ->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertSee('Add to favourite')
            ->assertSee(route('favourites.toggle', $product), false);
    });

    it('says saved once the product is a favourite', function () {
        $product = sellableProduct();
        $user    = User::factory()->create();
        $user->favourites()->create(['gift_card_category_id' => $product->id]);

        $this->actingAs($user)
            ->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertSee('Saved');
    });
});

describe('the buy panel', function () {
    it('posts to the cart with the selected card and quantity', function () {
        sellableProduct();

        $this->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertSee(route('cart.add'), false)
            ->assertSee('name="gift_card_id"', false)
            ->assertSee('name="quantity"', false)
            ->assertSee('name="redirect_to"', false);
    });

    it('renders no buy form at all when there is nothing to sell', function () {
        seoProduct(sectionBrand(giftCardsSectionModel()), ['name' => 'Empty', 'slug' => 'empty-product']);

        $this->get(route('product', 'empty-product'))
            ->assertSuccessful()
            ->assertDontSee('name="gift_card_id"', false);
    });

    it('keeps the chat buttons even when the product is out of stock', function () {
        SiteSetting::set('product_chat_whatsapp_enabled', '1', 'product_chat');
        SiteSetting::set('product_chat_whatsapp_number', '8801711223344', 'product_chat');

        sellableProduct(codes: 0);

        $this->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertSee('Order on WhatsApp');
    });

    it('asks for buyer input when the product declares a schema', function () {
        $product = sellableProduct(productOverrides: ['buyer_input_fields' => [
            ['key' => 'player_id', 'label' => 'Player ID', 'required' => true, 'help' => 'Find it in-game.'],
        ]]);

        $this->get(route('product', $product->slug))
            ->assertSuccessful()
            ->assertSee('Enter your account details')
            ->assertSee('Player ID')
            ->assertSee('Find it in-game.')
            ->assertSee('name="buyer_inputs[player_id]"', false);
    });

    it('asks for nothing extra on an ordinary gift card', function () {
        sellableProduct();

        $this->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertDontSee('Enter your account details');
    });
});

describe('related products', function () {
    it('links the brand\'s other products with their lowest price', function () {
        $brand = sectionBrand(giftCardsSectionModel());
        seoCard(seoProduct($brand), ['slug' => 'steam-10']);
        seoCard(seoProduct($brand, ['name' => 'Steam Wallet TL', 'slug' => 'steam-tl']), ['slug' => 'steam-tl-100', 'price_bdt' => 900]);

        $this->get(route('product', 'steam-wallet'))
            ->assertSuccessful()
            ->assertSee('More from Steam')
            ->assertSee(route('product', 'steam-tl'), false)
            ->assertSee('from ৳ 900', false);
    });

    it('shows no related panel for an unbranded product', function () {
        seoCard(seoProduct(null, ['name' => 'Lonely', 'slug' => 'lonely']), ['slug' => 'lonely-10']);

        $this->get(route('product', 'lonely'))
            ->assertSuccessful()
            ->assertDontSee('More from');
    });
});
