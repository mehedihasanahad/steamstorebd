<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralUsage extends Model
{
    protected $fillable = [
        'referrer_id',
        'referee_id',
        'order_id',
        'discount_given',
        'owner_reward',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'discount_given' => 'decimal:2',
            'owner_reward'   => 'decimal:2',
        ];
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
