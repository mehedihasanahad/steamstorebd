<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The suppression list.
 *
 * Keyed by address rather than tied to a customer: most buyers check out as
 * guests and have no account to hang a preference on. Anything that resolves
 * an audience subtracts this list, and the send itself checks again — someone
 * may unsubscribe while the campaign is still working through the queue.
 */
class EmailUnsubscribe extends Model
{
    protected $fillable = [
        'email',
        'email_campaign_id',
        'reason',
        'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return ['unsubscribed_at' => 'datetime'];
    }

    public static function normalise(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public static function has(string $email): bool
    {
        return static::where('email', static::normalise($email))->exists();
    }

    public static function add(string $email, ?int $campaignId = null, ?string $reason = null): self
    {
        return static::firstOrCreate(
            ['email' => static::normalise($email)],
            [
                'email_campaign_id' => $campaignId,
                'reason'            => $reason,
                'unsubscribed_at'   => now(),
            ],
        );
    }
}
