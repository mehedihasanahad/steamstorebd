<?php

/**
 * The parser that reads a price off a competitor page.
 *
 * This is the part of the feature most likely to rot, because it depends on
 * markup nobody here controls. It is pure string work on purpose, so every
 * shape a shop might publish can be pinned down here without a network.
 */

use App\Services\Competitor\ProductPagePrice;

function page(string $body): string
{
    return '<!doctype html><html><head>' . $body . '</head><body>Buy now</body></html>';
}

function jsonLd(array $payload): string
{
    return '<script type="application/ld+json">' . json_encode($payload) . '</script>';
}

describe('schema.org JSON-LD', function () {
    it('reads a plain product offer', function () {
        $html = page(jsonLd([
            '@context' => 'https://schema.org',
            '@type'    => 'Product',
            'name'     => 'Steam Gift Card 10 USD',
            'offers'   => ['@type' => 'Offer', 'price' => '12.99', 'priceCurrency' => 'USD'],
        ]));

        $price = ProductPagePrice::extract($html);

        expect($price->amount)->toBe(12.99)
            ->and($price->currency)->toBe('USD');
    });

    it('reads an offer nested in a graph', function () {
        $html = page(jsonLd([
            '@context' => 'https://schema.org',
            '@graph'   => [
                ['@type' => 'BreadcrumbList'],
                ['@type' => 'Product', 'offers' => ['@type' => 'Offer', 'price' => 9.5, 'priceCurrency' => 'eur']],
            ],
        ]));

        $price = ProductPagePrice::extract($html);

        expect($price->amount)->toBe(9.5)
            // Shops are inconsistent about case; the rate lookup is not.
            ->and($price->currency)->toBe('EUR');
    });

    it('takes the low price of an aggregate offer', function () {
        $html = page(jsonLd([
            '@type'  => 'Product',
            'offers' => [
                '@type'         => 'AggregateOffer',
                'lowPrice'      => '4.20',
                'highPrice'     => '8.00',
                'priceCurrency' => 'USD',
            ],
        ]));

        expect(ProductPagePrice::extract($html)->amount)->toBe(4.2);
    });

    it('skips an offer with no currency rather than guessing one', function () {
        $html = page(jsonLd(['@type' => 'Product', 'offers' => ['price' => '12.99']]));

        expect(ProductPagePrice::extract($html))->toBeNull();
    });

    it('ignores a block that is not valid JSON', function () {
        $html = page('<script type="application/ld+json">{ not json </script>');

        expect(ProductPagePrice::extract($html))->toBeNull();
    });
});

describe('fallbacks', function () {
    it('falls back to Open Graph price meta tags', function () {
        $html = page(
            '<meta property="product:price:amount" content="15.50">' .
            '<meta property="product:price:currency" content="EUR">'
        );

        $price = ProductPagePrice::extract($html);

        expect($price->amount)->toBe(15.5)
            ->and($price->currency)->toBe('EUR');
    });

    it('reads a meta tag with its attributes the other way round', function () {
        $html = page('<meta content="7.25" itemprop="price"><meta content="GBP" itemprop="priceCurrency">');

        expect(ProductPagePrice::extract($html)->currency)->toBe('GBP');
    });

    it('falls back to an embedded JSON payload', function () {
        $html = page('<script>window.__DATA__ = {"sku":"x","price":"19.99","currency":"USD"};</script>');

        $price = ProductPagePrice::extract($html);

        expect($price->amount)->toBe(19.99)
            ->and($price->currency)->toBe('USD');
    });

    it('will not pair a price with a currency far away in the payload', function () {
        $filler = str_repeat('x', 300);
        $html   = page('<script>{"price":"19.99","' . $filler . '":1,"currency":"USD"}</script>');

        expect(ProductPagePrice::extract($html))->toBeNull();
    });

    it('prefers JSON-LD over a meta tag when both are present', function () {
        $html = page(
            jsonLd(['@type' => 'Product', 'offers' => ['price' => '11.00', 'priceCurrency' => 'USD']]) .
            '<meta property="product:price:amount" content="99.00">' .
            '<meta property="product:price:currency" content="USD">'
        );

        expect(ProductPagePrice::extract($html)->amount)->toBe(11.0);
    });

    it('finds nothing in a page that carries no price', function () {
        expect(ProductPagePrice::extract(page('<title>Steam</title>')))->toBeNull();
    });
});

describe('written prices', function () {
    it('reads the separators the way the shop meant them', function (string $written, float $expected) {
        $html = page(jsonLd([
            '@type'  => 'Product',
            'offers' => ['price' => $written, 'priceCurrency' => 'USD'],
        ]));

        expect(ProductPagePrice::extract($html)->amount)->toBe($expected);
    })->with([
        'plain'                 => ['12.99', 12.99],
        'european decimal'      => ['12,99', 12.99],
        'english thousands'     => ['1,234.56', 1234.56],
        'european thousands'    => ['1.234,56', 1234.56],
        'comma as grouping'     => ['1,234', 1234.0],
        'currency symbol glued' => ['$12.99', 12.99],
        'whole number'          => ['15', 15.0],
    ]);

    it('rejects a zero price, which is what a sold-out page tends to publish', function () {
        $html = page(jsonLd(['@type' => 'Product', 'offers' => ['price' => '0', 'priceCurrency' => 'USD']]));

        expect(ProductPagePrice::extract($html))->toBeNull();
    });
});
