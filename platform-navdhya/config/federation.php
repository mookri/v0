<?php

/*
|--------------------------------------------------------------------------
| Auth Federation (ADR-0020)
|--------------------------------------------------------------------------
|
| The authorization-server subdomain and its binding policy numbers. All
| values are env-driven; the auth surface fails closed when a federation
| deployment is asserted (domain set) but incomplete. An empty domain
| means "federation routes not mounted" — direct-mode apps are unaffected.
|
| Local dev: auth.localhost resolves to 127.0.0.0 in modern browsers; the
| subdomain scope mounts on whatever host is configured, TLS optional
| (Secure-prefixed SSO cookies require TLS and stay gated off on http).
|
*/

return [
    // AUTH_MODE (ADR-0020): the single knob that tells applications
    // which login path exists.
    //   direct    - apps keep calling their own tenant-scoped login
    //               APIs (today's /api/v1/customer/auth/*); the auth
    //               subdomain (if any) serves metadata + probe only.
    //   federated - apps hand the browser to this subdomain's unified
    //               login; verified proof continues into the OAuth
    //               authorize -> code -> back-channel exchange.
    // Anything else is a broken deployment: rejected at boot
    // (FederationMode::current) — a typo must never leave apps guessing
    // against a surface that cannot serve them. Federated additionally
    // requires domain + issuer: every app redirect would otherwise
    // dead-end at the fail-closed host gate.
    'mode' => env('AUTH_MODE', 'direct'),

    // AUTH_RUNTIME (ADR-0020 D6): the role this process is configured
    // as — the same repository running as an independently configured
    // runtime.
    //   monolith             - everything on one process (default; the
    //                          strangler-era behavior: federation
    //                          surface on the auth host, legacy tenant
    //                          login for apps).
    //   authorization_server - the issuer. Must run AUTH_MODE=federated
    //                          with a token-signing key (both refused
    //                          at boot otherwise) and NEVER serves the
    //                          legacy tenant-scoped login surface —
    //                          the issuer is sole (D1).
    //   application          - a business runtime. Never serves the
    //                          federation surface, even when the
    //                          request arrives on the auth host
    //                          (mispointed vhost / copied env); and
    //                          once federated it also stops serving
    //                          the legacy login surface — the cutover
    //                          is committed.
    // Broken pairings refuse to boot; surface effects fail closed per
    // request (the gates read this per request, like the host gate).
    'runtime' => env('AUTH_RUNTIME', 'monolith'),

    // Host that serves the auth surface (e.g. auth.navdhya.com in prod,
    // auth.localhost in dev). Empty = federation routes not mounted.
    'domain' => env('AUTH_DOMAIN', ''),

    // Canonical issuer URL. Must equal the externally reachable origin of
    // the auth surface; every token `iss` claim is pinned to it.
    'issuer' => env('AUTH_ISSUER', ''),

    // D2: hard freshness bound for cached revocation/session status on
    // resource servers. Ordinary API decisions may not rely on status
    // older than this; revocation events invalidate sooner.
    'revocation_status_ttl_seconds' => (int) env('AUTH_REVOCATION_STATUS_TTL', 30),

    // D4: bounded retry window in which a refresh exchange with the SAME
    // client + idempotency key + request fingerprint may recover its
    // original response (lost-response recovery, not a replay grace
    // period). Outside the window a consumed token is treated as reuse.
    'refresh_retry_window_seconds' => (int) env('AUTH_REFRESH_RETRY_WINDOW', 60),

    // D3: retired signing public keys stay published in JWKS during the
    // rotation overlap window so already-issued tokens keep verifying
    // while new tokens use the current key. auth:rotate-signing-key
    // archives retired PUBLIC halves here — never private material.
    'retired_signing_keys_dir' => env('AUTH_RETIRED_SIGNING_KEYS_DIR', storage_path('oauth-retired-keys')),

    // D3 (custody): where the token-signing private key lives.
    //   file - inline PASSPORT_PRIVATE_KEY or the oauth-private.key pair
    //          at the Passport key path (default; today's deployment).
    //   kms  - an external KMS/HSM custody: the private half never
    //          exists in process memory; minting calls the custody's
    //          sign endpoint (KmsHttpSigner over SignsTokenRemotely,
    //          bound automatically when this custody is configured).
    //          An issuer runtime boots under this custody only when
    //          the config validates AND the signer port is bound —
    //          otherwise it REFUSES the boot: a JWKS-published KMS key
    //          nothing can sign under (or a league fallback to local
    //          files) is exactly the "publish one key, sign with
    //          another" failure (fail closed, never degrade).
    // Unknown custodies refuse the boot too: custody is a security
    // boundary, not a default.
    'signing' => [
        'custody' => env('AUTH_SIGNING_CUSTODY', 'file'),
        'kms' => [
            'key_resource' => env('AUTH_SIGNING_KMS_KEY_RESOURCE', ''),
            'endpoint' => env('AUTH_SIGNING_KMS_ENDPOINT', ''),
            'public_key' => env('AUTH_SIGNING_KMS_PUBLIC_KEY', ''),
            // Static bearer for the shim. Request signing/mTLS belongs
            // to the shim's deployment hardening, not this client.
            'auth_token' => env('AUTH_SIGNING_KMS_AUTH_TOKEN', ''),
            'timeout_seconds' => (int) env('AUTH_SIGNING_KMS_TIMEOUT', 3),
        ],
    ],

    // D3 (RFC 7523): longest accepted client_assertion lifetime (exp-iat).
    // Short by design — assertions are transport credentials, not
    // sessions — and the jti single-use window never exceeds it.
    'client_assertion_max_ttl_seconds' => (int) env('AUTH_CLIENT_ASSERTION_TTL', 300),

    // OAuth server-side clock skew tolerance for exp/nbf checks.
    'clock_skew_seconds' => (int) env('AUTH_CLOCK_SKEW', 60),

    // Access-token lifetime for federated sessions (D2 binds revocation
    // propagation independently of this value).
    // SEC-003 §3.2 ceiling: access tokens live at most 15 min (900 s).
    // AUTH_ACCESS_TOKEN_TTL may only SHORTEN (e.g. 90 s) — anything
    // longer clamps here so the probe's advertised value and the TTL
    // Passport actually mints are always the same number.
    'access_token_ttl_seconds' => min(900, (int) env('AUTH_ACCESS_TOKEN_TTL', 900)),

    // SSO ticket cookie. The __Secure- prefix requires TLS; on plain-http
    // dev the ticket is issued without the prefix (config-gated, never in
    // prod-over-http).
    'sso' => [
        'cookie_name' => env('AUTH_SSO_COOKIE', 'nv_sso'),
        'cookie_domain' => env('AUTH_SSO_COOKIE_DOMAIN', ''),
        'ttl_seconds' => (int) env('AUTH_SSO_TTL', 86400),
        'require_secure' => (bool) env('AUTH_SSO_REQUIRE_SECURE', true),
    ],
];
