<?php

namespace App\Services;

use App\Models\SiteSetting;

class PaymentMethods
{
    public const CARD_NETWORKS = 'VISA / MC';

    /**
     * How each method is presented at checkout: its name, its logo, and for
     * the send-money wallets the app, short code and destination number a
     * buyer needs in order to pay.
     *
     * The checkout view used to carry one hand-written block per wallet,
     * which meant three places to edit whenever the wording changed. This is
     * that content, once.
     *
     * @var array<string, array<string, string|null>>
     */
    public const DETAILS = [
        'bkash_online' => [
            'name'       => 'bKash Online',
            'sub'        => 'Tokenized payment',
            'logo'       => 'bkash-logo.png',
            'app'        => null,
            'ussd'       => null,
            'number_key' => null,
        ],
        'bkash_send_money' => [
            'name'       => 'bKash Send Money',
            'sub'        => 'Manual transfer',
            'logo'       => 'bkash-logo.png',
            'app'        => 'bKash app',
            'ussd'       => '*247#',
            'number_key' => 'services.payment.bkash_send_money_number',
        ],
        'nagad_send_money' => [
            'name'       => 'Nagad Send Money',
            'sub'        => 'Manual transfer',
            'logo'       => 'nagad-logo.webp',
            'app'        => 'Nagad app',
            'ussd'       => '*167#',
            'number_key' => 'services.payment.nagad_send_money_number',
        ],
        'rocket_send_money' => [
            'name'       => 'Rocket Send Money',
            'sub'        => 'Manual transfer',
            'logo'       => 'rocket-logo.png',
            'app'        => 'Rocket app',
            'ussd'       => '*322#',
            'number_key' => 'services.payment.rocket_send_money_number',
        ],
    ];

    /**
     * Display data for one method, with the destination number resolved.
     * An unknown key still returns something renderable rather than throwing
     * in a view.
     *
     * @return array<string, string|null>
     */
    public static function describe(string $method): array
    {
        $details = self::DETAILS[$method] ?? [
            'name'       => $method,
            'sub'        => '',
            'logo'       => null,
            'app'        => null,
            'ussd'       => null,
            'number_key' => null,
        ];

        $details['number'] = $details['number_key'] ? config($details['number_key'], '') : null;

        return $details;
    }

    /** Does this method mean the buyer transfers the money themselves? */
    public static function isSendMoney(string $method): bool
    {
        return $method !== 'bkash_online';
    }

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
