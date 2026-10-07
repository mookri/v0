<?php

use App\Platform\Api\ErrorEnvelope;
use App\Platform\Authz\Http\Middleware\CheckCapability;
use App\Platform\Authz\Http\Middleware\CheckScope;
use App\Platform\Authz\Http\Middleware\RequiresAcr;
use App\Platform\Authz\Http\Middleware\ThrottleTier;
use App\Platform\Http\Middleware\AttachRequestId;
use App\Platform\Tenancy\Http\Middleware\ResolveTenantContext;
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

        $middleware->alias([
            'tenant' => ResolveTenantContext::class,
            'capability' => CheckCapability::class,
            'scope' => CheckScope::class,
            'throttle.tier' => ThrottleTier::class,
            'stepup' => RequiresAcr::class,
            'auth.hmac' => \App\Platform\Identity\Http\Middleware\AuthenticateHmacToken::class,
            'auth.any' => \App\Platform\Identity\Http\Middleware\AuthenticateAny::class,
            'limit.body' => \App\Platform\Http\Middleware\LimitJsonBody::class,
            // 'stepup' lands with the token platform (docs 03-04).
        ]);

        $middleware->appendToGroup('api', 'limit.body');

        // Session context for API reads only (doc 06 §8.4): interactive
        // admin-shell components fetch module JSON with the web session's
        // user. Unsafe methods never start a session, so cookie-authenticated
        // writes stay impossible and the CSRF posture does not move.
        $middleware->appendToGroup('api', \App\Platform\Http\Middleware\SessionContextForReads::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // JSON error envelope for API clients (doc 06 §4). Blade/admin
        // requests keep the framework's default error rendering.
        $exceptions->render(function (Throwable $e, Request $request) {
            return ErrorEnvelope::render($request, $e);
        });
    })->create();
