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



    'prestashop' => [
        'endpoint' => env('PRESTASHOP_API_URL'),
        'key' => env('PRESTASHOP_API_KEY'),
        'outbound_key' => env('PRESTASHOP_OUTBOUND_KEY', env('PRESTASHOP_API_KEY')),
        'webhook_key' => env('PRESTASHOP_WEBHOOK_KEY', env('PRESTASHOP_OUTBOUND_KEY', env('PRESTASHOP_API_KEY'))),
        'webhook_url' => env('PRESTASHOP_WEBHOOK_URL'),
        'product_timeout' => (int) env('PRESTASHOP_PRODUCT_SYNC_TIMEOUT', 30),
        'bookingbridge_cron_url' => env('PRESTASHOP_BOOKINGBRIDGE_CRON_URL'),
        'bookingbridge_cron_token' => env('PRESTASHOP_BOOKINGBRIDGE_CRON_TOKEN'),
        'bookingbridge_cron_timeout' => (int) env('PRESTASHOP_BOOKINGBRIDGE_CRON_TIMEOUT', 60),
    ],

    'stripe' => [
        'secret'        => env('STRIPE_SECRET'),
        'price_monthly' => env('STRIPE_PRICE_MONTHLY'),
        'price_yearly'  => env('STRIPE_PRICE_YEARLY'),
    ],

];
