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

    /**
     * Mirror the schema defaults, so a card built in memory behaves the same
     * as one read back from the database. Without this a freshly created
     * model reports no fulfilment type and no purchase limits until it is
     * refreshed, and the order pipeline branches on exactly those.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'fulfilment_type' => self::FULFILMENT_CODE_POOL,
        'manual_stock'    => 0,
        'min_quantity'    => 1,
        'max_quantity'    => 10,
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
        'compare_at_price_bdt',
        'min_quantity',
        'max_quantity',
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
            'compare_at_price_bdt' => 'decimal:2',
            'min_quantity' => 'integer',
            'max_quantity' => 'integer',
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

    /**
     * Is this card delivered from the pre-stocked code pool?
     *
     * An absent value reads as code_pool, which is both the column default and
     * what every row written before manual fulfilment existed is. Treating
     * null as "manual" would quietly route a gift card into the admin queue.
     */
    public function usesCodePool(): bool
    {
        return ($this->fulfilment_type ?? self::FULFILMENT_CODE_POOL) === self::FULFILMENT_CODE_POOL;
    }

    /** Does an admin have to do something before the buyer gets this? */
    public function needsManualFulfilment(): bool
    {
        return ! $this->usesCodePool();
    }

    /**
     * Is this card discounted? Derived from the two prices rather than a flag,
     * so a deal can never be advertised after its price has moved back.
     */
    public function isDeal(): bool
    {
        return $this->compare_at_price_bdt !== null
            && (float) $this->compare_at_price_bdt > (float) $this->price_bdt;
    }

    /** Whole percent off, or null when this card is not a deal. */
    public function discountPercent(): ?int
    {
        if (! $this->isDeal()) {
            return null;
        }

        $was = (float) $this->compare_at_price_bdt;

        return (int) round((($was - (float) $this->price_bdt) / $was) * 100);
    }

    /** Taka saved, or null when this card is not a deal. */
    public function discountAmount(): ?float
    {
        return $this->isDeal()
            ? (float) $this->compare_at_price_bdt - (float) $this->price_bdt
            : null;
    }

    /**
     * The most a buyer may take in one order: their own cap, never more than
     * what is actually in stock.
     */
    public function maxOrderableQuantity(): int
    {
        return max(0, min((int) ($this->max_quantity ?? 10), $this->stock_count));
    }

    /**
     * Cards with a compare-at price above their selling price.
     *
     * Column names are qualified because this scope is also used inside a
     * subquery beside three other tables that carry price-like columns of
     * their own.
     */
    public function scopeDeals(Builder $query): Builder
    {
        return $query->whereNotNull($query->qualifyColumn('compare_at_price_bdt'))
            ->whereColumn(
                $query->qualifyColumn('compare_at_price_bdt'),
                '>',
                $query->qualifyColumn('price_bdt'),
            );
    }

    /**
     * Deepest discount first — the share taken off, not the taka saved, so a
     * 50% cut on a small card still leads a 5% cut on an expensive one.
     *
     * Only meaningful together with deals(): that scope is what guarantees a
     * compare-at price above zero for the division below.
     *
     * The saving is multiplied by 1.0 before it is divided. Both prices are
     * whole taka in practice, and SQLite divides two integers into an integer
     * — every discount came out as zero and the order fell through to the
     * tie-break.
     */
    public function scopeOrderByDiscount(Builder $query): Builder
    {
        $was   = $query->qualifyColumn('compare_at_price_bdt');
        $price = $query->qualifyColumn('price_bdt');

        return $query
            ->orderByRaw("(({$was} - {$price}) * 1.0 / {$was}) desc")
            ->orderByRaw("({$was} - {$price}) desc")
            ->orderBy($query->qualifyColumn('sort_order'))
            ->orderByDesc($query->qualifyColumn('id'));
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
