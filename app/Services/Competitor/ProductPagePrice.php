<?php

namespace App\Services\Competitor;

/**
 * Pulls a price out of a product page's HTML.
 *
 * Scraping rendered markup is a losing game -- class names change with every
 * redesign -- so this reads the structured data instead. Shops publish
 * schema.org Product/Offer blocks because that is what puts a price in a
 * Google result, which makes it the one part of the page nobody breaks
 * casually, and it states its own currency rather than leaving us to infer it.
 *
 * Three readings are tried in order of how much we trust them: the JSON-LD
 * block, then Open Graph / microdata price meta tags, then adjacent
 * price/priceCurrency keys anywhere in an embedded JSON payload. All of them
 * are pure string work, which is what lets the whole thing be tested against
 * saved markup without a network.
 */
final class ProductPagePrice
{
    /** Matches a quote character in an HTML attribute, either kind. */
    private const Q = '["\']';

    public static function extract(string $html): ?CompetitorPrice
    {
        return self::fromJsonLd($html)
            ?? self::fromMetaTags($html)
            ?? self::fromEmbeddedJson($html);
    }

    /** The schema.org block. Preferred: it is explicit about its currency. */
    private static function fromJsonLd(string $html): ?CompetitorPrice
    {
        $q = self::Q;
        $pattern = '#<script[^>]*type=' . $q . 'application/ld\+json' . $q . '[^>]*>(.*?)</script>#is';

        if (! preg_match_all($pattern, $html, $matches)) {
            return null;
        }

        foreach ($matches[1] as $block) {
            $decoded = json_decode(trim($block), true);

            if (! is_array($decoded)) {
                continue;
            }

            if ($price = self::walkForOffer($decoded)) {
                return $price;
            }
        }

        return null;
    }

    /**
     * Depth-first walk for anything carrying an `offers` key.
     *
     * Deliberately not anchored to a Product node: a page may wrap the product
     * in an ItemPage, a graph, or a list of several entities, and some shops
     * mistype the node while still filling the offer correctly. The offer is
     * the part that has to be right, so that is what is matched.
     */
    private static function walkForOffer(array $node): ?CompetitorPrice
    {
        if (isset($node['offers']) && is_array($node['offers'])) {
            $offers = array_is_list($node['offers']) ? $node['offers'] : [$node['offers']];

            foreach ($offers as $offer) {
                if (is_array($offer) && $price = self::readOffer($offer)) {
                    return $price;
                }
            }
        }

        // A bare Offer, or any nested structure: a graph, itemListElement,
        // mainEntity, or a plain list of entities.
        if ($price = self::readOffer($node)) {
            return $price;
        }

        foreach ($node as $child) {
            if (is_array($child) && $price = self::walkForOffer($child)) {
                return $price;
            }
        }

        return null;
    }

    /**
     * `lowPrice` comes after `price` because an AggregateOffer carries both a
     * range and, on some shops, a meaningless `price` of 0. The cheapest
     * offer is also the honest comparison: it is what a buyer would pay.
     */
    private static function readOffer(array $offer): ?CompetitorPrice
    {
        $currency = $offer['priceCurrency'] ?? $offer['pricecurrency'] ?? null;

        if (! is_string($currency) || trim($currency) === '') {
            return null;
        }

        foreach (['price', 'lowPrice'] as $key) {
            if (! isset($offer[$key]) || is_array($offer[$key])) {
                continue;
            }

            $amount = self::toAmount((string) $offer[$key]);

            if ($amount !== null && $amount > 0) {
                return new CompetitorPrice($amount, strtoupper(trim($currency)));
            }
        }

        return null;
    }

    /** Open Graph product tags and bare microdata, as a second opinion. */
    private static function fromMetaTags(string $html): ?CompetitorPrice
    {
        $amount   = self::metaContent($html, ['product:price:amount', 'og:price:amount', 'price']);
        $currency = self::metaContent($html, ['product:price:currency', 'og:price:currency', 'priceCurrency']);

        if ($amount === null || $currency === null) {
            return null;
        }

        $value = self::toAmount($amount);

        return $value !== null && $value > 0
            ? new CompetitorPrice($value, strtoupper(trim($currency)))
            : null;
    }

    /**
     * Reads a meta tag's content whichever way round its attributes are
     * written -- property/name/itemprop before content, or content first.
     */
    private static function metaContent(string $html, array $keys): ?string
    {
        $q = self::Q;

        foreach ($keys as $key) {
            $name = preg_quote($key, '#');

            $patterns = [
                '#<meta[^>]*(?:property|name|itemprop)=' . $q . $name . $q . '[^>]*content=' . $q . '([^"\']+)' . $q . '#i',
                '#<meta[^>]*content=' . $q . '([^"\']+)' . $q . '[^>]*(?:property|name|itemprop)=' . $q . $name . $q . '#i',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $html, $match)) {
                    return $match[1];
                }
            }
        }

        return null;
    }

    /**
     * Last resort: a price and its currency sitting next to each other in any
     * JSON the page embeds. Single-page storefronts hydrate from a blob that
     * mirrors the schema.org key names even when they publish no JSON-LD.
     *
     * Only adjacent pairs are accepted, in either order and within a short
     * span, so this cannot pair one product's price with another's currency.
     */
    private static function fromEmbeddedJson(string $html): ?CompetitorPrice
    {
        $number   = '"(?:price|amount)"\s*:\s*"?([0-9][0-9.,]*)"?';
        $currency = '"(?:priceCurrency|currency)"\s*:\s*"([A-Za-z]{3})"';

        $patterns = [
            '#' . $number . '.{0,80}?' . $currency . '#is',
            '#' . $currency . '.{0,80}?' . $number . '#is',
        ];

        foreach ($patterns as $index => $pattern) {
            if (! preg_match($pattern, $html, $match)) {
                continue;
            }

            [$rawAmount, $rawCurrency] = $index === 0
                ? [$match[1], $match[2]]
                : [$match[2], $match[1]];

            $amount = self::toAmount($rawAmount);

            if ($amount !== null && $amount > 0) {
                return new CompetitorPrice($amount, strtoupper($rawCurrency));
            }
        }

        return null;
    }

    /**
     * Turns a written price into a number.
     *
     * European shops write 12,34 and English-speaking ones write 1,234.56, so
     * the separator that appears last is the decimal point and anything else
     * is grouping. A lone comma with exactly two digits behind it is decimal;
     * with three it is a thousands separator, which is why "1,234" reads as
     * one thousand and not as one and a bit.
     */
    private static function toAmount(string $raw): ?float
    {
        $clean = preg_replace('/[^0-9.,]/', '', trim($raw)) ?? '';

        if ($clean === '') {
            return null;
        }

        $lastComma = strrpos($clean, ',');
        $lastDot   = strrpos($clean, '.');

        if ($lastComma !== false && $lastDot !== false) {
            $decimal = $lastComma > $lastDot ? ',' : '.';
            $clean   = str_replace($decimal === ',' ? '.' : ',', '', $clean);
            $clean   = str_replace($decimal, '.', $clean);
        } elseif ($lastComma !== false) {
            $decimals = strlen($clean) - $lastComma - 1;
            $clean    = $decimals === 2
                ? str_replace(',', '.', $clean)
                : str_replace(',', '', $clean);
        }

        return is_numeric($clean) ? round((float) $clean, 2) : null;
    }
}
