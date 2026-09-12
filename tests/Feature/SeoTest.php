<?php

use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\GiftCardCode;
use App\Models\MainCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function seoBrand(array $overrides = []): MainCategory
{
    return MainCategory::create(array_merge([
        'name'      => 'Steam',
        'slug'      => 'steam',
        'is_active' => true,
    ], $overrides));
}

function seoProduct(?MainCategory $brand = null, array $overrides = []): GiftCardCategory
{
    return GiftCardCategory::create(array_merge([
        'name'             => 'Steam Wallet',
        'slug'             => 'steam-wallet',
        'is_active'        => true,
        'main_category_id' => $brand?->id,
    ], $overrides));
}

function seoCard(GiftCardCategory $category, array $overrides = [], int $codes = 0): GiftCard
{
    $card = GiftCard::create(array_merge([
        'category_id'           => $category->id,
        'name'                  => 'Steam Wallet $10',
        'slug'                  => 'steam-wallet-10',
        'denomination'          => 10,
        'denomination_currency' => 'USD',
        'denomination_bdt'      => 1200,
        'price_bdt'             => 1250,
        'is_active'             => true,
    ], $overrides));

    $admin = $codes > 0 ? User::factory()->create() : null;

    for ($i = 0; $i < $codes; $i++) {
        GiftCardCode::create([
            'gift_card_id'      => $card->id,
            'code'              => "{$card->slug}-CODE-{$i}",
            'status'            => 'available',
            'added_by_admin_id' => $admin->id,
        ]);
    }

    return $card;
}

describe('sitemap', function () {
    it('lists visible brands, products and policy pages but never card redirect urls', function () {
        $brand   = seoBrand();
        $product = seoProduct($brand);
        seoCard($product);

        $this->get('/sitemap.xml')
            ->assertSuccessful()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee(route('brand', 'steam'), false)
            ->assertSee(route('product', 'steam-wallet'), false)
            ->assertSee(route('refund-policy'), false)
            ->assertSee(route('about'), false)
            ->assertDontSee('/cards/', false)
            ->assertDontSee('<priority>', false);
    });

    it('leaves out products the storefront hides', function () {
        $brand = seoBrand();
        seoCard(seoProduct($brand));

        seoProduct($brand, ['name' => 'Disabled', 'slug' => 'disabled-product', 'is_active' => false]);
        seoProduct($brand, ['name' => 'Empty', 'slug' => 'empty-product']);

        $hiddenBrand = seoBrand(['name' => 'Hidden', 'slug' => 'hidden-brand', 'is_active' => false]);
        seoCard(seoProduct($hiddenBrand, ['name' => 'Orphan', 'slug' => 'orphan-product']), ['slug' => 'orphan-10']);

        $this->get('/sitemap.xml')
            ->assertSuccessful()
            ->assertDontSee('disabled-product')
            ->assertDontSee('empty-product')
            ->assertDontSee('hidden-brand')
            ->assertDontSee('orphan-product');
    });

    it('includes products that are not assigned to a brand', function () {
        seoCard(seoProduct(null, ['name' => 'Google Play', 'slug' => 'google-play']), ['slug' => 'google-play-10']);

        $this->get('/sitemap.xml')
            ->assertSuccessful()
            ->assertSee(route('product', 'google-play'), false);
    });
});

