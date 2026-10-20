<?php

/*
|--------------------------------------------------------------------------
| Identity Token Bridge
|--------------------------------------------------------------------------
|
| HMAC actor-token verification for frontend-minted bearer tokens
| (ActorTokenVerifier). Mirrors SESSION_SIGNING_KEY on the Next.js side;
| empty in tests (each test supplies its own secret explicitly).
|
*/

return [
    'hmac_secret' => env('IDENTITY_HMAC_SECRET', ''),

    // Key for the auth_events chain seal (ADR-0020 D5). The chain seal
    // is an HMAC, not a bare digest: an unkeyed hash only proves a row
    // was not edited *without* recomputing the hash. Defaults to the
    // identity HMAC secret; set a dedicated key to separate the two
    // blast radii.
    'chain_signing_key' => env('FEDERATION_CHAIN_SIGNING_KEY', ''),
];
