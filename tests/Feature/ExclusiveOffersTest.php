<?php

/**
 * The Exclusive Offers programme: the homepage slider under the hero, and the
 * /offers page its View all button opens.
 *
 * What counts as an offer is asserted in MerchandisingTest, on the model. What
 * is asserted here is the part a shopper meets: that the deepest discount
 * leads, that a section filter narrows the list without hiding the rest, and
 * that switching the programme off closes both surfaces.
 */

use App\Models\GiftCard;
use App\Models\SiteSetting;
use App\Services\ExclusiveOffers;

/** A discounted card on its own product, in the given section. */
function offerCard(?App\Models\CatalogSection $section, string $slug, float $price, float $was, array $overrides = []): GiftCard
{
    $product = sellableProduct($section, [
        'name'        => 'Product ' . $slug,
        'slug'        => 'product-' . $slug,
        'brand_name'  => 'Brand ' . $slug,
        'brand_slug'  => 'brand-' . $slug,
    ], ['slug' => $slug . '-base', 'price_bdt' => 5000], codes: 1);

    return seoCard($product, array_merge([
        'name'                 => 'Offer ' . $slug,
        'slug'                 => $slug,
        'price_bdt'            => $price,
        'compare_at_price_bdt' => $was,
    ], $overrides), codes: 2);
}

describe('the homepage slider', function () {
    it('shows discounted cards under the default heading', function () {
        offerCard(null, 'steam-5', 699, 720);

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Exclusive Offers')
            ->assertSee('Offer steam-5')
            ->assertSee('3% off')
            ->assertSee('৳ 699', false)
            ->assertSee('৳ 720', false);
    });

    it('orders the cards by how deep the discount is, not by what it saves', function () {
        // 100tk off 1000 is 10%; 60tk off 200 is 30% and must lead.
        offerCard(null, 'shallow', 900, 1000);
        offerCard(null, 'deep', 140, 200);

        $html = $this->get(route('home'))->assertSuccessful()->getContent();

        expect(strpos($html, 'Offer deep'))->toBeLessThan(strpos($html, 'Offer shallow'));
    });

    it('links View all to the offers page', function () {
        offerCard(null, 'steam-5', 699, 720);

        $this->get(route('home'))->assertSee(route('offers'), false);
    });

    it('takes the heading and subheading from site settings', function () {
        SiteSetting::set('exclusive_offers_title', 'Eid Blowout', 'exclusive_offers');
        SiteSetting::set('exclusive_offers_subtitle', 'Three days only.', 'exclusive_offers');
        offerCard(null, 'steam-5', 699, 720);

        $this->get(route('home'))
            ->assertSee('Eid Blowout')
            ->assertSee('Three days only.')
            ->assertDontSee('Exclusive Offers');
    });

    it('hides the section while the programme is switched off', function () {
        SiteSetting::set('exclusive_offers_enabled', '0', 'exclusive_offers');
        offerCard(null, 'steam-5', 699, 720);

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertDontSee('Exclusive Offers')
            ->assertDontSee('Offer steam-5');
    });

    it('shows the section by default on a database with no setting row', function () {
        offerCard(null, 'steam-5', 699, 720);

        expect(SiteSetting::where('key', 'exclusive_offers_enabled')->exists())->toBeFalse();

        $this->get(route('home'))->assertSee('Exclusive Offers');
    });

    it('leaves the section out entirely when nothing is discounted', function () {
        sellableProduct();

        $this->get(route('home'))->assertDontSee('Exclusive Offers');
    });
});

describe('the offers page', function () {
    it('lists every offer, deepest discount first', function () {
        offerCard(null, 'shallow', 900, 1000);
        offerCard(null, 'deep', 140, 200);

        $html = $this->get(route('offers'))->assertSuccessful()
            ->assertSee('Offer deep')
            ->assertSee('Offer shallow')
            ->getContent();

        expect(strpos($html, 'Offer deep'))->toBeLessThan(strpos($html, 'Offer shallow'));
    });

    it('leaves out a card that is not discounted', function () {
        sellableProduct(null, ['name' => 'Full Price', 'slug' => 'full-price']);
        offerCard(null, 'steam-5', 699, 720);

        $this->get(route('offers'))->assertDontSee('Full Price');
    });

    it('leaves out an offer on a switched-off product', function () {
        $card = offerCard(null, 'ghost', 100, 200);
        $card->category->update(['is_active' => false]);

        $this->get(route('offers'))->assertDontSee('Offer ghost');
    });

    it('offers one filter per section that has an offer, and none for the rest', function () {
        offerCard(giftCardsSectionModel(), 'card-offer', 100, 200);
        storefrontSection(['name' => 'Subscriptions', 'slug' => 'subscriptions']);

        $this->get(route('offers'))
            ->assertSuccessful()
            ->assertSee('Gift Cards')
            ->assertDontSee('Subscriptions');
    });

    it('narrows the list to one section', function () {
        $topUps = storefrontSection();
        offerCard(giftCardsSectionModel(), 'card-offer', 100, 200);
        offerCard($topUps, 'top-up-offer', 300, 400);

        $this->get(route('offers', ['section' => $topUps->slug]))
            ->assertSuccessful()
            ->assertSee('Offer top-up-offer')
            ->assertDontSee('Offer card-offer');
    });

    it('shows everything by default', function () {
        $topUps = storefrontSection();
        offerCard(giftCardsSectionModel(), 'card-offer', 100, 200);
        offerCard($topUps, 'top-up-offer', 300, 400);

        $this->get(route('offers'))
            ->assertSee('Offer card-offer')
            ->assertSee('Offer top-up-offer');
    });

    it('falls back to every offer when the section slug is unknown', function () {
        offerCard(null, 'steam-5', 699, 720);

        $this->get(route('offers', ['section' => 'no-such-section']))
            ->assertSuccessful()
            ->assertSee('Offer steam-5');
    });

    it('says so, rather than showing an empty grid, when a section has nothing', function () {
        $topUps = storefrontSection();
        offerCard(giftCardsSectionModel(), 'card-offer', 100, 200);

        $this->get(route('offers', ['section' => $topUps->slug]))
            ->assertSuccessful()
            ->assertSee('No offers here right now');
    });


    it('paginates rather than printing every offer at once', function () {
        foreach (range(1, ExclusiveOffers::PER_PAGE + 2) as $i) {
            offerCard(null, 'offer-' . $i, 100 + $i, 500);
        }

        $total = ExclusiveOffers::PER_PAGE + 2;

        $this->get(route('offers'))
            ->assertSuccessful()
            ->assertSee("Showing 1\u{2013}" . ExclusiveOffers::PER_PAGE . ' of ' . $total, false);
    });

    it('is a 404 while the programme is switched off', function () {
        SiteSetting::set('exclusive_offers_enabled', '0', 'exclusive_offers');
        offerCard(null, 'steam-5', 699, 720);

        $this->get(route('offers'))->assertNotFound();
    });

    it('is listed in the sitemap only while the programme is on', function () {
        $this->get('/sitemap.xml')->assertSee(route('offers'), false);

        SiteSetting::set('exclusive_offers_enabled', '0', 'exclusive_offers');

        $this->get('/sitemap.xml')->assertDontSee(route('offers'), false);
    });
});
