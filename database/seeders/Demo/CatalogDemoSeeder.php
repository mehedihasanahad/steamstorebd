<?php

namespace Database\Seeders\Demo;

use App\Models\Banner;
use App\Models\CatalogSection;
use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\GiftCardCode;
use App\Models\MainCategory;
use App\Models\User;
use App\Services\StorefrontCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The catalog the homepage, section pages, brand pages and product pages all
 * render: five sections, the brand roster the design was drawn around, products
 * in several regions, deals, featured flags, manually-fulfilled top-ups and
 * credential-delivered subscriptions.
 *
 * Every brand, product, denomination and banner gets generated artwork, because
 * a catalog page is mostly pictures — judging the layout against a grid of grey
 * placeholders tells you nothing about whether it works.
 *
 * Shaped to exercise every branch the storefront has: a product with regional
 * siblings, one with a buyer-input schema, denominations that are sold out,
 * cards with a compare-at price, and a section with nothing to sell.
 */
class CatalogDemoSeeder extends Seeder
{
    private User $admin;

    private ArtworkFactory $artwork;

    public function run(): void
    {
        $this->admin = User::firstOrCreate(
            ['email' => 'demo-admin@steamstorebd.test'],
            ['name' => 'Demo Admin', 'password' => 'password', 'email_verified_at' => now(), 'is_admin' => true],
        );

        $this->artwork = new ArtworkFactory();

        $this->banners();

        $giftCards     = $this->section('Gift Cards', 'gift-cards', 0, 'Steam, Google Play, App Store and more — delivered instantly');
        $topUp         = $this->section('Games Top Up', 'game-top-up', 1, 'Credited straight to your game account');
        $gameKeys      = $this->section('Games Key', 'games-key', 2, 'Activate and play the same day');
        $subscriptions = $this->section('Subscriptions', 'subscriptions', 3, 'Streaming, music and productivity plans');

        $this->topUpBrands($topUp);
        $this->giftCardBrands($giftCards);
        $this->gameKeyBrands($gameKeys);
        $this->subscriptionBrands($subscriptions);

        // Left deliberately empty: a section with nothing sellable must be
        // omitted from the menu and the homepage, and its own page must say so
        // rather than render a blank grid.
        $this->section('Utility', 'utility', 4, 'Bill pay and top-up utilities');

        StorefrontCatalog::flush();
    }

    private function section(string $name, string $slug, int $sort, string $tagline): CatalogSection
    {
        return CatalogSection::updateOrCreate(['slug' => $slug], [
            'name'            => $name,
            'tagline'         => $tagline,
            'sort_order'      => $sort,
            'is_active'       => true,
            'seo_description' => 'Buy ' . $name . ' in Bangladesh with bKash or Nagad. Instant delivery, 100% genuine.',
        ]);
    }

    // ── Games top up ─────────────────────────────────────────────────────────

