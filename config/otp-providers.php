<?php

/*
|--------------------------------------------------------------------------
| Verification Fabric - per-audience OTP provider registry (S47)
|--------------------------------------------------------------------------
|
| Each audience selects its phone-OTP provider: 'local' (the in-house
| hash path - default, and the permanent platform/step-up provider) or
| 'firebase' (Identity Toolkit, one Firebase project per audience so
| quotas and shadow accounts never cross-pollinate).
|
| FIREBASE_{CUSTOMER,VENDOR}_SERVICE_ACCOUNT is reserved for the
| optional Admin SDK idToken cross-check (spike L-item in
| docs/04-security/firebase-phone-otp-spike.md); the send/verify REST
| endpoints authenticate with the web API key alone.
|
| FAIL CLOSED: an audience that selects 'firebase' without an API key
| registers no provider at all - dispatch then throws
| OtpProviderUnavailableException instead of silently downgrading to
| local, which cannot deliver SMS. There is no production fallback.
|
*/

return [
    'audiences' => [
        'customer' => [
            'provider' => env('OTP_PROVIDER_CUSTOMER', 'local'),
            'firebase' => [
                'project' => env('FIREBASE_CUSTOMER_PROJECT'),
                'api_key' => env('FIREBASE_CUSTOMER_API_KEY'),
                'service_account' => env('FIREBASE_CUSTOMER_SERVICE_ACCOUNT'),
                // reCAPTCHA v2 site key bound to the audience's project
                // (S48 captcha pass-through; the same key is discoverable
                // at runtime via GET /v1/recaptchaParams).
                'recaptcha_key' => env('FIREBASE_CUSTOMER_RECAPTCHA_KEY'),
                'timeout_seconds' => (int) env('FIREBASE_CUSTOMER_TIMEOUT', 5),
            ],
        ],
        'vendor' => [
            'provider' => env('OTP_PROVIDER_VENDOR', 'local'),
            'firebase' => [
                'project' => env('FIREBASE_VENDOR_PROJECT'),
                'api_key' => env('FIREBASE_VENDOR_API_KEY'),
                'service_account' => env('FIREBASE_VENDOR_SERVICE_ACCOUNT'),
                'recaptcha_key' => env('FIREBASE_VENDOR_RECAPTCHA_KEY'),
                'timeout_seconds' => (int) env('FIREBASE_VENDOR_TIMEOUT', 5),
            ],
        ],
        'platform' => [
            // Step-up/MFA stays on the local provider forever (S45
            // decision): Firebase never verifies platform assertions.
            'provider' => 'local',
        ],
    ],

    // Email OTP delivery channel (S48): 'null' (the S46 silent default
    // — codes surface only via the drill-only OTP_LOG_CODES log) or
    // 'mailer' (MailerEmailOtpDelivery through Laravel's MAIL_*).
    // Delivery only; verification stays the local hash path.
    'email_delivery' => env('OTP_EMAIL_DELIVERY', 'null'),
];
