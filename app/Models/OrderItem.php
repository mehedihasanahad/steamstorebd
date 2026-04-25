<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'gift_card_id',
        'quantity',
        'unit_price_bdt',
        'subtotal_bdt',
    ];

    protected function casts(): array
    {
        return [
            'unit_price_bdt' => 'decimal:2',
            'subtotal_bdt' => 'decimal:2',
            'quantity' => 'integer',
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
}