    private function topUpBrands(CatalogSection $section): void
    {
        $pubg = $this->brand($section, 'PUBG', 'pubg', 0);
        $uc   = $this->product($pubg, 'Pubg Mobile UC', 'pubg-uc', 'pubg', [
            'region'             => 'BD',
            'is_featured'        => true,
            'featured_sort'      => 1,
            'description'        => 'UC is credited directly to the Player ID you give at checkout. There is no code to redeem.',
            'long_description'   => '<p>Top up PUBG Mobile UC without a card. Give us your Player ID at checkout and our team credits the account directly, usually within half an hour.</p>',
            'instructions'       => '<p>There is nothing to redeem. Watch your order page — it shows the progress until the UC lands on your account.</p>',
            'buyer_input_fields' => [
                ['key' => 'player_id', 'label' => 'Player ID', 'required' => true, 'placeholder' => '5123456789', 'help' => 'Find it in-game under Profile, top-left.'],
                ['key' => 'zone_id', 'label' => 'Zone ID', 'required' => false, 'placeholder' => '1234'],
            ],
        ]);

        foreach ([[325, 633, 643, 40], [660, 950, null, 25], [1800, 2450, null, 12], [3850, 4900, null, 6]] as $i => [$amount, $price, $compareAt, $stock]) {
            $this->card($uc, "Pubg Mobile {$amount} UC", "pubg-{$amount}-uc", 'pubg', "{$amount} UC", [
                'denomination'          => $amount,
                'denomination_currency' => 'UC',
                'denomination_bdt'      => $price - 40,
                'price_bdt'             => $price,
                'compare_at_price_bdt'  => $compareAt,
                'sort_order'            => $i,
                'max_quantity'          => 3,
                'fulfilment_type'       => GiftCard::FULFILMENT_MANUAL,
                'manual_stock'          => $stock,
                'delivery_eta_label'    => '5-30 minutes',
            ]);
        }

        $freeFire = $this->brand($section, 'Free Fire', 'free-fire', 1);
        $diamonds = $this->product($freeFire, 'Free Fire Diamonds', 'free-fire-diamonds', 'free-fire', [
            'region'             => 'BD',
            'description'        => 'Diamonds credited to your Free Fire player ID.',
            'buyer_input_fields' => [
                ['key' => 'player_id', 'label' => 'Player ID', 'required' => true, 'placeholder' => '1234567890'],
            ],
        ]);

        foreach ([[310, 330, 25], [520, 540, 20], [1060, 1090, 15]] as $i => [$amount, $price, $stock]) {
            $this->card($diamonds, "Free Fire {$amount} Diamonds", "free-fire-{$amount}", 'free-fire', "{$amount}", [
                'denomination'          => $amount,
                'denomination_currency' => 'Diamonds',
                'denomination_bdt'      => $price - 30,
                'price_bdt'             => $price,
                'sort_order'            => $i,
                'fulfilment_type'       => GiftCard::FULFILMENT_MANUAL,
                'manual_stock'          => $stock,
                'delivery_eta_label'    => '5-30 minutes',
            ]);
        }

        $this->simpleTopUp($section, 'Roblox Robux', 'roblox-topup', 2, 'Roblox Robux Top Up', 'roblox-robux', [
            ['500 Robux', 'robux-500', 500, 750, 800],
            ['1000 Robux', 'robux-1000', 1000, 1440, null],
        ]);

        $this->simpleTopUp($section, 'Genshin Impact', 'genshin', 3, 'Genshin Genesis Crystals', 'genshin-crystals', [
            ['980 Crystals', 'genshin-980', 980, 1680, null],
        ]);

        $this->simpleTopUp($section, 'Mobile Legends', 'mobile-legends', 4, 'Mobile Legends Diamonds', 'mlbb-diamonds', [
            ['275 Diamonds', 'mlbb-275', 275, 590, null],
        ]);

        $this->simpleTopUp($section, 'Marvel Rivals', 'marvel-rivals', 5, 'Marvel Rivals Lattice', 'marvel-rivals-lattice', [
            ['500 Lattice', 'marvel-500', 500, 690, null],
        ]);
    }

    // ── Gift cards ───────────────────────────────────────────────────────────

