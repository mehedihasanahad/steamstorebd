<?php

namespace App\Models;

use App\Services\StorefrontCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class MainCategory extends Model
{
    protected $fillable = [
        'catalog_section_id',
        'name',
        'slug',
        'description',
        'icon',
        'image',
        'sort_order',
        'is_active',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'seo_content',
        'how_to_redeem',
    ];

    protected function casts(): array
    {
        return [
            'is_active'           => 'boolean',
            'sort_order'          => 'integer',
            'catalog_section_id'  => 'integer',
        ];
    }

    public function catalogSection(): BelongsTo
    {
        return $this->belongsTo(CatalogSection::class);
    }

    public function giftCardCategories(): HasMany
    {
        return $this->hasMany(GiftCardCategory::class, 'main_category_id');
    }

    protected static function booted(): void
    {
        static::saved(fn() => Cache::forget(StorefrontCatalog::BRANDS_CACHE_KEY));
        static::deleted(fn() => Cache::forget(StorefrontCatalog::BRANDS_CACHE_KEY));
        static::updated(function (MainCategory $brand) {
            if ($brand->wasChanged('slug')) {
                SlugRedirect::rememberSlugChange($brand);
            }
        });
    }
}
