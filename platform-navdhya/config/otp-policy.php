<?php

/*
|--------------------------------------------------------------------------
| Verification Fabric - purpose policy
|--------------------------------------------------------------------------
|
| purpose -> audience + risk tier. The audience picks the provider via
| config/otp-providers.php (msg91_widget everywhere by default); the
| tier declares how sensitive the step is:
|
|   T0/T1  1-of-1 single channel (login, verify_*, signup, partner_submit)
|   T2/T3  2-of-2 dual-channel quorum (change_*) — declared here;
|          enforcement lands with the contact-swap flows.
|
| `delivery` is the single source of truth for the OtpChallengeManager
| policy machine: challenge TTL, attempt cap, the compounding resend
| schedule (delays measured from the PREVIOUS send — 60s to the 1st
| resend, 180s to the 2nd, 540s to the 3rd), the fresh-challenge
| back-off (no new challenge while a live one can still deliver, and
| for fresh_retry_after seconds after its last send) and the rolling
| hourly fresh-challenge cap. Resend channel codes live in
| config/services.php (msg91.resend_channels) — they are provider
| dialect, not policy.
|
| Purposes missing from this table fail closed at issuance
| (OtpPurposeUnknownException) — an unmapped purpose is a deployment
| error, never a silent send.
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
        // MFA enrollment step-up (StepUpOtpService): identifier is the
        // account email (the widget sends to it; TOTP remains the
        // actual second factor).
        'mfa_enroll_stepup' => ['audience' => 'platform', 'tier' => 'T0'],
        // Partner onboarding submission (SubmitController): identifier
        // is the application contact phone from evidence.identity.
        'partner_submit' => ['audience' => 'vendor', 'tier' => 'T1'],
        // Contact swaps — dual-channel quorum when those flows land.
        'change_phone' => ['audience' => 'customer', 'tier' => 'T2'],
        'change_email' => ['audience' => 'customer', 'tier' => 'T2'],
        // Unified login at the auth subdomain (ADR-0020 D1): possession
        // proof on the federation surface. platform audience — the auth
        // host is the identity issuer, not a customer tenant surface —
        // provider still resolves to msg91_widget. No existence
        // pre-check: a verified identifier auto-provisions (D1).
        'federation_login' => ['audience' => 'platform', 'tier' => 'T0'],
    ],

    'delivery' => [
        // Fits the full resend schedule: 60 + 180 + 540 = 780s of
        // sends inside a 1200s challenge.
        'ttl' => 1200,
        'max_attempts' => 3,
        'resend_max' => 3,
        'resend_delays' => [60, 180, 540],
        // Fresh-challenge back-off after the last send of a live
        // challenge (and after its schedule exhausts).
        'fresh_retry_after' => 600,
        // Backstop: fresh challenges per identifier+purpose per
        // rolling hour (the back-off above is the primary control).
        'fresh_max_per_hour' => 3,
    ],
];