    private function giftCardBrands(CatalogSection $section): void
    {
        $apple = $this->brand($section, 'Apple Itunes', 'apple', 0);
        $this->simpleProduct($apple, 'iTunes Gift Card (USA)', 'itunes-gift-card-usa', 'apple', 'US', [
            ['iTunes Gift Card 5 USD', 'itunes-usa-5', 5, 670, 680, 5],
            ['iTunes Gift Card 10 USD', 'itunes-usa-10', 10, 1190, null, 5],
            ['iTunes Gift Card 25 USD', 'itunes-usa-25', 25, 2925, null, 3],
        ], featured: true);
        $this->simpleProduct($apple, 'iTunes Gift Card (UK)', 'itunes-gift-card-uk', 'apple', 'GB', [
            ['iTunes Gift Card 10 GBP', 'itunes-uk-10', 10, 1560, null, 2],
        ]);
        $this->simpleProduct($apple, 'iTunes Gift Card (India)', 'itunes-gift-card-india', 'apple', 'IN', [
            ['iTunes Gift Card 500 INR', 'itunes-in-500', 500, 760, 820, 4],
        ]);

        $steam = $this->brand($section, 'Steam', 'steam', 1, '<p>Sign in to Steam, click your username, choose <strong>Account details</strong>, then <strong>Add funds to your Steam Wallet</strong>.</p>');
        $this->steamRegionalFamily($steam);

        $this->simpleBrandWithProduct($section, 'Roblox', 'roblox', 2, 'Roblox Gift Card', 'roblox-gift-card', 'US', [
            ['Roblox 10 USD', 'roblox-gc-10', 10, 1240, null, 4],
        ]);

        $this->simpleBrandWithProduct($section, 'Valorant', 'valorant', 3, 'Valorant Point Gift Card', 'valorant-points', 'BD', [
            ['Valorant 1050 VP', 'valorant-1050', 1050, 1180, 1250, 5],
        ]);

        $this->simpleBrandWithProduct($section, 'PlayStation', 'playstation', 4, 'PlayStation Gift Card USA', 'playstation-gift-card-usa', 'US', [
            ['PSN 10 USD', 'psn-10', 10, 1240, null, 3],
            ['PSN 25 USD', 'psn-25', 25, 3050, null, 2],
        ], featured: true);

        $this->simpleBrandWithProduct($section, 'Nintendo', 'nintendo', 5, 'Nintendo eShop Card', 'nintendo-eshop', 'US', [
            ['Nintendo 20 USD', 'nintendo-20', 20, 2480, null, 2],
        ]);

        $this->simpleBrandWithProduct($section, 'Netflix', 'netflix', 6, 'Netflix Gift Card', 'netflix-gift-card', 'US', [
            ['Netflix 25 USD', 'netflix-25', 25, 3100, null, 2],
        ]);

        $this->simpleBrandWithProduct($section, 'Xbox', 'xbox', 7, 'Xbox Gift Card', 'xbox-gift-card', 'US', [
            ['Xbox 15 USD', 'xbox-15', 15, 1860, null, 3],
        ]);

        $this->simpleBrandWithProduct($section, 'Amazon', 'amazon', 8, 'Amazon Gift Card', 'amazon-gift-card', 'US', [
            ['Amazon 25 USD', 'amazon-25', 25, 3080, null, 3],
        ]);

        $this->simpleBrandWithProduct($section, 'Blizzard', 'blizzard', 9, 'Blizzard Battle.net Card', 'blizzard-balance', 'US', [
            ['Blizzard 20 USD', 'blizzard-20', 20, 2490, null, 2],
        ]);

        $this->simpleBrandWithProduct($section, 'Garena', 'garena', 10, 'Garena Shell', 'garena-shell', 'BD', [
            ['Garena 500 Shell', 'garena-500', 500, 890, null, 4],
        ]);

        $this->simpleBrandWithProduct($section, 'Fortnite', 'fortnite', 11, 'Fortnite V-Bucks Card', 'fortnite-vbucks', 'US', [
            ['Fortnite 1000 V-Bucks', 'fortnite-1000', 1000, 1180, null, 4],
        ]);

        $google = $this->brand($section, 'Google Play', 'google-play', 12, '<p>Open the Play Store, tap your profile, then <strong>Payments &amp; subscriptions</strong> and <strong>Redeem code</strong>.</p>');
        $this->simpleProduct($google, 'Google Play Gift Card', 'google-play-gift-card', 'google-play', 'US', [
            ['Google Play 10 USD', 'google-play-10', 10, 1180, null, 6],
            ['Google Play 25 USD', 'google-play-25', 25, 2890, 2990, 4],
            ['Google Play 50 USD', 'google-play-50', 50, 5750, null, 0],
        ]);
    }

