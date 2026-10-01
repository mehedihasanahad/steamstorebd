<?php

use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\GiftCardCode;
use App\Models\MainCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    it('lists the review wall only once a review has been approved', function () {
        seoCard(seoProduct(seoBrand()));

        $this->get('/sitemap.xml')
            ->assertSuccessful()
            ->assertDontSee(route('reviews'), false);

        \App\Models\Review::create([
            'rating'  => 5,
            'comment' => 'Instant delivery, paid with bKash.',
            'status'  => 'approved',
        ]);

        $this->get('/sitemap.xml')
            ->assertSuccessful()
            ->assertSee(route('reviews'), false);
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

    it('lists every configured profile in sameAs, google first', function () {
        \App\Models\SiteSetting::set('social_google_business_url', 'https://g.page/r/steamstorebd', 'social');
        \App\Models\SiteSetting::set('social_facebook_url', 'https://www.facebook.com/SteamStoreBD', 'social');
        \App\Models\SiteSetting::set('social_trustpilot_url', 'https://www.trustpilot.com/review/steamstorebd.com', 'social');

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('"sameAs":["https://g.page/r/steamstorebd","https://www.facebook.com/SteamStoreBD","https://www.trustpilot.com/review/steamstorebd.com"]', false);
    });

    it('drops a profile field that is not a url', function () {
        \App\Models\SiteSetting::set('social_google_business_url', 'g.page/r/steamstorebd', 'social');
        \App\Models\SiteSetting::set('social_facebook_url', 'https://www.facebook.com/SteamStoreBD', 'social');

        // A half-typed URL claims an identity that does not resolve, which is
        // worse for the site than claiming nothing.
        $this->get('/')
            ->assertSuccessful()
            ->assertSee('"sameAs":["https://www.facebook.com/SteamStoreBD"]', false);
    });

    it('prefers the configured facebook url over the messenger username', function () {
        \App\Models\SiteSetting::set('messenger_page_username', 'SteamStoreBD', 'chat');
        \App\Models\SiteSetting::set('social_facebook_url', 'https://www.facebook.com/SteamStoreBDOfficial', 'social');

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('"sameAs":["https://www.facebook.com/SteamStoreBDOfficial"]', false);
    });

    it('omits sameAs entirely when no profile is configured', function () {
        $this->get('/')
            ->assertSuccessful()
            ->assertDontSee('sameAs', false);
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

    it('links the review wall from every page footer', function () {
        seoCard(seoProduct(seoBrand()));

        // Unconditional on purpose: gating it on "are there reviews yet" would
        // cost a query on every page of the site, and the page carries its own
        // empty state. The sitemap is where the emptiness check belongs.
        $this->get('/faq')
            ->assertSuccessful()
            ->assertSee('Customer Reviews')
            ->assertSee(route('reviews'), false);
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

describe('robots.txt', function () {
    it('repeats the disallow list for every named assistant crawler', function () {
        $body = $this->get('/robots.txt')->assertSuccessful()->getContent();

        // A named group replaces the wildcard rather than adding to it, so an
        // agent listed here must carry the disallows itself or it is being
        // handed the admin and the checkout.
        foreach (['GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot'] as $agent) {
            $group = Str::of($body)->after('User-agent: '.$agent."\n")->before('User-agent: ')->value();

            expect($group)->toContain('Disallow: /admin')
                ->and($group)->toContain('Disallow: /checkout')
                ->and($group)->toContain('Disallow: /cart');
        }
    });

    it('advertises the xml sitemap as a directive and llms.txt as a comment', function () {
        $this->get('/robots.txt')
            ->assertSuccessful()
            ->assertSee('Sitemap: '.url('/sitemap.xml'), false)
            ->assertSee('# llms.txt: '.url('/llms.txt'), false)
            ->assertDontSee('Sitemap: '.url('/llms.txt'), false);
    });
});

describe('llms.txt', function () {
    it('lists what is on sale, with a price floor and a link', function () {
        $brand = seoBrand();
        seoCard(seoProduct($brand), [], 3);

        $this->get('/llms.txt')
            ->assertSuccessful()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('# Steam Store BD', false)
            ->assertSee('Steam Wallet', false)
            ->assertSee(route('product', 'steam-wallet'), false)
            ->assertSee('from ৳1,250', false)
            ->assertSee(route('faq'), false);
    });

    it('marks a product with no stock rather than hiding it', function () {
        $brand = seoBrand();
        seoCard(seoProduct($brand), [], 0);

        $this->get('/llms.txt')
            ->assertSuccessful()
            ->assertSee('(out of stock)', false);
    });
});

describe('canonical on a paginated listing', function () {
    it('points page two at itself, not at page one', function () {
        $brand = seoBrand();
        foreach (range(1, \App\Services\CatalogBrowser::PER_PAGE + 2) as $i) {
            seoCard(
                seoProduct($brand, ['name' => "Card {$i}", 'slug' => "card-{$i}"]),
                ['slug' => "card-{$i}-10"],
                1
            );
        }

        $base = route('brand', 'steam');

        $this->get($base.'?page=2')
            ->assertSuccessful()
            ->assertSee('<link rel="canonical" href="'.$base.'?page=2">', false)
            ->assertSee('<link rel="prev" href="'.$base.'">', false);

        $this->get($base)
            ->assertSuccessful()
            ->assertSee('<link rel="canonical" href="'.$base.'">', false)
            ->assertSee('<link rel="next" href="'.$base.'?page=2">', false);
    });

    it('folds a sorted view back onto the unfiltered page', function () {
        $brand = seoBrand();
        seoCard(seoProduct($brand), [], 1);

        $this->get(route('brand', 'steam').'?sort=price_asc')
            ->assertSuccessful()
            ->assertSee('<link rel="canonical" href="'.route('brand', 'steam').'">', false);
    });
});
