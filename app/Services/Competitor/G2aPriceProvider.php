<?php

namespace App\Services\Competitor;

use App\Exceptions\CompetitorFetchException;

/**
 * Reads a price off a G2A product page.
 *
 * G2A publishes no public price API, so this reads the page a buyer sees.
 * Both halves of that are deliberately generic: how the page is fetched is a
 * PageFetcher, and how it is read is ProductPagePrice, which works on any
 * shop publishing schema.org offers. What is left here -- the key, and which
 * failure means what -- is all that is actually specific to G2A.
 */
class G2aPriceProvider implements CompetitorPriceProvider
{
    public function __construct(private readonly PageFetcher $fetcher)
    {
    }

    public function key(): string
    {
        return 'g2a';
    }

    public function fetch(string $url): CompetitorPrice
    {
        $page = $this->fetcher->fetch($url);

        if (! $page->successful()) {
            throw CompetitorFetchException::transport("HTTP {$page->status}");
        }

        $price = ProductPagePrice::extract($page->body);

        if ($price === null) {
            // Nearly always one of two things: the URL now redirects to a
            // listing page, or the product was delisted. Either way a person
            // has to look, so the reason says the page loaded.
            throw CompetitorFetchException::priceNotFound();
        }

        return $price;
    }
}