    /**
     * Steam Wallet in three regions, sharing a region_group so each product
     * page offers the others in its region switcher.
     */
    private function steamRegionalFamily(MainCategory $brand): void
    {
        $hkd = $this->product($brand, 'Steam Wallet Code HKD', 'steam-wallet-hkd', 'steam', [
            'region'           => 'HK',
            'region_group'     => 'steam-wallet',
            'is_featured'      => true,
            'featured_sort'    => 0,
            'sort_order'       => 0,
            'description'      => 'Steam HKD codes work on Hong Kong accounts. The credited balance depends on Steam live HKD conversion rate, so the actual amount may vary slightly.',
            'long_description' => '<p>Steam Store BD sells <strong>Steam Wallet Code HKD</strong> for Bangladeshi gamers who want to add funds to their Steam account without a foreign credit card. Codes are delivered instantly by e-mail and to your account dashboard.</p>'
                . '<p>Available in 40, 50, 80, 100, 120 and 160 HKD. Payment is accepted through bKash, Nagad and Rocket. Orders are processed every day, including weekends and holidays.</p>'
                . '<h3>Why buy here</h3><ul><li>Instant delivery, no waiting for a reply</li><li>Every code sourced directly and checked before sale</li><li>Replacement guaranteed if a code fails</li></ul>',
            'instructions'     => '<ol><li>Open Steam and sign in to the account you want the funds on.</li><li>Click your username, top right, and choose <strong>Account details</strong>.</li><li>Choose <strong>Add funds to your Steam Wallet</strong>.</li><li>Scroll to <strong>Redeem a Steam Gift Card or Wallet Code</strong>.</li><li>Paste the code and confirm.</li></ol>',
            'faq'              => [
                ['question' => 'Does an HKD code work on a Bangladeshi account?', 'answer' => 'Yes. The balance is converted at Steam live HKD rate at the moment you redeem it, so the exact USD amount varies slightly.'],
                ['question' => 'How long does delivery take?', 'answer' => 'The code appears on screen and in your e-mail as soon as the payment clears — usually under two minutes.'],
                ['question' => 'What if the code does not work?', 'answer' => 'Send us the order number and a screenshot. We verify every code before sale and replace a faulty one at no cost.'],
            ],
        ]);

        // Two stocked, four sold out: the reference product page exactly.
        foreach ([[40, 697, 720, 4], [50, 871, null, 4], [80, 1394, null, 0], [100, 1739, null, 0], [120, 2176, null, 0], [160, 2894, null, 0]] as $i => [$denomination, $price, $compareAt, $codes]) {
            $this->card($hkd, "Steam Code {$denomination} HKD", "steam-hkd-{$denomination}", 'steam', "{$denomination}", [
                'denomination'          => $denomination,
                'denomination_currency' => 'HKD',
                'denomination_bdt'      => $price - 20,
                'price_bdt'             => $price,
                'compare_at_price_bdt'  => $compareAt,
                'sort_order'            => $i,
                'min_quantity'          => 1,
                'max_quantity'          => 2,
            ], $codes);
        }

        $usa = $this->product($brand, 'Steam Wallet Code USA', 'steam-wallet-usa', 'steam', [
            'region'       => 'US',
            'region_group' => 'steam-wallet',
            'sort_order'   => 1,
            'description'  => 'Steam USD codes redeem on accounts registered in the United States.',
        ]);

        foreach ([[5, 699, 720, 6], [10, 1340, null, 5], [20, 2640, null, 3]] as $i => [$denomination, $price, $compareAt, $codes]) {
            $this->card($usa, "Steam Wallet Code {$denomination} USD", "steam-usa-{$denomination}", 'steam', "\${$denomination}", [
                'denomination'          => $denomination,
                'denomination_currency' => 'USD',
                'denomination_bdt'      => $price - 20,
                'price_bdt'             => $price,
                'compare_at_price_bdt'  => $compareAt,
                'sort_order'            => $i,
                'max_quantity'          => 5,
            ], $codes);
        }

        $turkey = $this->product($brand, 'Steam Wallet Code Turkey (TL)', 'steam-wallet-turkey', 'steam', [
            'region'       => 'TR',
            'region_group' => 'steam-wallet',
            'sort_order'   => 2,
        ]);

        $this->card($turkey, 'Steam Code 100 TL', 'steam-tr-100', 'steam', '100', [
            'denomination'          => 100,
            'denomination_currency' => 'TRY',
            'denomination_bdt'      => 420,
            'price_bdt'             => 450,
            'max_quantity'          => 4,
        ], 3);
    }

    // ── Games key ────────────────────────────────────────────────────────────

    private function gameKeyBrands(CatalogSection $section): void
    {
        $this->simpleBrandWithProduct($section, 'Forza Horizon', 'forza', 0, 'Forza Horizon 5 Key', 'forza-horizon-key', 'GL', [
            ['Forza Horizon 5 PC', 'forza-5-pc', 1, 2950, 3250, 3],
        ]);

        $this->simpleBrandWithProduct($section, 'Minecraft', 'minecraft', 1, 'Minecraft Java Edition Key', 'minecraft-java-key', 'GL', [
            ['Minecraft Java PC', 'minecraft-java-pc', 1, 2450, null, 2],
        ]);

        $this->simpleBrandWithProduct($section, 'GTA V', 'rockstar', 2, 'GTA V Premium Edition Key', 'gta-v-key', 'GL', [
            ['GTA V PC Key', 'gta-v-pc', 1, 1850, 2100, 3],
        ], featured: true);

        $this->simpleBrandWithProduct($section, 'Red Dead Redemption', 'red-dead', 3, 'Red Dead Redemption 2 Key', 'rdr2-key', 'GL', [
            ['RDR2 PC Key', 'rdr2-pc', 1, 3150, null, 2],
        ]);

        $this->simpleBrandWithProduct($section, 'Battlefield', 'battlefield', 4, 'Battlefield 2042 Key', 'battlefield-key', 'GL', [
            ['Battlefield 2042 PC', 'bf-2042-pc', 1, 1650, null, 2],
        ]);
    }

