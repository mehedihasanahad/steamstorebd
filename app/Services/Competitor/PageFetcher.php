<?php

namespace App\Services\Competitor;

/**
 * Gets a product page, however that has to be done for the shop in question.
 *
 * Two implementations, because shops differ in whether they will talk to a
 * script at all: a plain HTTP request where that works, and a real browser
 * where the shop fingerprints its callers. What comes back is the same HTML
 * either way, so ProductPagePrice stays the only thing that reads a page.
 */
interface PageFetcher
{
    /** @throws \App\Exceptions\CompetitorFetchException on a transport failure */
    public function fetch(string $url): FetchedPage;
}
