<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiftCard extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'denomination_usd',
        'denomination_bdt',
        'price_bdt',
        'description',
        'badge_text',
        'image',
        'stock_count',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'denomination_usd' => 'decimal:2',
            'denomination_bdt' => 'decimal:2',
            'price_bdt' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'stock_count' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GiftCardCategory::class, 'category_id');
    }

    public function codes(): HasMany
    {
        return $this->hasMany(GiftCardCode::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function availableCodesCount(): int
    {
        return $this->codes()->available()->count();
    }

    public function getStockCountAttribute(): int
    {
        return $this->codes()->available()->count();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_count', '>', 0);
    }
}
