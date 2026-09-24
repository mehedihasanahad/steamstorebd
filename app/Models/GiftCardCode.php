<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GiftCardCode extends Model
{
    /**
     * Some denominations are stocked as several smaller codes -- a 20 dollar
     * card that the supplier ships as two tens. That is still one thing to
     * sell, so it stays one row: stock, reservation and the sale are all
     * counted per row and none of that changes. The row simply holds every
     * string the buyer has to redeem, joined by this separator.
     *
     * The spaces are load-bearing. Only " + " splits; a plus inside a code
     * with no space around it is part of the code and is left alone.
     */
    public const PART_SEPARATOR = ' + ';

    private const SPLIT_PATTERN = '/\s+\+\s+/';

    protected $fillable = [
        'gift_card_id',
        'code',
        'status',
        'order_item_id',
        'added_by_admin_id',
    ];

    /**
     * Written the same way however it was typed, so that "A+B", "A + B" and
     * "A  +  B" are one code and not three.
     */
    public function setCodeAttribute(?string $value): void
    {
        $this->attributes['code'] = static::normalise((string) $value);
    }

    public static function normalise(?string $code): string
    {
        return implode(self::PART_SEPARATOR, static::split($code));
    }

    /** @return array<int,string> every string the buyer has to redeem */
    public static function split(?string $code): array
    {
        $parts = preg_split(self::SPLIT_PATTERN, trim((string) $code)) ?: [];

        return array_values(array_filter(array_map('trim', $parts), fn (string $part) => $part !== ''));
    }

    /** @return array<int,string> */
    public function parts(): array
    {
        return static::split($this->code);
    }

    public function partCount(): int
    {
        return count($this->parts());
    }

    /** True when redeeming this card takes more than one code. */
    public function isSplit(): bool
    {
        return $this->partCount() > 1;
    }

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

    /**
     * Codes pulled off an order after the customer already had them. They are
     * burned on purpose and must never be sold again.
     */
    public function scopeRevoked($query)
    {
        return $query->where('status', 'revoked');
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }
}
