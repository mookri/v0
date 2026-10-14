<?php

/*
|--------------------------------------------------------------------------
| Verification Fabric - per-audience OTP provider registry
|--------------------------------------------------------------------------
|
| Each audience selects its OTP provider. The default everywhere is
| 'msg91_widget' (the MSG91 OTP widget in custom-SDK mode: the browser
| drives sendOtp/retryOtp/verifyOtp, Laravel verifies the resulting
| access token server-side — OTP codes never transit the backend).
|
| PROVIDER SWAP (ADR-002 recipe 1): implement PhoneVerificationProvider
| + VerifiesOtpTokens for the new vendor, register it in the
| OtpProviderRegistry singleton (AppServiceProvider) under its NAME,
| and flip these keys. No call site changes — the challenge rows carry
| the provider name, so in-flight challenges keep verifying under
| their original provider.
|
| FAIL CLOSED: a selected name the registry does not know (e.g. the
| provider was never registered because its credentials are missing)
| throws OtpProviderUnavailableException at dispatch — there is no
| fallback channel that cannot actually deliver.
|
*/

return [
    'audiences' => [
        'customer' => [
            'provider' => env('OTP_PROVIDER_CUSTOMER', 'msg91_widget'),
        ],
        'vendor' => [
            'provider' => env('OTP_PROVIDER_VENDOR', 'msg91_widget'),
        ],
        'platform' => [
            'provider' => env('OTP_PROVIDER_PLATFORM', 'msg91_widget'),
        ],
    ],
];
