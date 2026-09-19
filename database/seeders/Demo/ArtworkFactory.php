<?php

namespace Database\Seeders\Demo;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Generates the artwork the demo storefront needs.
 *
 * Brand tiles, product covers, denomination icons and hero banners are all
 * written as SVG: no binary dependency, a few hundred bytes each, and crisp at
 * any size. They carry the brand's own colours and its name set as a wordmark.
 *
 * Deliberately NOT reproductions of the real logos. Those are other companies'
 * trademarks; a fixture has no business shipping copies of them, and the real
 * ones are uploaded through the admin panel in production. What these give is
 * the density and colour the layout was designed around, so a page can be
 * judged on its design rather than on a grid of grey placeholders.
 */
class ArtworkFactory
{
    /**
     * Brand colours, eyeballed from each brand's own marketing.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const PALETTE = [
        'steam'          => ['#1B2838', '#2A475E'],
        'pubg'           => ['#F2A900', '#8A5A00'],
        'free-fire'      => ['#FF6B00', '#9A3412'],
        'roblox'         => ['#4B5563', '#111827'],
        'genshin'        => ['#4C6EF5', '#7C3AED'],
        'mobile-legends' => ['#1E40AF', '#312E81'],
        'marvel-rivals'  => ['#B91C1C', '#450A0A'],
        'apple'          => ['#EC4899', '#8B5CF6'],
        'valorant'       => ['#FF4655', '#7F1D1D'],
        'playstation'    => ['#0070D1', '#00307E'],
        'nintendo'       => ['#E60012', '#7A0009'],
        'netflix'        => ['#E50914', '#5C0206'],
        'xbox'           => ['#107C10', '#0A4A0A'],
        'amazon'         => ['#FF9900', '#232F3E'],
        'blizzard'       => ['#148EFF', '#0B3F72'],
        'garena'         => ['#E11D2E', '#6B0E17'],
        'fortnite'       => ['#2BA8FF', '#7B2FF7'],
        'google-play'    => ['#34A853', '#1A73E8'],
        'forza'          => ['#1D4ED8', '#0EA5E9'],
        'minecraft'      => ['#4F7942', '#22331F'],
        'rockstar'       => ['#F59E0B', '#78350F'],
        'red-dead'       => ['#8B1A1A', '#431407'],
        'battlefield'    => ['#1E3A5F', '#0F172A'],
        'nordvpn'        => ['#4687FF', '#1E3A8A'],
        'youtube'        => ['#FF0000', '#7F0000'],
        'prime-video'    => ['#00A8E1', '#0F3D6B'],
        'microsoft'      => ['#EA3E23', '#7C1D0F'],
        'hoichoi'        => ['#E11D48', '#6B0B23'],
        'exitlag'        => ['#F97316', '#7C2D12'],
        'razer'          => ['#44D62C', '#14501A'],
        'hulu'           => ['#1CE783', '#0A5C36'],
        'spotify'        => ['#1DB954', '#0A4023'],
    ];

    /** 16:10 — the brand tile in a homepage or section rail. */
    public function brandTile(string $slug, string $name): string
    {
        // Wrapped, because "Red Dead Redemption" on one line runs off the tile.
        return $this->write("brands/{$slug}.svg", $this->wordmark($slug, $name, 320, 200, 30, wrap: 14));
    }

    /**
     * 4:3 — the product cover in a catalog grid, where the flag badge sits on
     * top and the product name is printed beneath. The cover carries the BRAND
     * name rather than the product's, so the card does not say the same thing
     * twice — which is what the reference designs do.
     */
    public function productCover(string $slug, string $brandName, string $brandSlug): string
    {
        return $this->write("products/{$slug}.svg", $this->wordmark($brandSlug, $brandName, 400, 300, 30, wrap: 14));
    }

    /** 1:1 — the denomination icon beside a tile on the product page. */
    public function cardIcon(string $slug, string $label, string $brandSlug): string
    {
        return $this->write("cards/{$slug}.svg", $this->badge($brandSlug, $label));
    }

    /** 16:5 — a hero slide. */
    public function banner(string $slug, string $headline, string $subline, string $brandSlug): string
    {
        return $this->write("banners/{$slug}.svg", $this->heroSlide($brandSlug, $headline, $subline));
    }

    private function write(string $path, string $svg): string
    {
        Storage::disk('public')->put($path, $svg);

        return $path;
    }

