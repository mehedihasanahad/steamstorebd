<?php

namespace Database\Seeders\Demo;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * Every feature flag the storefront reads, switched on.
 *
 * Several blocks — the announcement bar, the referral and reseller sections,
 * the chat buttons, the send-money instructions — render nothing at all unless
 * a setting turns them on. Without this the demo storefront would be missing
 * whole sections for reasons that look like bugs.
 */
class SiteSettingsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'general' => [
                'site_name'               => 'Steam Store BD',
                'announcement_bar_active' => '1',
                'announcement_bar_text'   => 'New cards in stock. Instant delivery, every day.',
                'contact_email'           => 'support@steamstorebd.test',
                'contact_whatsapp'        => '+8801700000000',
            ],
            'payment' => [
                'payment_bkash_online_enabled'     => '1',
                'payment_bkash_send_money_enabled' => '1',
                'payment_nagad_send_money_enabled' => '1',
                'payment_rocket_send_money_enabled' => '0',
            ],
            'referral' => [
                'referral_enabled'                => '1',
                'referral_owner_reward_amount'    => '30',
                'referral_discount_type'          => 'flat',
                'referral_discount_value'         => '20',
                'referral_max_discount_cap'       => '0',
                'referral_min_withdrawal_amount'  => '100',
            ],
            'reseller' => [
                'reseller_program_enabled' => '1',
            ],
            'chat' => [
                'whatsapp_chat_enabled'   => '1',
                'whatsapp_chat_number'    => '8801700000000',
                'whatsapp_chat_message'   => 'Hello! I want to buy a gift card.',
                'messenger_chat_enabled'  => '1',
                'messenger_page_username' => 'SteamStoreBD',
            ],
            'product_chat' => [
                'product_chat_whatsapp_enabled'   => '1',
                'product_chat_whatsapp_number'    => '8801700000000',
                'product_chat_messenger_enabled'  => '1',
                'product_chat_messenger_username' => 'SteamStoreBD',
            ],
        ];

        foreach ($settings as $group => $pairs) {
            foreach ($pairs as $key => $value) {
                SiteSetting::set($key, $value, $group);
            }
        }
    }
}
