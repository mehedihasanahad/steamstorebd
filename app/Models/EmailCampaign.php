<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailCampaign extends Model
{
    /** Where the recipients come from. */
    public const AUDIENCE_BUYERS    = 'buyers';
    public const AUDIENCE_RESELLERS = 'resellers';
    public const AUDIENCE_MANUAL    = 'manual';

    /**
     * draft     — being written, nothing resolved yet
     * scheduled — waiting for its time; the scheduler picks it up
     * sending   — recipients are frozen and the queue is working through them
     * sent      — every recipient reached a final state
     * cancelled — stopped by hand; whatever had not gone out never will
     */
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_SENDING   = 'sending';
    public const STATUS_SENT      = 'sent';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'name',
        'subject',
        'preheader',
        'body',
        'cta_label',
        'cta_url',
        'audience',
        'filters',
        'manual_recipients',
        'status',
        'scheduled_for',
        'started_at',
        'completed_at',
        'total_recipients',
        'sent_count',
        'failed_count',
        'created_by_admin_id',
    ];

    /**
     * The same defaults the table carries, so a campaign that has been created
     * but not read back still knows it is a draft. Without these, anything
     * asking `isDraft()` of a fresh model gets false from a null column.
     */
    protected $attributes = [
        'status'           => self::STATUS_DRAFT,
        'audience'         => self::AUDIENCE_BUYERS,
        'total_recipients' => 0,
        'sent_count'       => 0,
        'failed_count'     => 0,
    ];

    protected function casts(): array
    {
        return [
            'filters'          => 'array',
            'scheduled_for'    => 'datetime',
            'started_at'       => 'datetime',
            'completed_at'     => 'datetime',
            'total_recipients' => 'integer',
            'sent_count'       => 'integer',
            'failed_count'     => 'integer',
        ];
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(EmailCampaignRecipient::class);
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    public function scopeDue($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now());
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function isSending(): bool
    {
        return $this->status === self::STATUS_SENDING;
    }

    /**
     * A campaign stops being editable the moment its recipients are frozen —
     * after that the subject line in the admin would no longer be the subject
     * line people received.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED], true);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_CANCELLED], true);
    }

    public function usesFilters(): bool
    {
        return $this->audience !== self::AUDIENCE_MANUAL;
    }

    public function audienceLabel(): string
    {
        return match ($this->audience) {
            self::AUDIENCE_BUYERS    => 'Buyers',
            self::AUDIENCE_RESELLERS => 'Approved resellers',
            self::AUDIENCE_MANUAL    => 'Pasted list',
            default                  => $this->audience,
        };
    }

    /** How far through the send it is, 0–100. */
    public function progress(): int
    {
        if ($this->total_recipients < 1) {
            return $this->isFinished() ? 100 : 0;
        }

        return (int) floor((($this->sent_count + $this->failed_count) / $this->total_recipients) * 100);
    }
}
