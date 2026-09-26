<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Laravel')),
    ],

    'admin_notification_email' => env('ADMIN_NOTIFICATION_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@example.com')),

    /*
    |--------------------------------------------------------------------------
    | Campaign Sending
    |--------------------------------------------------------------------------
    |
    | Bulk e-mail runs on its own queue so it can never get in front of a
    | customer waiting for the code they just paid for -- the worker is told to
    | drain the default queue first. The rate is whatever the sending provider
    | will take without throttling or blocking; the queue releases jobs back
    | rather than dropping them when the limit is reached.
    |
    */

    'campaign' => [
        'queue'            => env('MAIL_CAMPAIGN_QUEUE', 'campaigns'),
        'rate_per_minute'  => (int) env('MAIL_CAMPAIGN_RATE_PER_MINUTE', 60),

        /*
         | Whether campaign mail announces itself as bulk. Off by default: the
         | header is a declaration, and a provider that is told a message is
         | bulk stops judging it the way it judges order mail and starts
         | demanding SPF, DKIM and DMARC that line up with the relay actually
         | sending it. Until the domain authenticates for that relay, saying
         | nothing is what gets a campaign the same treatment as the order
         | mail that already arrives. Turn it on once the DNS is right — it is
         | the better setting, and providers reward it.
         */
        'list_unsubscribe' => filter_var(env('MAIL_CAMPAIGN_LIST_UNSUBSCRIBE', false), FILTER_VALIDATE_BOOLEAN),
    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown Mail Settings
    |--------------------------------------------------------------------------
    |
    | Laravel builds its own messages — the password reset link, and anything
    | else sent as a MailMessage — from markdown components rather than from
    | the components under resources/views/components/email. The theme below
    | re-skins them from the same palette so they do not arrive in the
    | framework default while every other message is on brand.
    |
    */

    'markdown' => [
        'theme' => 'ash',

        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],

];
