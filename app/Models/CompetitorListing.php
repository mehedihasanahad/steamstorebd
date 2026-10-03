<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The competitor page a gift card is priced against.
 *
 * An admin pastes the URL once; the nightly check re-reads it from then on.
 * Whether it is actually read is the card's decision -- see
 * GiftCard::$price_watch_enabled -- so there is no second switch here.
 */
class CompetitorListing extends Model
{
    public const PROVIDER_G2A = 'g2a';

    protected $attributes = [
        'provider' => self::PROVIDER_G2A,
    ];

    protected $fillable = [
        'gift_card_id',
        'provider',
        'url',
        'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'last_checked_at' => 'datetime',
        ];
    }

    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(CompetitorPriceCheck::class);
    }

    /** The provider keys this installation knows how to read. */
    public static function providerOptions(): array
    {
        return collect(array_keys(config('competitor.providers', [])))
            ->mapWithKeys(fn (string $key) => [$key => strtoupper($key)])
            ->all();
    }

    /**
     * Listings the nightly run should actually fetch: the card has to be
     * active, still for sale, and opted in to watching.
     *
     * Qualified column names because the runner applies this inside a join
     * against `gift_cards`, which carries an `is_active` of its own.
     */
    public function scopeWatchable(Builder $query): Builder
    {
        return $query->whereHas('giftCard', fn (Builder $card) => $card
            ->where('gift_cards.is_active', true)
            ->where('gift_cards.price_watch_enabled', true));
    }
}