describe('redirects', function () {
    it('permanently redirects a card url to its product page', function () {
        $card = seoCard(seoProduct(seoBrand()));

        $this->get('/cards/' . $card->slug)
            ->assertMovedPermanently()
            ->assertRedirect(route('product', 'steam-wallet'));
    });

    it('permanently redirects legacy shop urls to the matching page', function (string $path, string $routeName, array $parameters) {
        seoCard(seoProduct(seoBrand()));

        $this->get($path)
            ->assertMovedPermanently()
            ->assertRedirect(route($routeName, $parameters));
    })->with([
        'shop index'      => ['/shop', 'home', []],
        'product slug'    => ['/shop/steam-wallet', 'product', ['steam-wallet']],
        'brand slug'      => ['/shop/steam', 'brand', ['steam']],
        'unknown slug'    => ['/shop/no-such-thing', 'home', []],
    ]);

    it('keeps old product and brand urls working after a slug change', function () {
        $brand   = seoBrand();
        $product = seoProduct($brand);
        seoCard($product);

        $product->update(['slug' => 'steam-wallet-usd']);
        $brand->update(['slug' => 'steam-games']);

        $this->get('/product/steam-wallet')
            ->assertMovedPermanently()
            ->assertRedirect(route('product', 'steam-wallet-usd'));

        $this->get('/brand/steam')
            ->assertMovedPermanently()
            ->assertRedirect(route('brand', 'steam-games'));
    });

    it('drops the redirect when a slug is taken back', function () {
        $product = seoProduct(seoBrand());
        seoCard($product);

        $product->update(['slug' => 'steam-wallet-usd']);
        $product->update(['slug' => 'steam-wallet']);

        $this->get('/product/steam-wallet')->assertSuccessful();
        $this->get('/product/steam-wallet-usd')
            ->assertMovedPermanently()
            ->assertRedirect(route('product', 'steam-wallet'));
    });

    it('does not redirect an old slug to a product that was switched off', function () {
        $product = seoProduct(seoBrand());
        $product->update(['slug' => 'steam-wallet-usd', 'is_active' => false]);

        $this->get('/product/steam-wallet')->assertNotFound();
    });
});

describe('product page metadata', function () {
    it('writes a clean description with the lowest in-stock price', function () {
        seoCard(seoProduct(seoBrand()), codes: 2);

        $this->get('/product/steam-wallet')
            ->assertSuccessful()
            ->assertSee('<title>Buy Steam Wallet in Bangladesh — Steam Store BD</title>', false)
            ->assertSee('content="Buy Steam Wallet in Bangladesh with bKash. Instant code delivery to email. Prices from ৳1,250. 100% genuine codes."', false)
            ->assertDontSee('&amp;amp;', false);
    });

    it('omits the price when nothing is in stock', function () {
        seoCard(seoProduct(seoBrand()));

        $this->get('/product/steam-wallet')
            ->assertSuccessful()
            ->assertDontSee('Prices from', false)
            ->assertDontSee('৳ BDT', false)
            ->assertSee('"availability":"https://schema.org/OutOfStock"', false);
    });

    it('lists every wallet enabled in site settings', function () {
        \App\Models\SiteSetting::set('payment_nagad_send_money_enabled', '1', 'payment');
        seoCard(seoProduct(seoBrand()), codes: 1);

        $this->get('/product/steam-wallet')
            ->assertSuccessful()
            ->assertSee('with bKash or Nagad.', false);
    });

    it('prefers the seo title and description set in the admin', function () {
        seoCard(seoProduct(seoBrand(), [
            'seo_title'       => 'Steam Wallet Code BD',
            'seo_description' => 'Custom description from the admin panel.',
        ]));

        $this->get('/product/steam-wallet')
            ->assertSuccessful()
            ->assertSee('<title>Steam Wallet Code BD — Steam Store BD</title>', false)
            ->assertSee('content="Custom description from the admin panel."', false);
    });

    it('counts stock for every denomination in a single query', function () {
        $product = seoProduct(seoBrand());
        seoCard($product, ['slug' => 'steam-wallet-5', 'denomination' => 5, 'price_bdt' => 650], codes: 2);
        seoCard($product, ['slug' => 'steam-wallet-10'], codes: 3);
        seoCard($product, ['slug' => 'steam-wallet-20', 'denomination' => 20, 'price_bdt' => 2450], codes: 1);

        DB::enableQueryLog();
        $this->get('/product/steam-wallet')->assertSuccessful();

        $codeQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains($query['query'], 'gift_card_codes'));

        expect($codeQueries)->toHaveCount(1);
    });

    it('links to other products from the same brand', function () {
        $brand = seoBrand();
        seoCard(seoProduct($brand));
        seoCard(seoProduct($brand, ['name' => 'Steam Wallet TL', 'slug' => 'steam-wallet-tl']), ['slug' => 'steam-wallet-tl-100', 'price_bdt' => 900]);

        $this->get('/product/steam-wallet')
            ->assertSuccessful()
            ->assertSee('More from Steam')
            ->assertSee(route('product', 'steam-wallet-tl'), false);
    });
});

