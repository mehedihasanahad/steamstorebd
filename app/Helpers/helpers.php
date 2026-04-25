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
        return number_format((float) $amount, 0) . ' ' . $currency;
    }
}

if (! function_exists('generate_order_number')) {
    function generate_order_number(): string
    {
        return 'BD' . date('Y') . '-' . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
