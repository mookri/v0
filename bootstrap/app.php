<?php

use App\Platform\Api\ErrorEnvelope;
use App\Platform\Http\Middleware\AttachRequestId;
use App\Platform\Http\Middleware\LimitJsonBody;
use App\Platform\Http\Middleware\RequestTiming;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Request correlation generated at the edge (doc 05 §11.1).
        $middleware->prepend(AttachRequestId::class);

        // Perf timing (drill-only, PERF_TIMING-gated): prepended after
        // AttachRequestId so it sits outermost and reads the rid that
        // AttachRequestId sets one layer in; no-op when the flag is off.
        $middleware->prepend(RequestTiming::class);

        $middleware->appendToGroup('api', LimitJsonBody::class);

        // Session context for API reads only (doc 06 §8.4): interactive
        // admin-shell components fetch module JSON with the web session's
        // user. Unsafe methods never start a session, so cookie-authenticated
        // writes stay impossible and the CSRF posture does not move.
        $middleware->appendToGroup('api', \App\Platform\Http\Middleware\SessionContextForReads::class);

        // SortedMiddleware matches Authenticate via the AuthenticatesRequests
        // interface and would pull `auth` above the unlisted session reader
        // (session context established too late -> 401 on cookie reads).
        // Pin the reader directly ahead of authentication instead.
        $middleware->prependToPriorityList(
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \App\Platform\Http\Middleware\SessionContextForReads::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // JSON error envelope for API clients (doc 06 §4). Blade/admin
        // requests keep the framework's default error rendering.
        $exceptions->render(function (Throwable $e, Request $request) {
            return ErrorEnvelope::render($request, $e);
        });
    })->create();
