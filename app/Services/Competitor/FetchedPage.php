<?php

namespace App\Services\Competitor;

/** A product page as it came back, before anything has read it. */
final readonly class FetchedPage
{
    public function __construct(
        public int $status,
        public string $body,
    ) {
    }

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }
}
