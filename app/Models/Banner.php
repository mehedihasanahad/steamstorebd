<?php

namespace App\Models;

use App\Services\StorefrontCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A slide in the homepage hero carousel.
 *
 * Visibility is the conjunction of the active flag and an optional date
 * window, so a campaign can be loaded ahead of time and retire itself.
 */
class Banner extends Model
{
    protected $fillable = [
        'title',
        'image',
        'mobile_image',
        'link_url',
        'alt_text',
        'sort_order',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
            'starts_at'  => 'datetime',
            'ends_at'    => 'datetime',
        ];
    }

    /** Active, and inside its date window if it has one. */
    public function scopeVisible(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /** The phone artwork when one was uploaded, otherwise the desktop one. */
    public function imageForMobile(): string
    {
        return $this->mobile_image ?: $this->image;
    }

    public function isVisible(): bool
    {
        return $this->is_active
            && ($this->starts_at === null || $this->starts_at->isPast())
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    protected static function booted(): void
    {
        static::saved(fn () => StorefrontCatalog::flush());
        static::deleted(fn () => StorefrontCatalog::flush());
    }
}
