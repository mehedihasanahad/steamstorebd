<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerApplication extends Model
{
    public const PLATFORMS = [
        'facebook_page' => 'Facebook Page',
        'facebook_group' => 'Facebook Group',
        'whatsapp' => 'WhatsApp / Messenger',
        'website' => 'Own Website',
        'physical_shop' => 'Physical Shop',
        'gaming_zone' => 'Gaming Zone / Cyber Cafe',
        'other' => 'Other',
    ];

    protected $fillable = [
        'application_number',
        'name',
        'email',
        'phone',
        'whatsapp_number',
        'selling_platform',
        'gift_card_types',
        'status',
        'decline_reason',
        'reviewed_by',
        'reviewed_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'gift_card_types' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function platformLabel(): string
    {
        return self::PLATFORMS[$this->selling_platform]
            ?? ucfirst(str_replace('_', ' ', (string) $this->selling_platform));
    }

    /**
     * Applications store the brand labels chosen at submission time, so the
     * record still reads correctly after a brand is renamed or removed.
     */
    public function giftCardTypesLabel(): string
    {
        return implode(', ', $this->gift_card_types ?? []);
    }

    public function whatsappLink(): string
    {
        return 'https://wa.me/'.preg_replace('/\D/', '', (string) $this->whatsapp_number);
    }

    public static function generateApplicationNumber(): string
    {
        return 'RSL'.date('Y').'-'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
