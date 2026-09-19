<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    /** Paid for, waiting on an admin to top up an account or send credentials. */
    public const FULFILMENT_AWAITING = 'awaiting';

    /** An admin has delivered it. */
    public const FULFILMENT_FULFILLED = 'fulfilled';

    protected $fillable = [
        'order_id',
        'gift_card_id',
        'quantity',
        'unit_price_bdt',
        'buy_price_bdt',
        'subtotal_bdt',
        'fulfilment_status',
        'buyer_inputs',
        'delivered_payload',
        'fulfilled_at',
    ];

    protected function casts(): array
    {
        return [
            'unit_price_bdt' => 'decimal:2',
            'buy_price_bdt' => 'decimal:2',
            'subtotal_bdt' => 'decimal:2',
            'quantity' => 'integer',
            'buyer_inputs' => 'array',
            // Account credentials are materially more sensitive than a spent
            // gift card code, so they never sit in the database in plaintext.
            'delivered_payload' => 'encrypted',
            'fulfilled_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }

    public function orderItemCodes(): HasMany
    {
        return $this->hasMany(OrderItemCode::class);
    }

    public function giftCardCodes(): HasMany
    {
        return $this->hasMany(GiftCardCode::class);
    }

    /**
     * Does this line need a person to do something?
     *
     * A null status means the line is delivered from the code pool, which is
     * what every line written before manual fulfilment existed is.
     */
    public function needsFulfilment(): bool
    {
        return $this->fulfilment_status === self::FULFILMENT_AWAITING;
    }

    public function isFulfilled(): bool
    {
        return $this->fulfilment_status === self::FULFILMENT_FULFILLED;
    }

    /** True for any line an admin has to handle, whether or not it is done. */
    public function isManual(): bool
    {
        return $this->fulfilment_status !== null;
    }

    /**
     * What to call this line in an e-mail or on the order page. Falls back
     * through the product to the card, so nothing is ever labelled with a
     * hardcoded product name it may not be.
     */
    public function deliveryLabel(): string
    {
        return $this->giftCard?->category?->name
            ?? $this->giftCard?->name
            ?? 'Digital item';
    }

    /** Lines still waiting on an admin, oldest order first. */
    public function scopeAwaitingFulfilment(Builder $query): Builder
    {
        return $query->where('fulfilment_status', self::FULFILMENT_AWAITING);
    }
}
