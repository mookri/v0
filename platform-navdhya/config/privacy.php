<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shared Privacy Substrate Configuration (SPEC §10 / E-TR-06)
    |--------------------------------------------------------------------------
    | Keyed HMAC secret for deterministic duplicate/PII fingerprinting
    | (never raw PII in logs or unsalted SHA-256).
    */
    'fingerprint_key' => env('PRIVACY_FINGERPRINT_KEY', env('APP_KEY', 'navdhya-privacy-dev-key')),

    'default_retention_days' => [
        'rejected_application_documents' => 90,
        'withdrawn_draft_documents' => 30,
        'expired_evidence' => 180,
    ],

    'dsr_sla_days' => 30,
];

