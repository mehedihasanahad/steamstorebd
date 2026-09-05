<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Services\ChatLinkBuilder;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'Steam Store BD', 'group' => 'general'],
            ['key' => 'contact_email', 'value' => 'support@steamstorebd.com', 'group' => 'general'],
            ['key' => 'contact_whatsapp', 'value' => '+8801700000000', 'group' => 'general'],
            ['key' => 'hero_title', 'value' => 'Top Up Your Steam Wallet — Instantly', 'group' => 'hero'],
            ['key' => 'hero_subtitle', 'value' => 'Digital gift cards delivered to your email in minutes. Pay with bKash.', 'group' => 'hero'],
            ['key' => 'announcement_bar_text', 'value' => '🎮 New cards in stock! Buy now and get instant delivery.', 'group' => 'announcement'],
            ['key' => 'announcement_bar_active', 'value' => '1', 'group' => 'announcement'],
            ['key' => 'product_chat_whatsapp_enabled', 'value' => '0', 'group' => 'product_chat'],
            ['key' => 'product_chat_whatsapp_number', 'value' => '', 'group' => 'product_chat'],
            ['key' => 'product_chat_whatsapp_template', 'value' => ChatLinkBuilder::DEFAULT_TEMPLATE, 'group' => 'product_chat'],
            ['key' => 'product_chat_messenger_enabled', 'value' => '0', 'group' => 'product_chat'],
            ['key' => 'product_chat_messenger_username', 'value' => '', 'group' => 'product_chat'],
        ];

        foreach ($settings as $setting) {
            SiteSetting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
