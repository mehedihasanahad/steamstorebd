<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class MainCategory extends Model
{
    protected $fillable = [
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
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function giftCardCategories(): HasMany
    {
        return $this->hasMany(GiftCardCategory::class, 'main_category_id');
    }

    protected static function booted(): void
    {
        static::saved(fn() => Cache::forget('home_main_categories'));
        static::deleted(fn() => Cache::forget('home_main_categories'));
    }
}