    // ── Subscriptions ────────────────────────────────────────────────────────

    private function subscriptionBrands(CatalogSection $section): void
    {
        $this->subscription($section, 'NordVPN', 'nordvpn', 0, 'NordVPN Premium', 'nordvpn-premium', 'GL', [
            ['NordVPN 1 Year', 'nordvpn-1y', 12, 3200, null, 5],
        ]);

        $this->subscription($section, 'YouTube Premium', 'youtube', 1, 'YouTube Premium', 'youtube-premium', 'BD', [
            ['YouTube Premium 3M', 'youtube-3m', 3, 690, 750, 10],
        ]);

        $this->subscription($section, 'Prime Video', 'prime-video', 2, 'Prime Video Subscription', 'prime-video-plan', 'BD', [
            ['Prime Video 6M', 'prime-6m', 6, 890, null, 8],
        ]);

        $this->subscription($section, 'Microsoft 365', 'microsoft', 3, 'Microsoft 365 Personal', 'microsoft-365', 'GL', [
            ['Microsoft 365 1 Year', 'ms365-1y', 12, 4200, null, 4],
        ]);

        $this->subscription($section, 'Hoichoi', 'hoichoi', 4, 'Hoichoi Premium', 'hoichoi-premium', 'BD', [
            ['Hoichoi 6M — 1 Screen', 'hoichoi-6m', 6, 420, null, 8],
        ]);

        $this->subscription($section, 'ExitLag', 'exitlag', 5, 'ExitLag Subscription', 'exitlag-plan', 'GL', [
            ['ExitLag 1 Month', 'exitlag-1m', 1, 480, null, 12],
        ]);
    }

    // ── Building blocks ──────────────────────────────────────────────────────

    private function brand(CatalogSection $section, string $name, string $slug, int $sort, ?string $howToRedeem = null): MainCategory
    {
        return MainCategory::updateOrCreate(['slug' => $slug], [
            'name'               => $name,
            'is_active'          => true,
            'sort_order'         => $sort,
            'catalog_section_id' => $section->id,
            'how_to_redeem'      => $howToRedeem,
            'description'        => $name . ' products, delivered to your inbox.',
            'image'              => $this->artwork->brandTile($slug, $name),
        ]);
    }

    /**
     * A brand with exactly one product under it, which is most of the roster.
     *
     * @param  list<array{0: string, 1: string, 2: float, 3: float, 4: float|null, 5: int}>  $cards
     */
    private function simpleBrandWithProduct(CatalogSection $section, string $brandName, string $brandSlug, int $sort, string $productName, string $productSlug, string $region, array $cards, bool $featured = false): void
    {
        $brand = $this->brand($section, $brandName, $brandSlug, $sort);

        $this->simpleProduct($brand, $productName, $productSlug, $brandSlug, $region, $cards, $featured);
    }

    /** A top-up brand: manual fulfilment, and it asks for a Player ID. */
    private function simpleTopUp(CatalogSection $section, string $brandName, string $brandSlug, int $sort, string $productName, string $productSlug, array $cards): void
    {
        $brand = $this->brand($section, $brandName, $brandSlug, $sort);

        $product = $this->product($brand, $productName, $productSlug, $brandSlug, [
            'region'             => 'BD',
            'description'        => 'Credited directly to the account you name at checkout.',
            'buyer_input_fields' => [
                ['key' => 'player_id', 'label' => 'Player ID', 'required' => true, 'placeholder' => '1234567890'],
            ],
        ]);

        foreach ($cards as $i => [$name, $slug, $denomination, $price, $compareAt]) {
            $this->card($product, $name, $slug, $brandSlug, (string) $denomination, [
                'denomination'          => $denomination,
                'denomination_currency' => 'Credits',
                'denomination_bdt'      => $price - 40,
                'price_bdt'             => $price,
                'compare_at_price_bdt'  => $compareAt,
                'sort_order'            => $i,
                'fulfilment_type'       => GiftCard::FULFILMENT_MANUAL,
                'manual_stock'          => 20,
                'delivery_eta_label'    => '5-30 minutes',
            ]);
        }
    }

