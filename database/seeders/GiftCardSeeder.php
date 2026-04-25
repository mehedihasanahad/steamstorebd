<?php

namespace Database\Seeders;

use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\GiftCardCode;
use App\Models\User;
use Illuminate\Database\Seeder;

class GiftCardSeeder extends Seeder
{
    public function run(): void
    {
        $usdCategory = GiftCardCategory::where('slug', 'usd-cards')->first();
        $bdtCategory = GiftCardCategory::where('slug', 'bdt-cards')->first();
        $admin = User::where('is_admin', true)->first();

        $cards = [
            ['name' => 'Steam $5 Gift Card',  'slug' => 'steam-5-usd',  'denomination' => 5,  'denomination_currency' => 'USD', 'denomination_bdt' => 600,  'price_bdt' => 750,  'category_id' => $usdCategory->id, 'badge_text' => null,         'sort_order' => 1],
            ['name' => 'Steam $10 Gift Card', 'slug' => 'steam-10-usd', 'denomination' => 10, 'denomination_currency' => 'USD', 'denomination_bdt' => 1200, 'price_bdt' => 1450, 'category_id' => $usdCategory->id, 'badge_text' => 'Popular',      'sort_order' => 2],
            ['name' => 'Steam $20 Gift Card', 'slug' => 'steam-20-usd', 'denomination' => 20, 'denomination_currency' => 'USD', 'denomination_bdt' => 2400, 'price_bdt' => 2800, 'category_id' => $usdCategory->id, 'badge_text' => 'Best Value',   'sort_order' => 3]
        ];

        foreach ($cards as $cardData) {
            $card = GiftCard::firstOrCreate(
                ['slug' => $cardData['slug']],
                array_merge($cardData, ['is_active' => true, 'stock_count' => 5, 'description' => 'Add funds to your Steam Wallet and use it to purchase games, DLC, and more on Steam.'])
            );

            // Only seed codes in local environment
            if (app()->environment('local') && $card->codes()->count() === 0) {
                for ($i = 1; $i <= 5; $i++) {
                    $suffix = strtoupper(substr(md5($card->slug . $i . microtime()), 0, 16));
                    $code = 'STEAM-' . implode('-', str_split($suffix, 4));
                    GiftCardCode::create([
                        'gift_card_id' => $card->id,
                        'code' => $code,
                        'status' => 'available',
                        'added_by_admin_id' => $admin->id,
                    ]);
                }
            }
        }
    }
}
