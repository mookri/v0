<?php

/*
|--------------------------------------------------------------------------
| Customer Self-Service
|--------------------------------------------------------------------------
|
| Drill/staging support for the customer OTP flow. OTP delivery vendors
| land later (MAIL-001 tracks email fallback); until then there is no
| channel that carries codes in non-test environments.
|
| OTP_LOG_CODES=true writes issued codes to the log for local/staging
| drills ONLY. Never enable in production: logged codes are bearer
| credentials for account access.
|
*/

return [
    'log_otp_codes' => filter_var(env('OTP_LOG_CODES', false), FILTER_VALIDATE_BOOLEAN),
];
