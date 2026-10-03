<?php

namespace App\Services\Competitor;

/** What one run of the nightly check did, for the console and the log. */
final class PriceCheckSummary
{
    public int $checked       = 0;
    public int $opportunities = 0;
    public int $failed        = 0;
    public int $skipped       = 0;

    public function total(): int
    {
        return $this->checked + $this->failed + $this->skipped;
    }
}
