<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderEdit extends Model
{
    protected $fillable = [
        'order_id',
        'admin_id',
        'order_status',
        'subtotal_before_bdt',
        'subtotal_after_bdt',
        'total_before_bdt',
        'total_after_bdt',
        'balance_delta_bdt',
        'wallet_refunded_bdt',
        'items_before',
        'items_after',
        'code_changes',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_before_bdt' => 'decimal:2',
            'subtotal_after_bdt'  => 'decimal:2',
            'total_before_bdt'    => 'decimal:2',
            'total_after_bdt'     => 'decimal:2',
            'balance_delta_bdt'   => 'decimal:2',
            'wallet_refunded_bdt' => 'decimal:2',
            'items_before'        => 'array',
            'items_after'         => 'array',
            'code_changes'        => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id')->withDefault(['name' => 'System']);
    }

    /** The customer still owes money on this edit. */
    public function customerOwes(): bool
    {
        return (float) $this->balance_delta_bdt > 0;
    }

    /** The edit shrank the order below what was already collected. */
    public function refundDue(): bool
    {
        return (float) $this->balance_delta_bdt < 0;
    }

    /**
     * @return array<int, int>
     */
    public function codeIds(string $bucket): array
    {
        return $this->code_changes[$bucket] ?? [];
    }
}
