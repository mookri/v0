<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing
|--------------------------------------------------------------------------
|
| The API is called from the Next.js apps, which are served from
| different origins than the Laravel host. Without this file CORS is
| entirely OFF: `HandleCors` is in the global stack, but it matches
| `config('cors.paths', [])`, so no path ever matched and no
| `Access-Control-Allow-Origin` header was ever emitted - every
| cross-origin browser call failed the preflight.
|
| The origin list is an explicit allowlist. `*` with credentials is
| never valid (browsers reject it, and it would expose the cookie
| bearing API to any site).
|
*/

$allowedOrigins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')),
)));

return [

    'paths' => ['api/*', 'oauth/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_headers' => ['*'],

    'allowed_origins' => $allowedOrigins,

    'exposed_headers' => ['X-Request-Id'],

    'max_age' => 0,

    'supports_credentials' => true,

];
