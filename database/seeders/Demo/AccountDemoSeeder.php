<?php

namespace Database\Seeders\Demo;

use App\Models\ContactMessage;
use App\Models\Favourite;
use App\Models\GiftCardCategory;
use App\Models\ResellerApplication;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The rest of what a signed-in shopper sees — saved products — plus the two
 * admin-side inboxes that would otherwise be empty: reseller applications and
 * contact messages.
 */
class AccountDemoSeeder extends Seeder
{
    public function run(): void
    {
        $shopper = User::where('email', OrderDemoSeeder::SHOPPER_EMAIL)->first();

        if ($shopper !== null) {
            $this->favourites($shopper);
        }

        $this->resellerApplications();
        $this->contactMessages();
    }

    private function favourites(User $shopper): void
    {
        $slugs = ['steam-wallet-hkd', 'pubg-uc', 'itunes-gift-card-usa'];

        foreach (GiftCardCategory::whereIn('slug', $slugs)->get() as $product) {
            Favourite::firstOrCreate([
                'user_id'               => $shopper->id,
                'gift_card_category_id' => $product->id,
            ]);
        }
    }

    private function resellerApplications(): void
    {
        $applications = [
            ['RS-2026-0001', 'Imran Hossain', 'imran@example.test', 'facebook_page', ['steam', 'google-play'], 'pending', null],
            ['RS-2026-0002', 'Sumaiya Akter', 'sumaiya@example.test', 'gaming_zone', ['pubg', 'free-fire'], 'approved', null],
            ['RS-2026-0003', 'Rakib Khan', 'rakib@example.test', 'other', ['steam'], 'declined', 'We could not verify a selling history.'],
        ];

        foreach ($applications as [$number, $name, $email, $platform, $types, $status, $reason]) {
            ResellerApplication::updateOrCreate(['application_number' => $number], [
                'name'             => $name,
                'email'            => $email,
                'phone'            => '01712345678',
                'whatsapp_number'  => '01712345678',
                'selling_platform' => $platform,
                'gift_card_types'  => $types,
                'status'           => $status,
                'decline_reason'   => $reason,
                'ip_address'       => '203.0.113.7',
            ]);
        }
    }

    private function contactMessages(): void
    {
        $messages = [
            ['Nusrat Jahan', 'nusrat@example.test', 'My code shows as already redeemed. Order BD2026-100001.', null],
            ['Faisal Ahmed', 'faisal@example.test', 'Do you sell Xbox gift cards for the UK region?', now()->subDay()],
        ];

        foreach ($messages as [$name, $email, $message, $readAt]) {
            ContactMessage::updateOrCreate(['email' => $email, 'message' => $message], [
                'name'       => $name,
                'ip_address' => '203.0.113.7',
                'read_at'    => $readAt,
            ]);
        }
    }
}
