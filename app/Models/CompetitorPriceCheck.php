<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One night's reading of one card against one competitor.
 *
 * Rows are written whether or not the comparison was favourable, and whether
 * or not it succeeded, because "no row" is not an answer anyone can act on.
 */
class CompetitorPriceCheck extends Model
{
    public const STATUS_OK     = 'ok';
    public const STATUS_FAILED = 'failed';

    /** Why a check produced no comparable number. Shown to the admin as-is. */
    public const REASON_FETCH_FAILED    = 'Could not load the competitor page';
    public const REASON_PRICE_NOT_FOUND = 'Page loaded but carried no readable price';
    public const REASON_NO_BUY_PRICE    = 'This card has no buy price set';
    public const REASON_NO_RATE         = 'No BDT rate configured for this currency';
    public const REASON_NO_PROVIDER     = 'No reader is configured for this price source';

    protected $fillable = [
        'gift_card_id',
        'competitor_listing_id',
        'provider',
        'url',
        'checked_on',
        'status',
        'failure_reason',
        'buy_price_bdt',
        'sell_price_bdt',
        'competitor_price',
        'competitor_currency',
        'competitor_fee',
        'fx_rate',
        'competitor_price_bdt',
        'margin_bdt',
        'margin_percent',
        'is_opportunity',
    ];

    protected function casts(): array
    {
        return [
            'checked_on'           => 'date',
            'buy_price_bdt'        => 'decimal:2',
            'sell_price_bdt'       => 'decimal:2',
            'competitor_price'     => 'decimal:2',
            'competitor_fee'       => 'decimal:2',
            'fx_rate'              => 'decimal:4',
            'competitor_price_bdt' => 'decimal:2',
            'margin_bdt'           => 'decimal:2',
            'margin_percent'       => 'decimal:2',
            'is_opportunity'       => 'boolean',
        ];
    }

    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(CompetitorListing::class, 'competitor_listing_id');
    }

    public function succeeded(): bool
    {
        return $this->status === self::STATUS_OK;
    }

    /**
     * How the taka figure was arrived at: their listed price, the payment fee
     * a buyer also pays, and the rate applied to the sum.
     *
     * Shown under the converted number so it is never a figure the admin has
     * to take on trust -- the arithmetic is right there to check against the
     * page itself.
     */
    public function quotedBreakdown(): ?string
    {
        if ($this->competitor_price === null) {
            return null;
        }

        $line = number_format((float) $this->competitor_price, 2) . ' ' . $this->competitor_currency;

        if ((float) $this->competitor_fee > 0) {
            $line .= ' + ' . number_format((float) $this->competitor_fee, 2) . ' fee';
        }

        if ($this->fx_rate !== null) {
            $line .= ' @ ' . rtrim(rtrim(number_format((float) $this->fx_rate, 4, '.', ''), '0'), '.');
        }

        return $line;
    }

    public function scopeOpportunities(Builder $query): Builder
    {
        return $query->where('is_opportunity', true);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * The most recent day that was actually checked, which is not always
     * today: the run happens at night and may not have happened yet, and a
     * missed night should still leave the screen showing real numbers rather
     * than an empty table.
     */
    public static function latestCheckedOn(): ?string
    {
        $latest = static::max('checked_on');

        return $latest === null ? null : substr((string) $latest, 0, 10);
    }

    public function scopeForLatestRun(Builder $query): Builder
    {
        $latest = static::latestCheckedOn();

        return $latest === null ? $query->whereRaw('1 = 0') : $query->whereDate('checked_on', $latest);
    }
}
