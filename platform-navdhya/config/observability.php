<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Performance instrumentation
    |--------------------------------------------------------------------------
    |
    | `perf_timing` gates the RequestTiming middleware, which emits one
    | structured line per request (request id, route, status, total and
    | SQL time, query count) so the performance harness can join
    | client-observed latency to server-side cost.
    |
    | It lives in config (not an `env()` call inside the middleware)
    | because `php artisan config:cache` freezes the environment: a
    | runtime `env()` lookup returns null under a cached config and the
    | instrumentation would silently switch off in production, blinding
    | the very harness the SLO story depends on.
    |
    | Drill-only. Leave it off outside a performance run.
    |
    */

    'perf_timing' => filter_var(
        env('PERF_TIMING', false),
        FILTER_VALIDATE_BOOLEAN,
    ),

];
