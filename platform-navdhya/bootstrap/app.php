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
        // Rate limit for the whole API surface. The limiter itself is
        // registered in AppServiceProvider::boot(); Laravel only adds
        // it to the `api` group when it is told to, and without it the
        // entire /api/v1 surface was unthrottled for human principals
        // (`throttle.tier` only covers machine clients).
        $middleware->throttleApi('api');

        // Trusted hosts: without them, absolute URLs (password-reset
        // links, the federation login bounce) are built from the Host
        // header, so a forged Host delivers a reset token to the
        // attacker.
        //
        // The list is read through a CALLABLE: this callback runs when
        // the HTTP kernel is resolved, which is BEFORE the
        // configuration is loaded, and a direct env() lookup would
        // return null under php artisan config:cache. The callable
        // is resolved per request, by which time the (cached) config
        // is available.
        $middleware->trustHosts(
            at: fn (): array => (array) config('trusted.hosts', []),
            subdomains: true,
        );

        // Request correlation generated at the edge (doc 05 §11.1).
        $middleware->prepend(AttachRequestId::class);

        // Perf timing (drill-only, PERF_TIMING-gated): prepended after
        // AttachRequestId so it sits outermost and reads the rid that
        // AttachRequestId sets one layer in; no-op when the flag is off.
        $middleware->prepend(RequestTiming::class);

        // Session cookies leave the process encrypted, whichever surface
        // issued them. The web group encrypts through EncryptCookies; the
        // API surface re-issues through SessionContextForReads, and the
        // platform API's group-wide StartSession writes a raw id. This is
        // the outer invariant: a plaintext session id never reaches the
        // browser. It is prepended so it runs outside every other
        // middleware and therefore sees the final cookie value.
        $middleware->prepend(\App\Platform\Http\Middleware\EncryptsSessionCookie::class);

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

        // OIDC RP-Initiated Logout (A-SEC-004-5): the end-session
        // endpoint accepts POST per spec, from RP pages on other
        // origins that cannot carry this app's CSRF token. The logout
        // it performs is validated by id_token_hint + registered
        // post-logout URIs, not by CSRF posture (and a cross-site
        // logout is the accepted trade of any spec GET logout link).
        $middleware->validateCsrfTokens(except: [
            'oauth/logout',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // JSON error envelope for API clients (doc 06 §4). Blade/admin
        // requests keep the framework's default error rendering.
        $exceptions->render(function (Throwable $e, Request $request) {
            return ErrorEnvelope::render($request, $e);
        });

        // On the federation host every unauthenticated browser bounce
        // lands on the unified login (ADR-0020 D1) — Passport's
        // authorize prompt included — and guest() stashes the intended
        // URL so the ceremony resumes exactly where it stopped. Runs
        // after the envelope above (JSON requests already got theirs)
        // and off-domain behavior is untouched: the default `login`
        // redirect stays for every other host.
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if (! \App\Platform\Identity\Federation\FederationHostGate::matches($request)
                || $request->expectsJson()
                || ! $request->hasSession()) {
                return null;
            }

            return redirect()->guest(route('federation.login'));
        });
    })->create();
