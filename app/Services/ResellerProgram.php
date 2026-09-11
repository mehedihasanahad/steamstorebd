<?php

namespace App\Services;

use App\Models\MainCategory;
use App\Models\ResellerApplication;
use Illuminate\Support\Facades\Cache;

/**
 * The only place that reads the reseller program's site settings. Everything
 * the landing page and the application form need comes from here, so the
 * admin-configurable copy has exactly one source of truth.
 */
class ResellerProgram
{
    public const DEFAULT_BENEFITS = [
        ['icon' => '💰', 'title' => 'Wholesale Pricing', 'description' => 'Approved resellers get dedicated bulk pricing on every brand we stock — the more you move, the better your rate.'],
        ['icon' => '⚡', 'title' => 'Priority Delivery', 'description' => 'Reseller orders jump the queue. Codes are delivered first so you never keep your own customer waiting.'],
        ['icon' => '📦', 'title' => 'Bulk Stock Access', 'description' => 'Need 50 codes at once? Tell us ahead of time and we reserve the stock for you.'],
        ['icon' => '🤝', 'title' => 'Dedicated Support', 'description' => 'A direct WhatsApp line to our team — no tickets, no queue, straight to a human who knows your account.'],
        ['icon' => '🛡️', 'title' => '100% Genuine Codes', 'description' => 'Every code is sourced from authorised channels. Sell with confidence and keep your own reputation clean.'],
        ['icon' => '📈', 'title' => 'Grow With Us', 'description' => 'As your volume grows we unlock better rates, longer stock holds and early access to new brands.'],
    ];

    public function __construct(
        protected bool $enabled = false,
        protected string $heroTitle = '',
        protected string $heroSubtitle = '',
        protected string $benefitsRaw = '',
        protected string $responseTime = '',
    ) {}

    public static function fromSettings(): self
    {
        return new self(
            (bool) site_setting('reseller_program_enabled', false),
            (string) site_setting('reseller_hero_title', ''),
            (string) site_setting('reseller_hero_subtitle', ''),
            (string) site_setting('reseller_benefits', ''),
            (string) site_setting('reseller_response_time', ''),
        );
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function heroTitle(): string
    {
        return trim($this->heroTitle) !== '' ? trim($this->heroTitle) : 'Sell Gift Cards. Earn More.';
    }

    public function heroSubtitle(): string
    {
        return trim($this->heroSubtitle) !== ''
            ? trim($this->heroSubtitle)
            : 'Join our reseller network and get wholesale pricing, priority delivery and bulk stock for your own customers.';
    }

    public function responseTime(): string
    {
        return trim($this->responseTime) !== '' ? trim($this->responseTime) : 'within 24 hours';
    }

    /**
     * @return array<int, array{icon: string, title: string, description: string}>
     */
    public function benefits(): array
    {
        $decoded = json_decode($this->benefitsRaw, true);

        if (! is_array($decoded)) {
            return self::DEFAULT_BENEFITS;
        }

        $benefits = [];

        foreach ($decoded as $benefit) {
            $title = trim((string) ($benefit['title'] ?? ''));

            if ($title === '') {
                continue;
            }

            $benefits[] = [
                'icon' => trim((string) ($benefit['icon'] ?? '')) ?: '★',
                'title' => $title,
                'description' => trim((string) ($benefit['description'] ?? '')),
            ];
        }

        return $benefits !== [] ? $benefits : self::DEFAULT_BENEFITS;
    }

    /**
     * Brand options for the form, keyed by slug so the submitted value is URL
     * safe while the label stays human readable.
     *
     * @return array<string, string>
     */
    public function giftCardTypeOptions(): array
    {
        $brands = Cache::remember('reseller_gift_card_type_options', 300, function () {
            return MainCategory::where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('name', 'slug')
                ->all();
        });

        if ($brands === []) {
            $brands = ['steam' => 'Steam'];
        }

        return $brands + ['other' => 'Other'];
    }

    /**
     * @return array<string, string>
     */
    public function platformOptions(): array
    {
        return ResellerApplication::PLATFORMS;
    }
}
