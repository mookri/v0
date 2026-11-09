<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notifications Driver (E0 / E4)
    |--------------------------------------------------------------------------
    |
    | Supported: "fake", "live" (or provider-specific: "msg91", "whatsapp", "smtp").
    | Default is "fake" offline for local dev/testing/E2E without live credentials.
    | Email fake uses Laravel's native MAIL_MAILER=log (documented as the email fake).
    |
    */

    'driver' => env('NOTIFICATIONS_DRIVER', 'fake'),

    'msg91' => [
        'auth_key' => env('MSG91_AUTH_KEY'),
        'sender_id' => env('MSG91_SENDER_ID', 'NAVDHY'),
        'api_base' => env('MSG91_API_BASE', 'https://api.msg91.com/api/v5'),
        'webhook_secret' => env('MSG91_WEBHOOK_SECRET'),
        'dlt_templates' => [
            'booking_confirmed' => env('MSG91_DLT_BOOKING_CONFIRMED'),
            'ritual_reminder' => env('MSG91_DLT_RITUAL_REMINDER'),
            'panchang_alert' => env('MSG91_DLT_PANCHANG_ALERT'),
        ],
    ],

    'whatsapp' => [
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
    ],

    'quiet_hours' => [
        'default_start' => env('NOTIFICATIONS_QUIET_HOURS_START', '21:00'),
        'default_end' => env('NOTIFICATIONS_QUIET_HOURS_END', '07:00'),
    ],

];

