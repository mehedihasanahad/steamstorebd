<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    /**
     * The settings that are genuinely true/false.
     *
     * Named, rather than inferred from whatever happens to be stored. The
     * value cannot tell you: an empty heading and a switched-off toggle both
     * read as "", and treating them alike turned a blank Exclusive Offers
     * title into a literal "0" on the storefront -- it came back as false,
     * went into a text column as 0, and read as false for good afterwards.
     */
    public const BOOLEAN_KEYS = [
        'announcement_bar_active',
        'exclusive_offers_enabled',
        'payment_bkash_online_enabled',
        'payment_bkash_send_money_enabled',
        'payment_nagad_send_money_enabled',
        'payment_rocket_send_money_enabled',
        'whatsapp_chat_enabled',
        'messenger_chat_enabled',
        'messenger_use_plugin',
        'referral_enabled',
        'product_chat_whatsapp_enabled',
        'product_chat_messenger_enabled',
        'reseller_program_enabled',
    ];

    public static function isBoolean(string $key): bool
    {
        return in_array($key, self::BOOLEAN_KEYS, true);
    }

    protected $fillable = ['key', 'value', 'group'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("site_setting_{$key}", 600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );
        Cache::forget("site_setting_{$key}");
    }

    public static function getGroup(string $group): array
    {
        return static::where('group', $group)->pluck('value', 'key')->toArray();
    }
}
