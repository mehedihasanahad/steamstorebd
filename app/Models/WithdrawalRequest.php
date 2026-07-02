<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalRequest extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'method',
        'account_type',
        'transfer_type',
        'phone_number',
        'status',
        'admin_note',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function methodLabel(): string
    {
        return match ($this->method) {
            'bkash' => 'bKash',
            'nagad' => 'Nagad',
            default => ucfirst($this->method),
        };
    }

    public function transferTypeLabel(): string
    {
        return match ($this->transfer_type) {
            'cashout'    => 'Cash Out',
            'send_money' => 'Send Money',
            default      => ucfirst(str_replace('_', ' ', $this->transfer_type)),
        };
    }
}
