<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * The e-mail palette.
 *
 * The storefront paints itself with CSS custom properties, which no mail client
 * can be trusted to resolve, and with Tailwind's alpha modifiers, which need a
 * compositor the mail clients also lack. E-mail therefore cannot consume
 * resources/css/storefront.css the way a view does — it needs literal hex, and
 * it needs every panel opaque.
 *
 * What it can do is derive every one of those literals from the same twelve
 * numbers. The channels below mirror the `:root` block of storefront.css, and
 * MailTemplateTest fails the build if the two ever disagree. Every tinted panel
 * and every lifted heading in the templates is computed from them rather than
 * typed by hand, so a palette change lands in the e-mails and the storefront
 * together or not at all.
 */
final class EmailTheme
{
    /**
     * The two stacks every message uses. Inter first, to match the storefront,
     * then the system faces a mail client will actually have.
     */
    public const FONT = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
    public const MONO = "'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, 'Courier New', monospace";

    /** Bare RGB channels, mirroring the `:root` block of storefront.css. */
    public const TOKENS = [
        'surface-0'    => [15, 15, 17],
        'surface-1'    => [23, 23, 26],
        'surface-2'    => [31, 31, 35],
        'surface-3'    => [42, 42, 48],
        'text-hi'      => [244, 244, 245],
        'text-mid'     => [161, 161, 170],
        'text-low'     => [113, 113, 122],
        'accent'       => [37, 99, 235],
        'accent-hover' => [59, 130, 246],
        'success'      => [34, 197, 94],
        'warning'      => [245, 158, 11],
        'danger'       => [239, 68, 68],
    ];

    /**
     * How a toned panel is built: the tone laid over the canvas at 12% for the
     * fill and 32% for the border, and lifted 30% toward the brightest ink for
     * the heading. Composited here because `rgba()` over a dark body collapses
     * to near-white the moment a client drops the background — which is exactly
     * how the previous templates lost their light text.
     */
    private const PANEL_FILL   = 0.12;
    private const PANEL_BORDER = 0.32;
    private const HEADING_LIFT = 0.30;

    private static ?array $palette = null;

    /** @return array{0:int,1:int,2:int} */
    public static function channels(string $token): array
    {
        return self::TOKENS[$token]
            ?? throw new InvalidArgumentException("Unknown e-mail colour token [{$token}].");
    }

    public static function hex(string $token): string
    {
        return self::toHex(self::channels($token));
    }

    /** `$token` laid over `$over` at `$alpha`, flattened to an opaque hex. */
    public static function tint(string $token, float $alpha, string $over = 'surface-0'): string
    {
        return self::toHex(self::mix(self::channels($token), self::channels($over), $alpha));
    }

    /**
     * `$token` blended toward the brightest ink, for a heading that has to stay
     * legible on its own tinted panel. Lifting toward `text-hi` rather than pure
     * white keeps the result inside the palette.
     */
    public static function lift(string $token, float $amount): string
    {
        return self::toHex(self::mix(self::channels('text-hi'), self::channels($token), $amount));
    }

    /**
     * Every colour the templates may name, under the role it plays rather than
     * the token it came from. A template reaches for `$c['card']`, never for a
     * literal and never for arithmetic of its own.
     */
    public static function palette(): array
    {
        return self::$palette ??= [
            'canvas'       => self::hex('surface-0'),
            'card'         => self::hex('surface-1'),
            'raised'       => self::hex('surface-2'),
            'border'       => self::hex('surface-3'),
            'ink'          => self::hex('text-hi'),
            'ink-mid'      => self::hex('text-mid'),
            'ink-low'      => self::hex('text-low'),
            'accent'       => self::hex('accent'),
            'accent-hover' => self::hex('accent-hover'),

            ...self::tone('success', 'success'),
            ...self::tone('warning', 'warning'),
            ...self::tone('danger', 'danger'),
            ...self::tone('info', 'accent'),

            // The one tone that is not a semantic colour: a plain raised panel,
            // for a message that is neither good news nor bad.
            'neutral-bg'     => self::hex('surface-2'),
            'neutral-border' => self::hex('surface-3'),
            'neutral-ink'    => self::hex('text-hi'),
        ];
    }

    /** @return array<string,string> */
    private static function tone(string $name, string $token): array
    {
        return [
            "{$name}-bg"     => self::tint($token, self::PANEL_FILL),
            "{$name}-border" => self::tint($token, self::PANEL_BORDER),
            "{$name}-ink"    => self::lift($token, self::HEADING_LIFT),
        ];
    }

    /** @return array{0:int,1:int,2:int} */
    private static function mix(array $top, array $bottom, float $alpha): array
    {
        return array_map(
            fn (int $t, int $b) => (int) round($alpha * $t + (1 - $alpha) * $b),
            $top,
            $bottom,
        );
    }

    private static function toHex(array $channels): string
    {
        return vsprintf('#%02X%02X%02X', $channels);
    }
}
