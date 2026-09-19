<?php

namespace App\Models;

use App\Services\StorefrontCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

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
    protected $fillable = [
        'name',
        'slug',
        'tagline',
        'description',
        'icon',
        'image',
        'accent_color',
        'sort_order',
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
        static::saved(fn () => Cache::forget(StorefrontCatalog::BRANDS_CACHE_KEY));
        static::deleted(fn () => Cache::forget(StorefrontCatalog::BRANDS_CACHE_KEY));
        static::updated(function (CatalogSection $section) {
            if ($section->wasChanged('slug')) {
                SlugRedirect::rememberSlugChange($section);
            }
        });
    }
}
