<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemCode extends Model
{
    protected $fillable = [
        'order_item_id',
        'gift_card_code_id',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function giftCardCode(): BelongsTo
    {
        return $this->belongsTo(GiftCardCode::class);
    }
}
