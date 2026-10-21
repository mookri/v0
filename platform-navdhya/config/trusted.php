<?php

/*
|--------------------------------------------------------------------------
| Trusted Hosts, Proxies and Origins
|--------------------------------------------------------------------------
|
| These are read from the environment HERE, at config-build time, and
| never at request time: a runtime `env()` lookup returns null under
| `php artisan config:cache`, which would silently empty the trusted
| host list and take the production boot guard down with it.
|
| - `hosts`: the hostnames this deployment answers for. Absolute URLs
|   (password-reset links, the federation login bounce) are built from
|   the Host header, so an empty list means a forged Host can steal a
|   reset token. The application refuses to boot in production without
|   it.
| - `proxies`: the reverse proxies whose X-Forwarded-* headers may be
|   trusted. Behind a TLS-terminating proxy every request otherwise
|   looks like it came from the proxy: the client IP is wrong (which
|   collapses every per-IP rate limit into one bucket) and generated
|   links come out as http://.
| - `cors_origins`: the browser origins allowed to call the API.
|
*/

return [

    'hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TRUSTED_HOSTS', '')),
    ))),

    'proxies' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TRUSTED_PROXIES', '')),
    ))),

    'cors_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')),
    ))),

];
