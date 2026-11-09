<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Privacy & Telemetry Retention Matrix (E5)
    | Ported from packages/analytics/src/privacy/retention-matrix.ts
    |--------------------------------------------------------------------------
    |
    | Defines statutory and operational retention horizons in days.
    |
    */

    'telemetry_days' => env('PRIVACY_RETENTION_TELEMETRY_DAYS', 90),

    'error_logs_days' => env('PRIVACY_RETENTION_ERROR_LOGS_DAYS', 30),

    'insights_days' => env('PRIVACY_RETENTION_INSIGHTS_DAYS', 365),

    'financial_exception_years' => env('PRIVACY_RETENTION_FINANCIAL_YEARS', 8),

];

