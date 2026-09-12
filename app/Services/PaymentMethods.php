<?php

namespace App\Services;

use App\Models\SiteSetting;

class PaymentMethods
{
    public const CARD_NETWORKS = 'VISA / MC';

    /**
     * Payment options switched on in Site Settings, in the order shoppers see them.
     *
     * @return array<int, array{name: string, logo: ?string, round: bool}>
     */
    public static function active(): array
    {
        $methods = [];

        if (SiteSetting::get('payment_bkash_online_enabled', true) || SiteSetting::get('payment_bkash_send_money_enabled', false)) {
            $methods[] = ['name' => 'bKash', 'logo' => 'bkash-logo.png', 'round' => true];
        }
        if (SiteSetting::get('payment_nagad_send_money_enabled', false)) {
            $methods[] = ['name' => 'Nagad', 'logo' => 'nagad-logo.webp', 'round' => false];
        }
        if (SiteSetting::get('payment_rocket_send_money_enabled', false)) {
            $methods[] = ['name' => 'Rocket', 'logo' => 'rocket-logo.png', 'round' => true];
        }
        if (SiteSetting::get('payment_bkash_online_enabled', true)) {
            $methods[] = ['name' => self::CARD_NETWORKS, 'logo' => null, 'round' => false];
        }

        return $methods;
    }

    /**
     * Only the mobile wallets, for copy such as "pay with bKash or Nagad".
     *
     * @return array<int, string>
     */
    public static function walletNames(): array
    {
        return collect(static::active())
            ->pluck('name')
            ->reject(fn (string $name) => $name === self::CARD_NETWORKS)
            ->values()
            ->all();
    }
}