describe('indexing directives', function () {
    it('keeps shopping pages indexable', function () {
        seoCard(seoProduct(seoBrand()));

        $this->get('/')->assertSuccessful()->assertSee('content="index, follow"', false);
        $this->get('/product/steam-wallet')->assertSuccessful()->assertSee('content="index, follow"', false);
    });

    it('marks utility pages noindex', function (string $path) {
        $this->get($path)
            ->assertSuccessful()
            ->assertSee('name="robots" content="noindex', false);
    })->with([
        'cart'            => '/cart',
        'order lookup'    => '/orders',
        'login'           => '/login',
        'register'        => '/register',
        'forgot password' => '/forgot-password',
    ]);

    it('gives each auth page its own title', function () {
        $this->get('/login')->assertSee('<title>Sign In — ', false);
        $this->get('/register')->assertSee('<title>Create Account — ', false);
    });

    it('drops meta keywords from the layout', function () {
        $this->get('/')->assertDontSee('name="keywords"', false);
    });
});

describe('structured data', function () {
    it('describes the organisation without the retired search action', function () {
        \App\Models\SiteSetting::set('contact_email', 'support@example.com', 'general');
        \App\Models\SiteSetting::set('messenger_page_username', 'SteamStoreBD', 'chat');

        $this->get('/')
            ->assertSuccessful()
            ->assertDontSee('SearchAction', false)
            ->assertSee('images/icons/icon-512.png', false)
            ->assertSee('"sameAs":["https://www.facebook.com/SteamStoreBD"]', false)
            ->assertSee('"email":"support@example.com"', false)
            ->assertSee('MerchantReturnNotPermitted', false);
    });
});

describe('site pages', function () {
    it('serves the company and policy pages', function (string $routeName, string $heading) {
        $this->get(route($routeName))
            ->assertSuccessful()
            ->assertSee($heading);
    })->with([
        'about'   => ['about', 'About Steam Store BD'],
        'refund'  => ['refund-policy', 'Refund & Replacement Policy'],
        'privacy' => ['privacy-policy', 'Privacy Policy'],
        'terms'   => ['terms', 'Terms of Service'],
    ]);

    it('links brands and policy pages from the footer', function () {
        seoCard(seoProduct(seoBrand()));

        $this->get('/faq')
            ->assertSuccessful()
            ->assertSee(route('brand', 'steam'), false)
            ->assertSee(route('how-to-redeem'), false)
            ->assertSee(route('refund-policy'), false);
    });

    it('renders a branded 404 page that is not indexed', function () {
        seoCard(seoProduct(seoBrand()));

        $this->get('/product/does-not-exist')
            ->assertNotFound()
            ->assertSee("This page doesn't exist", false)
            ->assertSee('noindex, follow', false)
            ->assertSee(route('brand', 'steam'), false);
    });

    it('renders the branded 404 for urls that match no route', function () {
        $this->get('/no/such/page')
            ->assertNotFound()
            ->assertSee("This page doesn't exist", false);
    });
});

describe('canonical redirect', function () {
    it('sends other hosts and schemes to the app url when enabled', function () {
        config(['app.canonical_redirect' => true, 'app.url' => 'https://steamstorebd.com']);

        $this->get('http://www.steamstorebd.com/faq?ref=abc')
            ->assertMovedPermanently()
            ->assertRedirect('https://steamstorebd.com/faq?ref=abc');
    });

    it('does nothing while disabled', function () {
        config(['app.canonical_redirect' => false, 'app.url' => 'https://steamstorebd.com']);

        $this->get('http://www.steamstorebd.com/faq')->assertSuccessful();
    });

    it('never redirects form submissions', function () {
        config(['app.canonical_redirect' => true, 'app.url' => 'https://steamstorebd.com']);

        $this->post('http://www.steamstorebd.com/contact', [])
            ->assertStatus(302)
            ->assertSessionHasErrors(['name', 'email', 'message']);
    });
});
