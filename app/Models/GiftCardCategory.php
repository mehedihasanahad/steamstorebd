<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class GiftCardCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'long_description',
        'icon',
        'image',
        'sort_order',
        'is_active',
        'main_category_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active'        => 'boolean',
            'sort_order'       => 'integer',
            'main_category_id' => 'integer',
        ];
    }

    public function mainCategory(): BelongsTo
    {
        return $this->belongsTo(MainCategory::class, 'main_category_id');
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
        static::saved(fn() => Cache::forget('home_main_categories'));
        static::deleted(fn() => Cache::forget('home_main_categories'));
    }
}
