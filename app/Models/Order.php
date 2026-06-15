<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'order_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'subtotal_bdt',
        'total_bdt',
        'status',
        'payment_method',
        'send_money_trx_id',
        'notes',
        'ip_address',
        'referral_code_used',
        'referral_discount_bdt',
        'wallet_discount_bdt',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_bdt'          => 'decimal:2',
            'total_bdt'             => 'decimal:2',
            'referral_discount_bdt' => 'decimal:2',
            'wallet_discount_bdt'   => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function bkashPayment(): HasOne
    {
        return $this->hasOne(BkashPayment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function referralUsage(): HasOne
    {
        return $this->hasOne(ReferralUsage::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['paid', 'completed']);
    }

    public function isPendingReview(): bool
    {
        return $this->status === 'pending_review';
    }

    public function isSendMoneyOrder(): bool
    {
        return in_array($this->payment_method, ['bkash_send_money', 'nagad_send_money', 'rocket_send_money']);
    }

    public function paymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            'bkash_online'     => 'bKash Online',
            'bkash_send_money' => 'bKash Send Money',
            'nagad_send_money'   => 'Nagad Send Money',
            'rocket_send_money'  => 'Rocket Send Money',
            default              => $this->payment_method,
        };
    }
}
