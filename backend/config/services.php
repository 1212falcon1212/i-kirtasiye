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

    'netgsm' => [
        'user' => env('NETGSM_USER'),
        'password' => env('NETGSM_PASSWORD'),
        'header' => env('NETGSM_HEADER'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | GLN Verification Service
    |--------------------------------------------------------------------------
    |
    | Configuration for GLN (Global Location Number) verification.
    | Supported drivers: "whitelist", "its"
    |
    */
    'gln' => [
        'driver' => env('GLN_DRIVER', 'whitelist'),

        'its' => [
            'api_url' => env('GLN_ITS_API_URL'),
            'api_key' => env('GLN_ITS_API_KEY'),
            'timeout' => env('GLN_ITS_TIMEOUT', 30),
        ],
    ],

];