    /**
     * Colours for a slug, falling back to a hue derived from the name so an
     * unlisted brand still gets something of its own rather than grey.
     *
     * @return array{0: string, 1: string}
     */
    private function colours(string $slug): array
    {
        if (isset(self::PALETTE[$slug])) {
            return self::PALETTE[$slug];
        }

        $hue = crc32($slug) % 360;

        return ["hsl({$hue}, 58%, 42%)", "hsl({$hue}, 62%, 20%)"];
    }

    /** A gradient panel with the name across it, plus a soft corner highlight. */
    private function wordmark(string $slug, string $name, int $width, int $height, int $size, int $wrap = 0): string
    {
        [$from, $to] = $this->colours($slug);
        $id = Str::slug($slug) . '-' . $width;

        $lines = $wrap > 0 ? $this->wrapLines($name, $wrap) : [$name];
        $text  = $this->textBlock($lines, $width / 2, $height / 2, $size);

        return <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$width} {$height}" width="{$width}" height="{$height}" role="img" aria-label="{$this->escape($name)}">
                <defs>
                    <linearGradient id="g{$id}" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="{$from}"/>
                        <stop offset="100%" stop-color="{$to}"/>
                    </linearGradient>
                    <radialGradient id="s{$id}" cx="0.22" cy="0.18" r="0.7">
                        <stop offset="0%" stop-color="#ffffff" stop-opacity="0.22"/>
                        <stop offset="100%" stop-color="#ffffff" stop-opacity="0"/>
                    </radialGradient>
                </defs>
                <rect width="{$width}" height="{$height}" fill="url(#g{$id})"/>
                <rect width="{$width}" height="{$height}" fill="url(#s{$id})"/>
                {$text}
            </svg>
            SVG;
    }

    /** A rounded square with the denomination on it. */
    private function badge(string $slug, string $label): string
    {
        [$from, $to] = $this->colours($slug);
        $id = Str::slug($slug) . '-badge';

        return <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96" width="96" height="96" role="img" aria-label="{$this->escape($label)}">
                <defs>
                    <linearGradient id="g{$id}" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="{$from}"/>
                        <stop offset="100%" stop-color="{$to}"/>
                    </linearGradient>
                </defs>
                <rect width="96" height="96" rx="20" fill="url(#g{$id})"/>
                <text x="48" y="56" text-anchor="middle" font-family="Inter, Segoe UI, sans-serif"
                      font-size="26" font-weight="800" fill="#FFFFFF">{$this->escape($label)}</text>
            </svg>
            SVG;
    }

    private function heroSlide(string $slug, string $headline, string $subline): string
    {
        [$from, $to] = $this->colours($slug);
        $id = Str::slug($slug) . '-hero';

        return <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 500" width="1600" height="500" role="img" aria-label="{$this->escape($headline)}">
                <defs>
                    <linearGradient id="g{$id}" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="{$from}"/>
                        <stop offset="100%" stop-color="{$to}"/>
                    </linearGradient>
                    <radialGradient id="s{$id}" cx="0.78" cy="0.25" r="0.6">
                        <stop offset="0%" stop-color="#ffffff" stop-opacity="0.20"/>
                        <stop offset="100%" stop-color="#ffffff" stop-opacity="0"/>
                    </radialGradient>
                </defs>
                <rect width="1600" height="500" fill="url(#g{$id})"/>
                <rect width="1600" height="500" fill="url(#s{$id})"/>
                <text x="90" y="238" font-family="Inter, Segoe UI, sans-serif" font-size="66" font-weight="800" fill="#FFFFFF">{$this->escape($headline)}</text>
                <text x="92" y="296" font-family="Inter, Segoe UI, sans-serif" font-size="28" font-weight="500" fill="#FFFFFF" fill-opacity="0.82">{$this->escape($subline)}</text>
            </svg>
            SVG;
    }

    /** @param  list<string>  $lines */
    private function textBlock(array $lines, float $x, float $y, int $size): string
    {
        $lineHeight = $size * 1.2;
        $top = $y - (((count($lines) - 1) * $lineHeight) / 2) + ($size * 0.34);

        return collect($lines)
            ->map(fn (string $line, int $i) => sprintf(
                '<text x="%.1f" y="%.1f" text-anchor="middle" font-family="Inter, Segoe UI, sans-serif" font-size="%d" font-weight="800" fill="#FFFFFF">%s</text>',
                $x,
                $top + ($i * $lineHeight),
                $size,
                $this->escape($line),
            ))
            ->implode("\n                ");
    }

    /** @return list<string> */
    private function wrapLines(string $text, int $perLine): array
    {
        return array_values(array_filter(explode("\n", wordwrap($text, $perLine, "\n", true))));
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES);
    }
}
