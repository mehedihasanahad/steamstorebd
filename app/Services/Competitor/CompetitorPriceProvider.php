<?php

namespace App\Services\Competitor;

/**
 * Reads one shop's price for one product page.
 *
 * Implementations throw CompetitorFetchException rather than returning null,
 * so the caller is handed the reason along with the failure and never has to
 * guess which of the two failure modes it is looking at.
 */
interface CompetitorPriceProvider
{
    /** Matches the key stored in `competitor_listings.provider`. */
    public function key(): string;

    /** @throws \App\Exceptions\CompetitorFetchException */
    public function fetch(string $url): CompetitorPrice;
}
