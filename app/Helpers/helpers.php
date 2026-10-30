<?php

if (! function_exists('format_bdt')) {
    function format_bdt(float|int|string $amount): string
    {
        return '৳ ' . number_format((float) $amount, 0, '.', ',');
    }
}

if (! function_exists('site_setting')) {
    function site_setting(string $key, mixed $default = null): mixed
    {
        return \App\Models\SiteSetting::get($key, $default);
    }
}

if (! function_exists('format_card_denomination')) {
    function format_card_denomination(float|int|string $amount, string $currency = 'USD'): string
    {
        return number_format((float) $amount, 2) . ' ' . $currency;
    }
}

if (! function_exists('generate_order_number')) {
    /**
     * A fresh order number in the long-standing BD<year>-<six digits> shape.
     *
     * `orders.order_number` is unique, and six digits is only a million slots
     * that reset each January -- by the birthday paradox a shop doing a
     * thousand orders a year had roughly a two-in-five chance of a collision,
     * and a collision here is not a duplicate number but an exception thrown
     * mid-checkout at whichever customer drew it. So the number is checked
     * before it is handed back.
     *
     * The format is unchanged, so every number already printed on an invoice
     * or quoted in an e-mail still reads and still looks up the same way.
     */
    function generate_order_number(): string
    {
        $prefix = 'BD' . date('Y') . '-';

        foreach (range(1, 10) as $ignored) {
            $number = $prefix . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            if (! \App\Models\Order::where('order_number', $number)->exists()) {
                return $number;
            }
        }

        // Ten draws missed, so the six-digit space is crowded. Widen it rather
        // than lose the sale; the prefix still sorts and reads as before.
        return $prefix . strtoupper(bin2hex(random_bytes(4)));
    }
}
