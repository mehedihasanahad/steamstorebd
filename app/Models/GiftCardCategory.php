<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiftCardCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'image',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function giftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class, 'category_id');
    }

    public function activeGiftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class, 'category_id')->where('is_active', true);
    }
}
