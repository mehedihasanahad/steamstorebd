<?php

namespace App\Exceptions;

use App\Models\CompetitorPriceCheck;
use RuntimeException;

/**
 * A competitor's price could not be read.
 *
 * Carries the reason the admin screen should show, because the two ways this
 * fails call for different responses: a page that would not load may well
 * load tomorrow, while a page that loads without a price has probably been
 * restructured or delisted and the URL needs re-checking by hand.
 */
class CompetitorFetchException extends RuntimeException
{
    public function __construct(public readonly string $reason, string $message = '')
    {
        parent::__construct($message !== '' ? $message : $reason);
    }

    public static function transport(string $detail = ''): self
    {
        return new self(CompetitorPriceCheck::REASON_FETCH_FAILED, $detail);
    }

    public static function priceNotFound(string $detail = ''): self
    {
        return new self(CompetitorPriceCheck::REASON_PRICE_NOT_FOUND, $detail);
    }
}
