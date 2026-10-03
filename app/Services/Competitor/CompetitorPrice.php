<?php

namespace App\Services\Competitor;

/**
 * A price as the competitor's own page stated it, before any conversion.
 *
 * Kept in the source currency on purpose. The conversion belongs to the
 * comparison, not to the reading, and storing what the page actually said is
 * what makes a stored comparison auditable afterwards.
 */
final readonly class CompetitorPrice
{
    public function __construct(
        public float $amount,
        public string $currency,
    ) {
    }
}
