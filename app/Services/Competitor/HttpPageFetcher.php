<?php

namespace App\Services\Competitor;

use App\Exceptions\CompetitorFetchException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;

/**
 * A plain HTTP request: cheap, fast, and enough for any shop that will serve
 * one. Where a shop refuses, BrowserPageFetcher takes over.
 */
class HttpPageFetcher implements PageFetcher
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    public function fetch(string $url): FetchedPage
    {
        $config = config('competitor.http');

        $request = $this->http
            ->timeout((int) $config['timeout'])
            ->connectTimeout((int) $config['connect_timeout'])
            // Transient 5xx and dropped connections are worth one more go;
            // a 404 is not, and retrying it only slows the run down.
            ->retry($config['retry_delays'], throw: false)
            ->withHeaders([
                'User-Agent'      => $config['user_agent'],
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
            ]);

        if (filled($config['proxy'] ?? null)) {
            $request = $request->withOptions(['proxy' => $config['proxy']]);
        }

        try {
            $response = $request->get($url);
        } catch (ConnectionException | RequestException $e) {
            throw CompetitorFetchException::transport($e->getMessage());
        }

        return new FetchedPage($response->status(), $response->body());
    }
}
