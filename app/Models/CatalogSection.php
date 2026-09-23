<?php

namespace App\Models;

use App\Services\StorefrontCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The top level of the catalog: Gift Cards, Software, Subscriptions, Game
 * Top-Up. Each section holds many brands (MainCategory), each brand many
 * products (GiftCardCategory), each product many cards (GiftCard).
 *
 * Deliberately shaped like MainCategory — same fillable style, same casts,
 * same cache and slug-redirect hooks — so the two read the same way.
 */
class CatalogSection extends Model
{
    /** The section's brands sit in one row the reader scrolls sideways. */
    public const DISPLAY_SLIDER = 'slider';

    /** The section's brands wrap onto as many rows as they need. */
    public const DISPLAY_GRID = 'grid';

    /** @var array<string, string> */
    public const DISPLAY_MODES = [
        self::DISPLAY_SLIDER => 'Slider — one row, scrolled sideways',
        self::DISPLAY_GRID   => 'Grid — wraps onto as many rows as it needs',
    ];

    protected $fillable = [
        'name',
        'slug',
        'tagline',
        'description',
        'icon',
        'image',
        'accent_color',
        'sort_order',
        'display_mode',
        'is_active',
        'seo_title',
        'seo_description',
        'seo_content',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Whether the homepage should wrap this section's brands instead of
     * scrolling them. Null for a row cached before the column existed, which
     * reads as the slider every section had until then.
     */
    public function isGrid(): bool
    {
        return $this->display_mode === self::DISPLAY_GRID;
    }

    public function mainCategories(): HasMany
    {
        return $this->hasMany(MainCategory::class);
    }

    public function activeMainCategories(): HasMany
    {
        return $this->mainCategories()->where('is_active', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    protected static function booted(): void
    {
        static::saved(fn () => StorefrontCatalog::flush());
        static::deleted(fn () => StorefrontCatalog::flush());
        static::updated(function (CatalogSection $section) {
            if ($section->wasChanged('slug')) {
                SlugRedirect::rememberSlugChange($section);
            }
        });
    }
}
