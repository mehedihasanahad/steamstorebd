<?php

namespace App\Services\Competitor;

use App\Models\SiteSetting;

/**
 * What one unit of a competitor's currency is worth to us in taka.
 *
 * This is not a market rate and is not meant to be. It is the number the shop
 * owner decides to price against -- their landed cost per dollar, which
 * already carries whatever spread, fee and buffer they buy at -- so it lives
 * in Site Settings next to the other figures they tune, not in a feed.
 *
 * A currency with no rate set yields null rather than falling back to another
 * currency's rate. Quietly converting euros at the dollar rate would produce
 * a number that looks like every other number on the screen and is wrong by
 * about a tenth, which is the whole margin on a gift card.
 */
class ExchangeRates
{
    public const SETTING_PREFIX = 'competitor_rate_';

    public const FEE_PREFIX = 'competitor_fee_';

    /** The per-order payment fee a shop adds, until an admin says otherwise. */
    public const DEFAULT_FEE = ['USD' => '0.33'];

    /** The currencies the settings screen offers a field for. */
    public const CURRENCIES = ['USD', 'EUR', 'GBP'];

    public static function settingKey(string $currency): string
    {
        return self::SETTING_PREFIX . strtolower($currency);
    }

    public static function feeSettingKey(string $currency): string
    {
        return self::FEE_PREFIX . strtolower($currency);
    }

    /** Taka per one unit of `$currency`, or null when none is configured. */
    public function bdtPer(string $currency): ?float
    {
        $currency = strtoupper(trim($currency));

        if ($currency === 'BDT') {
            return 1.0;
        }

        $rate = (float) SiteSetting::get(self::settingKey($currency), 0);

        return $rate > 0 ? $rate : null;
    }

    /**
     * The payment fee charged on top of a listed price, in that same
     * currency. Zero when none is configured, which simply means the listed
     * price is taken at face value.
     */
    public function feeFor(string $currency): float
    {
        $currency = strtoupper(trim($currency));

        // Falls back to the shipped default rather than to nothing: the fee
        // is charged whether or not anyone has opened the settings screen,
        // and a comparison that quietly ignores it is wrong in the
        // competitor's favour. Saving an explicit 0 is how it is turned off.
        $fee = (float) SiteSetting::get(
            self::feeSettingKey($currency),
            self::DEFAULT_FEE[$currency] ?? 0,
        );

        return $fee > 0 ? round($fee, 2) : 0.0;
    }
}
