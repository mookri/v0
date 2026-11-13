<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Versioned Channel Routing Matrix (E-CM-03 / §6.3)
    |--------------------------------------------------------------------------
    |
    | Defines the single authoritative source of truth for channel priority,
    | fallback sequences, quiet hours rules, and consent dependencies.
    | Replaces duplicate TypeScript routing logic (C-04).
    |
    */

    'policy_version' => '2026.1',

    'categories' => [
        'authentication' => [
            'delegate_to' => 'Otp',
            'bypasses_quiet_hours' => true,
        ],
        'transactional' => [
            'priority' => ['WHATSAPP', 'EMAIL', 'SMS'],
            'fallback' => [
                'WHATSAPP' => 'EMAIL',
                'EMAIL' => 'SMS',
                'SMS' => null,
            ],
            'bypasses_quiet_hours' => true,
            'requires_consent' => false,
        ],
        'service' => [
            'priority' => ['WHATSAPP', 'IN_APP', 'EMAIL'],
            'fallback' => [
                'WHATSAPP' => 'IN_APP',
                'IN_APP' => 'EMAIL',
                'EMAIL' => null,
            ],
            'bypasses_quiet_hours' => false,
            'requires_consent' => false,
        ],
        'reminder' => [
            'priority' => ['WHATSAPP', 'SMS', 'IN_APP'],
            'fallback' => [
                'WHATSAPP' => 'SMS',
                'SMS' => 'IN_APP',
                'IN_APP' => null,
            ],
            'bypasses_quiet_hours' => false,
            'requires_consent' => true,
            'consent_purpose' => 'RITUAL_REMINDERS',
        ],
        'marketing' => [
            'priority' => ['EMAIL'],
            'fallback' => [
                'EMAIL' => null,
                'WHATSAPP' => null, // Explicit: WhatsApp block NEVER substitutes SMS for marketing
            ],
            'bypasses_quiet_hours' => false,
            'requires_consent' => true,
            'consent_purpose' => 'MARKETING',
        ],
    ],

    'circuit_breaker' => [
        'failure_threshold' => 5,
        'window_seconds' => 600,
        'cooldown_seconds' => 300,
    ],
];

