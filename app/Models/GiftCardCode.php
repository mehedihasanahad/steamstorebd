<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GiftCardCode extends Model
{
    protected $fillable = [
        'gift_card_id',
        'code',
        'status',
        'order_item_id',
        'added_by_admin_id',
    ];

    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class)->withDefault();
    }

    public function orderItemCode(): HasOne
    {
        return $this->hasOne(OrderItemCode::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by_admin_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeReserved($query)
    {
        return $query->where('status', 'reserved');
    }

    public function scopeSold($query)
    {
        return $query->where('status', 'sold');
    }
}
