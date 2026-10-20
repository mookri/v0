<?php

use App\Platform\Identity\Federation\FederationHostGate;
use App\Platform\Identity\Federation\Http\Controllers\FederationLoginController;
use App\Platform\Identity\Federation\Http\Controllers\FederationProbeController;
use Illuminate\Support\Facades\Route;

// Federation route scope (ADR-0020): everything served under the
// federation domain (AUTH_DOMAIN: prod auth.navdhya.com, dev
// auth.localhost). FederationHostGate fails closed on every host that
// is not the configured domain, so this file is required
// unconditionally: direct-mode deployments 404 the whole scope. No
// business route may ever be registered here (D1).

Route::middleware([FederationHostGate::class, 'throttle:120,1'])->group(function (): void {
    Route::get('/auth/health', [FederationProbeController::class, '__invoke'])
        ->name('federation.health');

    // Unified login ceremony (ADR-0020 D1): no signup route exists —
    // a verified proof auto-provisions on the server. The page is NOT
    // behind `guest` (the controller renders the signed-in state and
    // honors a resume target); credential posts carry tighter
    // throttles on top of the scope-wide one.
    Route::get('/auth/login', [FederationLoginController::class, 'create'])
        ->name('federation.login');

    // Default landing / whoami for a session established here.
    Route::get('/auth/session', [FederationLoginController::class, 'session'])
        ->name('federation.session');

    Route::post('/auth/login/otp', [FederationLoginController::class, 'requestOtp'])
        ->middleware('throttle:10,1')
        ->name('federation.login.otp');

    Route::post('/auth/login/otp/resend', [FederationLoginController::class, 'resendOtp'])
        ->middleware('throttle:10,1')
        ->name('federation.login.otp.resend');

    Route::post('/auth/login/verify', [FederationLoginController::class, 'verify'])
        ->middleware('throttle:30,1')
        ->name('federation.login.verify');

    Route::post('/auth/login/password', [FederationLoginController::class, 'password'])
        ->middleware('throttle:10,1')
        ->name('federation.login.password');

    Route::post('/auth/login/totp', [FederationLoginController::class, 'totp'])
        ->middleware('throttle:10,1')
        ->name('federation.login.totp');

    // Idempotent logout (no `auth` middleware): SortedMiddleware would
    // hoist `auth` above the host gate, turning an off-domain POST into
    // a 401 that leaks the route's existence — every federation route
    // must 404 identically off-domain. A guest logout is a harmless
    // no-op that still invalidates whatever session came with it.
    Route::post('/auth/logout', [FederationLoginController::class, 'destroy'])
        ->middleware('throttle:30,1')
        ->name('federation.logout');
});
