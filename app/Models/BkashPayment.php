<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BkashPayment extends Model
{
    protected $fillable = [
        'order_id',
        'payment_id',
        'trx_id',
        'amount',
        'currency',
        'status',
        'bkash_response',
    ];

    protected function casts(): array
    {
        return [
            'bkash_response' => 'array',
            'amount' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
