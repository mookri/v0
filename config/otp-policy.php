<?php

/*
|--------------------------------------------------------------------------
| Verification Fabric - purpose policy (S48)
|--------------------------------------------------------------------------
|
| purpose -> audience + risk tier. The audience picks the provider via
| config/otp-providers.php (customer/vendor select 'local' by default
| and a Firebase project each once wired); the tier declares how many
| channels must confirm the step:
|
|   T0/T1  1-of-1 single channel (login, verify_*, signup)
|   T2/T3  2-of-2 dual-channel quorum (change_*, vendor onboarding-kyc)
|          — enforcement lands with S50, declared here from day one.
|
| Purposes missing from this table fail closed at issuance
| (OtpPurposeUnknownException) — an unmapped purpose is a deployment
| error, never a silent local send.
|
*/

return [
    'purposes' => [
        // Possession re-proof of a contact already on the account.
        'login' => ['audience' => 'customer', 'tier' => 'T0'],
        'verify_phone' => ['audience' => 'customer', 'tier' => 'T0'],
        'verify_email' => ['audience' => 'customer', 'tier' => 'T0'],
        // Account creation on a new identifier.
        'signup' => ['audience' => 'customer', 'tier' => 'T1'],
        // Contact swaps — dual-channel quorum from S50.
        'change_phone' => ['audience' => 'customer', 'tier' => 'T2'],
        'change_email' => ['audience' => 'customer', 'tier' => 'T2'],
        // vendor onboarding-kyc (T3, vendor audience) joins in S50
        // with the onboarding module's quorum wiring.
    ],

    'captcha' => [
        // reCAPTCHA v2 attestation required for REMOTE sends only.
        // Off by default: local delivery carries no attestation, and
        // Firebase test phone numbers don't require one (spike L1) —
        // flip per environment before real numbers go live.
        'customer' => filter_var(env('OTP_CAPTCHA_REQUIRED_CUSTOMER', false), FILTER_VALIDATE_BOOLEAN),
        'vendor' => filter_var(env('OTP_CAPTCHA_REQUIRED_VENDOR', false), FILTER_VALIDATE_BOOLEAN),
    ],
];
