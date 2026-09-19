<?php

namespace Database\Seeders;

use App\Models\CatalogSection;
use Illuminate\Database\Seeder;

/**
 * The four launch sections. Idempotent on slug, so re-running never
 * duplicates a section or overwrites copy an admin has edited.
 *
 * "Gift Cards" is also created by the 2026_09_19 backfill migration, because
 * the migration must not depend on a seeder having been run. This seeder fills
 * in the presentation fields the migration leaves minimal.
 */
class CatalogSectionSeeder extends Seeder
{
    /** @var list<array<string, mixed>> */
    private const SECTIONS = [
        [
            'name'         => 'Gift Cards',
            'slug'         => 'gift-cards',
            'tagline'      => 'Steam, Google Play, App Store and more — delivered instantly',
            'icon'         => '🎁',
            'accent_color' => '#2563EB',
            'sort_order'   => 0,
        ],
        [
            'name'         => 'Software',
            'slug'         => 'software',
            'tagline'      => 'Genuine licence keys for Windows, Office and antivirus',
            'icon'         => '💻',
            'accent_color' => '#0EA5E9',
            'sort_order'   => 1,
        ],
        [
            'name'         => 'Subscriptions',
            'slug'         => 'subscriptions',
            'tagline'      => 'Netflix, Spotify, YouTube Premium and more at BDT prices',
            'icon'         => '📺',
            'accent_color' => '#A855F7',
            'sort_order'   => 2,
        ],
        [
            'name'         => 'Game Top-Up',
            'slug'         => 'game-top-up',
            'tagline'      => 'PUBG UC, Free Fire Diamonds and in-game currency, topped up fast',
            'icon'         => '🎮',
            'accent_color' => '#22C55E',
            'sort_order'   => 3,
        ],
    ];

    public function run(): void
    {
        foreach (self::SECTIONS as $section) {
            CatalogSection::firstOrCreate(
                ['slug' => $section['slug']],
                $section + ['is_active' => true],
            );
        }
    }
}
