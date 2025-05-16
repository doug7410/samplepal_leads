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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'configuration_set' => env('AWS_SES_CONFIGURATION_SET', 'default'),
        'sender_email' => env('AWS_SES_SENDER_EMAIL'),
        'sender_name' => env('AWS_SES_SENDER_NAME'),
        'webhook_secret' => env('AWS_SES_WEBHOOK_SECRET'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
        'sender_email' => env('RESEND_SENDER_EMAIL', env('MAIL_FROM_ADDRESS')),
        'sender_name' => env('RESEND_SENDER_NAME', env('MAIL_FROM_NAME')),
        'webhook_secret' => env('RESEND_WEBHOOK_SECRET'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
