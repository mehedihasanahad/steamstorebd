<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Clears text settings that a coercion bug turned into the string "0".
 *
 * The settings screen decided what was a boolean by looking at the value:
 * anything reading "", "0" or "1" was cast to bool. An empty heading
 * therefore loaded as false, and the next Save wrote that false into a text
 * column, where it landed as "0". From then on it read as false again, so it
 * was self-sustaining -- and the storefront rendered a literal 0 where the
 * Exclusive Offers heading should have been.
 *
 * The page now names its booleans instead of guessing. This clears what the
 * guessing already stored. Keys are listed rather than derived: a migration
 * has to keep meaning the same thing after the lists it was written against
 * have moved on.
 */
return new class extends Migration
{
    /** Genuinely true/false, where "0" means off and must be left alone. */
    private const BOOLEAN_KEYS = [
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

    /** Amounts, where a zero is a figure somebody meant to type. */
    private const NUMERIC_KEYS = [
        'referral_discount_value',
        'referral_max_discount_cap',
        'referral_min_order_amount',
        'referral_owner_reward_amount',
        'referral_min_withdrawal_amount',
        'competitor_rate_usd',
        'competitor_rate_eur',
        'competitor_rate_gbp',
        'competitor_fee_usd',
        'competitor_fee_eur',
        'competitor_fee_gbp',
    ];

    public function up(): void
    {
        DB::table('site_settings')
            ->where('value', '0')
            ->whereNotIn('key', array_merge(self::BOOLEAN_KEYS, self::NUMERIC_KEYS))
            ->update(['value' => '']);
    }

    public function down(): void
    {
        // Not reversible, and should not be: the "0" this removes was never
        // a value anybody chose. Restoring it would put the bug back.
    }
};
