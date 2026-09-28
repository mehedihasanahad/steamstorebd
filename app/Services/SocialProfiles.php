<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Collection;

/**
 * The profiles this shop can be found on elsewhere.
 *
 * These exist for one reason: our own testimonials prove nothing to a search
 * engine, because we wrote them. `sameAs` is how the site says "and here is
 * the same business on Google, on Facebook, on Trustpilot" — places whose
 * ratings were never ours to edit. Without it a crawler that lands here has
 * no route to the reviews that actually count, and no way to tell that the
 * shop it is reading and the shop those reviews describe are one business.
 */
class SocialProfiles
{
    /**
     * Setting key => the label a link to it carries.
     *
     * The order is deliberate and is the order `sameAs` emits: Google Business
     * and Facebook are the two profiles that carry a visible review count in
     * Bangladesh, so they lead.
     */
    public const PROFILES = [
        'social_google_business_url' => 'Google',
        'social_facebook_url'        => 'Facebook',
        'social_trustpilot_url'      => 'Trustpilot',
        'social_youtube_url'         => 'YouTube',
        'social_instagram_url'       => 'Instagram',
        'social_linkedin_url'        => 'LinkedIn',
    ];

    /** @param  array<string, string>  $urls */
    private function __construct(private readonly array $urls)
    {
    }

    public static function fromSettings(): self
    {
        $urls = [];

        foreach (array_keys(self::PROFILES) as $key) {
            $url = trim((string) SiteSetting::get($key, ''));

            // A half-typed URL in `sameAs` is worse than no URL: it tells a
            // crawler the business claims an identity that does not resolve.
            if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL) !== false) {
                $urls[$key] = $url;
            }
        }

        // The Messenger page username predates these fields and is the only
        // Facebook link most installs have. It fills the Facebook slot rather
        // than adding a second, near-identical entry beside it.
        if (! isset($urls['social_facebook_url'])) {
            $page = trim((string) (SiteSetting::get('messenger_page_username') ?: SiteSetting::get('product_chat_messenger_username', '')));

            if ($page !== '') {
                $urls['social_facebook_url'] = 'https://www.facebook.com/' . $page;
            }
        }

        return new self($urls);
    }

    /**
     * The URLs for schema.org `sameAs`, in the order PROFILES declares them.
     *
     * @return array<int, string>
     */
    public function sameAs(): array
    {
        return $this->ordered()->pluck('url')->all();
    }

    /**
     * @return Collection<int, array{key: string, label: string, url: string}>
     */
    public function links(): Collection
    {
        return $this->ordered();
    }

    public function isEmpty(): bool
    {
        return $this->urls === [];
    }

    /**
     * @return Collection<int, array{key: string, label: string, url: string}>
     */
    private function ordered(): Collection
    {
        return collect(self::PROFILES)
            ->filter(fn (string $label, string $key) => isset($this->urls[$key]))
            ->map(fn (string $label, string $key) => [
                'key'   => $key,
                'label' => $label,
                'url'   => $this->urls[$key],
            ])
            ->values();
    }
}
