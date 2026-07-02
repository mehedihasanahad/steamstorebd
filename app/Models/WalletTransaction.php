<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'source',
        'description',
        'reference_id',
        'reference_type',
        'balance_after',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            'referral_reward'   => 'Referral Reward',
            'order_payment'     => 'Used at Checkout',
            'manual_credit'     => 'Manual Credit',
            'manual_debit'      => 'Manual Debit',
            'withdrawal'        => 'Withdrawal Request',
            'withdrawal_refund' => 'Withdrawal Refund',
            default             => ucfirst(str_replace('_', ' ', $this->source)),
        };
    }
}
