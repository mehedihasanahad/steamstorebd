<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favourite extends Model
{
    protected $fillable = [
        'user_id',
        'gift_card_category_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function giftCardCategory(): BelongsTo
    {
        return $this->belongsTo(GiftCardCategory::class);
    }
}
