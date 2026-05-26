<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'reviewer_name',
        'rating',
        'comment',
        'platform',
        'screenshot_path',
        'status',
        'is_verified_purchase',
        'source',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_verified_purchase' => 'boolean',
            'rating' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class)->withDefault();
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function displayName(): string
    {
        if ($this->reviewer_name) {
            return $this->reviewer_name;
        }
        if ($this->user_id && $this->user->name) {
            return $this->user->name;
        }
        return 'Anonymous';
    }

    public function platformLabel(): string
    {
        return match ($this->platform) {
            'whatsapp' => 'WhatsApp',
            'messenger' => 'Messenger',
            'steam' => 'Steam',
            default => 'Website',
        };
    }

    public function platformColor(): string
    {
        return match ($this->platform) {
            'whatsapp' => '#25D366',
            'messenger' => '#0099FF',
            'steam' => '#1b2838',
            default => '#2563EB',
        };
    }
}
