<?php

namespace App\Models;

use App\Services\StorefrontCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiftCardCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'long_description',
        'buyer_input_fields',
        'seo_title',
        'seo_description',
        'icon',
        'image',
        'sort_order',
        'is_active',
        'main_category_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active'          => 'boolean',
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
