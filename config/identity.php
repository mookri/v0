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
];
