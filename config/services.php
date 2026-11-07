<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/auth/google/callback'),
    ],

    'payment' => [
        'bkash_send_money_number'  => env('BKASH_SEND_MONEY_NUMBER', ''),
        'nagad_send_money_number'  => env('NAGAD_SEND_MONEY_NUMBER', ''),
        'rocket_send_money_number' => env('ROCKET_SEND_MONEY_NUMBER', ''),
    ],

    /*
    | Cloudflare Turnstile. Switched off wherever both keys are absent, which
    | is what keeps local work and the test suite off Cloudflare's network.
    |
    | fail_open: when Cloudflare itself cannot be reached we let the request
    | through rather than closing registration during someone else's outage.
    | Set it false only if you would rather turn customers away than risk a
    | bot getting past during an outage.
    */
    'turnstile' => [
        'site_key'   => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
        'timeout'    => env('TURNSTILE_TIMEOUT', 5),
        'fail_open'  => env('TURNSTILE_FAIL_OPEN', true),
    ],

];
