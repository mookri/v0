<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payments driver (B3)
    |--------------------------------------------------------------------------
    |
    | "razorpay" opens real orders through the Orders API (test-mode keys
    | by default — PAYMENTS_TEST_MODE). "fake" mints order_fake_* ids
    | offline for tests/E2E. The webhook verifies HMAC signatures against
    | RAZORPAY_WEBHOOK_SECRET under BOTH drivers — the money verification
    | path is never stubbed.
    |
    */

    'driver' => env('PAYMENTS_DRIVER', 'razorpay'),

    'test_mode' => env('PAYMENTS_TEST_MODE', true),

    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        'api_base' => env('RAZORPAY_API_BASE', 'https://api.razorpay.com/v1'),
    ],

];