    /** A subscription brand: credentials delivered by an admin. */
    private function subscription(CatalogSection $section, string $brandName, string $brandSlug, int $sort, string $productName, string $productSlug, string $region, array $cards): void
    {
        $brand = $this->brand($section, $brandName, $brandSlug, $sort);

        $product = $this->product($brand, $productName, $productSlug, $brandSlug, [
            'region'      => $region,
            'description' => 'Account details are sent to your order page once the plan is activated.',
        ]);

        foreach ($cards as $i => [$name, $slug, $months, $price, $compareAt, $stock]) {
            $this->card($product, $name, $slug, $brandSlug, "{$months}M", [
                'denomination'          => $months,
                'denomination_currency' => 'Months',
                'denomination_bdt'      => $price - 60,
                'price_bdt'             => $price,
                'compare_at_price_bdt'  => $compareAt,
                'sort_order'            => $i,
                'fulfilment_type'       => GiftCard::FULFILMENT_CREDENTIALS,
                'manual_stock'          => $stock,
                'delivery_eta_label'    => 'Within 1 hour',
                'max_quantity'          => 2,
            ]);
        }
    }

    /**
     * A product plus its denominations in one call.
     *
     * @param  list<array{0: string, 1: string, 2: float, 3: float, 4: float|null, 5: int}>  $cards
     */
    private function simpleProduct(MainCategory $brand, string $name, string $slug, string $brandSlug, string $region, array $cards, bool $featured = false): GiftCardCategory
    {
        $product = $this->product($brand, $name, $slug, $brandSlug, [
            'region'      => $region,
            'is_featured' => $featured,
            'description' => $name . ' — redeemable on an account registered in that region.',
        ]);

        foreach ($cards as $i => [$cardName, $cardSlug, $denomination, $price, $compareAt, $codes]) {
            $this->card($product, $cardName, $cardSlug, $brandSlug, $this->iconLabel($cardName, $denomination), [
                'denomination'          => $denomination,
                'denomination_currency' => 'USD',
                'denomination_bdt'      => $price - 30,
                'price_bdt'             => $price,
                'compare_at_price_bdt'  => $compareAt,
                'sort_order'            => $i,
            ], $codes);
        }

        return $product;
    }

    /** @param  array<string, mixed>  $attributes */
    private function product(MainCategory $brand, string $name, string $slug, string $brandSlug, array $attributes = []): GiftCardCategory
    {
        return GiftCardCategory::updateOrCreate(['slug' => $slug], array_merge([
            'name'             => $name,
            'is_active'        => true,
            'main_category_id' => $brand->id,
            'image'            => $this->artwork->productCover($slug, $brand->name, $brandSlug),
        ], $attributes));
    }

    /**
     * updateOrCreate, not firstOrCreate: gift_cards.denomination is NOT NULL,
     * so the row has to be written complete rather than created bare.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function card(GiftCardCategory $product, string $name, string $slug, string $brandSlug, string $iconLabel, array $attributes = [], int $codes = 0): GiftCard
    {
        $card = GiftCard::updateOrCreate(['slug' => $slug], array_merge([
            'name'        => $name,
            'category_id' => $product->id,
            'is_active'   => true,
            'image'       => $this->artwork->cardIcon($slug, $iconLabel, $brandSlug),
        ], $attributes));

        for ($i = 0; $i < $codes; $i++) {
            GiftCardCode::firstOrCreate(
                ['code' => strtoupper(str_replace('-', '', $slug)) . '-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT)],
                ['gift_card_id' => $card->id, 'status' => 'available', 'added_by_admin_id' => $this->admin->id],
            );
        }

        return $card;
    }

    /** A short label for a denomination icon: "$25", "660 UC", "6M". */
    private function iconLabel(string $cardName, float $denomination): string
    {
        return Str::contains($cardName, 'USD') ? '$' . (int) $denomination : (string) (int) $denomination;
    }

    private function banners(): void
    {
        $slides = [
            ['eid', 'Enjoy 7.5% off your first order', 'bKash, Nagad and Rocket accepted · 1–31 December', 'playstation'],
            ['steam', 'Steam Wallet codes, delivered in minutes', 'HKD, USD and TRY regions in stock right now', 'steam'],
            ['pubg', 'Top up PUBG UC without a card', 'Give us your Player ID — we credit it in 5 to 30 minutes', 'pubg'],
        ];

        foreach ($slides as $sort => [$slug, $headline, $subline, $brandSlug]) {
            $path = $this->artwork->banner($slug, $headline, $subline, $brandSlug);

            Banner::updateOrCreate(['title' => $headline], [
                'image'        => $path,
                'mobile_image' => $path,
                'alt_text'     => $headline,
                'link_url'     => null,
                'sort_order'   => $sort,
                'is_active'    => true,
            ]);
        }
    }
}
