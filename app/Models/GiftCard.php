<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiftCard extends Model
{
    /** One pre-stocked code per unit: gift cards and software licence keys. */
    public const FULFILMENT_CODE_POOL = 'code_pool';

    /** An admin fulfils it by hand — game top-ups credited to a Player ID. */
    public const FULFILMENT_MANUAL = 'manual';

    /** An admin hands over account credentials — shared subscriptions. */
    public const FULFILMENT_CREDENTIALS = 'credentials';

    /** @var array<string, string> */
    public const FULFILMENT_TYPES = [
        self::FULFILMENT_CODE_POOL   => 'Code pool (instant, from stocked codes)',
        self::FULFILMENT_MANUAL      => 'Manual (admin fulfils after payment)',
        self::FULFILMENT_CREDENTIALS => 'Credentials (admin sends account details)',
    ];

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'denomination',
        'denomination_currency',
        'denomination_bdt',
        'buy_price_bdt',
        'price_bdt',
        'description',
        'badge_text',
        'image',
        'stock_count',
        'is_active',
        'sort_order',
        'fulfilment_type',
        'manual_stock',
        'delivery_eta_label',
    ];

    protected function casts(): array
    {
        return [
            'denomination' => 'decimal:2',
            'denomination_bdt' => 'decimal:2',
            'buy_price_bdt' => 'decimal:2',
            'price_bdt' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'stock_count' => 'integer',
            'manual_stock' => 'integer',
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

    /** Is this card delivered from the pre-stocked code pool? */
    public function usesCodePool(): bool
    {
        return $this->fulfilment_type === self::FULFILMENT_CODE_POOL;
    }

    /** Does an admin have to do something before the buyer gets this? */
    public function needsManualFulfilment(): bool
    {
        return ! $this->usesCodePool();
    }

    public function availableCodesCount(): int
    {
        return $this->codes()->available()->count();
    }

    public function getStockCountAttribute(): int
    {
        // Cards an admin fulfils by hand have no code pool to count, so their
        // stock is the number the admin typed in.
        if ($this->needsManualFulfilment()) {
            return (int) ($this->attributes['manual_stock'] ?? 0);
        }

        // Storefront listings load the count up front with withAvailableCodesCount(),
        // so reading stock there doesn't run a COUNT query on every read.
        if (array_key_exists('available_codes_count', $this->attributes)) {
            return (int) $this->attributes['available_codes_count'];
        }

        return $this->codes()->available()->count();
    }

    /** The column that actually holds this card's stock, for reads and writes. */
    public function stockColumn(): string
    {
        return $this->usesCodePool() ? 'stock_count' : 'manual_stock';
    }

    public function scopeWithAvailableCodesCount($query)
    {
        return $query->withCount(['codes as available_codes_count' => fn ($q) => $q->available()]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Cards with something left to sell.
     *
     * Both branches read a stored column, never the accessor — a scope runs in
     * SQL. Manual cards would be invisible here if this only checked
     * stock_count, so a top-up would never appear in an in-stock listing.
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where(fn (Builder $pool) => $pool
                ->where('fulfilment_type', self::FULFILMENT_CODE_POOL)
                ->where('stock_count', '>', 0))
            ->orWhere(fn (Builder $manual) => $manual
                ->where('fulfilment_type', '!=', self::FULFILMENT_CODE_POOL)
                ->where('manual_stock', '>', 0)));
    }

    /** Cards at or below `$threshold`, whichever column holds their stock. */
    public function scopeLowStock(Builder $query, int $threshold): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where(fn (Builder $pool) => $pool
                ->where('fulfilment_type', self::FULFILMENT_CODE_POOL)
                ->where('stock_count', '<', $threshold))
            ->orWhere(fn (Builder $manual) => $manual
                ->where('fulfilment_type', '!=', self::FULFILMENT_CODE_POOL)
                ->where('manual_stock', '<', $threshold)));
    }
}
