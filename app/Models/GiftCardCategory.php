<?php

namespace App\Models;

use App\Services\StorefrontCatalog;
use App\Support\Region;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiftCardCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'region',
        'region_group',
        'description',
        'long_description',
        'buyer_input_fields',
        'seo_title',
        'seo_description',
        'icon',
        'image',
        'sort_order',
        'is_active',
        'is_featured',
        'featured_sort',
        'main_category_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active'          => 'boolean',
            'is_featured'        => 'boolean',
            'featured_sort'      => 'integer',
            'buyer_input_fields' => 'array',
            'sort_order'       => 'integer',
            'main_category_id' => 'integer',
        ];
    }

    public function mainCategory(): BelongsTo
    {
        return $this->belongsTo(MainCategory::class, 'main_category_id');
    }

    /**
     * What this product must ask the buyer for before it can be fulfilled.
     * Always an array, so callers never null-check.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buyerInputSchema(): array
    {
        return $this->buyer_input_fields ?? [];
    }

    public function needsBuyerInput(): bool
    {
        return $this->buyerInputSchema() !== [];
    }

    /**
     * The same product in other regions — Steam Wallet USA and Turkey when
     * this one is Hong Kong. Empty when the product declares no region group,
     * so the page simply renders no switcher.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public function regionalSiblings(): \Illuminate\Database\Eloquent\Collection
    {
        if (blank($this->region_group)) {
            return self::newCollection([]);
        }

        return self::query()
            ->where('region_group', $this->region_group)
            ->whereKeyNot($this->getKey())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function hasRegionalSiblings(): bool
    {
        return filled($this->region_group) && $this->regionalSiblings()->isNotEmpty();
    }

    public function regionName(): ?string
    {
        return Region::name($this->region);
    }

    public function regionFlag(): ?string
    {
        return Region::flag($this->region);
    }

    /** Admin-curated products for the homepage featured rail. */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
            ->where('is_active', true)
            ->orderBy('featured_sort')
            ->orderBy('name');
    }

    public function giftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class, 'category_id');
    }

    public function activeGiftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class, 'category_id')->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::saved(fn() => StorefrontCatalog::flush());
        static::deleted(fn() => StorefrontCatalog::flush());
        static::updated(function (GiftCardCategory $category) {
            if ($category->wasChanged('slug')) {
                SlugRedirect::rememberSlugChange($category);
            }
        });
    }
}
