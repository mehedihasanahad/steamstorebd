<?php

namespace App\Support;

/**
 * The regions a product can be redeemable in.
 *
 * One source of truth for the admin dropdown, the storefront flag chips and
 * the mega-menu region column, so a code, its label and its flag can never
 * disagree between screens.
 */
final class Region
{
    /** A product redeemable anywhere. */
    public const GLOBAL = 'GL';

    /** @var array<string, array{name: string, flag: string}> */
    private const REGIONS = [
        self::GLOBAL => ['name' => 'Global',         'flag' => '🌍'],
        'BD'         => ['name' => 'Bangladesh',     'flag' => '🇧🇩'],
        'US'         => ['name' => 'United States',  'flag' => '🇺🇸'],
        'GB'         => ['name' => 'United Kingdom', 'flag' => '🇬🇧'],
        'IN'         => ['name' => 'India',          'flag' => '🇮🇳'],
        'HK'         => ['name' => 'Hong Kong',      'flag' => '🇭🇰'],
        'SG'         => ['name' => 'Singapore',      'flag' => '🇸🇬'],
        'MY'         => ['name' => 'Malaysia',       'flag' => '🇲🇾'],
        'ID'         => ['name' => 'Indonesia',      'flag' => '🇮🇩'],
        'TR'         => ['name' => 'Turkey',         'flag' => '🇹🇷'],
        'AE'         => ['name' => 'UAE',            'flag' => '🇦🇪'],
        'SA'         => ['name' => 'Saudi Arabia',   'flag' => '🇸🇦'],
        'EU'         => ['name' => 'Europe',         'flag' => '🇪🇺'],
        'CA'         => ['name' => 'Canada',         'flag' => '🇨🇦'],
        'AU'         => ['name' => 'Australia',      'flag' => '🇦🇺'],
        'JP'         => ['name' => 'Japan',          'flag' => '🇯🇵'],
        'BR'         => ['name' => 'Brazil',         'flag' => '🇧🇷'],
        'PH'         => ['name' => 'Philippines',    'flag' => '🇵🇭'],
        'TH'         => ['name' => 'Thailand',       'flag' => '🇹🇭'],
        'PK'         => ['name' => 'Pakistan',       'flag' => '🇵🇰'],
    ];

    public static function exists(?string $code): bool
    {
        return $code !== null && array_key_exists(strtoupper($code), self::REGIONS);
    }

    public static function name(?string $code): ?string
    {
        return self::exists($code) ? self::REGIONS[strtoupper($code)]['name'] : null;
    }

    public static function flag(?string $code): ?string
    {
        return self::exists($code) ? self::REGIONS[strtoupper($code)]['flag'] : null;
    }

    /** "🇭🇰 Hong Kong", or null when the code is unknown. */
    public static function label(?string $code): ?string
    {
        return self::exists($code)
            ? self::REGIONS[strtoupper($code)]['flag'] . ' ' . self::REGIONS[strtoupper($code)]['name']
            : null;
    }

    /**
     * Code => label, for a Filament select or a storefront filter.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_map(
            fn (array $region) => $region['flag'] . ' ' . $region['name'],
            self::REGIONS,
        );
    }

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::REGIONS);
    }
}
