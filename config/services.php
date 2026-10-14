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
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MSG91 (OTP widget)
    |--------------------------------------------------------------------------
    |
    | auth_key is the SERVER-ONLY secret (MSG91 dashboard → Settings →
    | API). It leaves the process for exactly one call: server-side
    | verification of widget access tokens (api/v5/widget/
    | verifyAccessToken). It is never served to any client.
    |
    | widget_id + widget_token_auth are public by design — the widget
    | script needs them in the browser, and they are served to clients
    | through GET auth/otp/config. If they leak, nothing breaks beyond
    | what is already public; only auth_key must stay secret.
    |
    | resend_channels are MSG91 retryOtp channel codes (docs): SMS '11',
    | Voice '4', Email '3', WhatsApp '12'. Order is the rotation order
    | the OtpChallengeManager hands out per granted resend.
    |
    */

    'msg91' => [
        'auth_key' => env('MSG91_AUTH_KEY'),
        'widget_id' => env('MSG91_WIDGET_ID'),
        'widget_token_auth' => env('MSG91_WIDGET_TOKEN_AUTH'),
        'verify_base_url' => env('MSG91_VERIFY_BASE_URL', 'https://control.msg91.com'),
        'timeout' => (int) env('MSG91_TIMEOUT', 5),
        'resend_channels' => array_values(array_filter(array_map('strval', explode(',', (string) env('MSG91_RESEND_CHANNELS', '11,4'))))),
    ],

];
