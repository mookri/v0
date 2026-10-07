<?php

use App\Platform\Observability\Health\ReadinessCheck;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Platform Web Surfaces (doc 06 §11)
|--------------------------------------------------------------------------
|
| Kernel-owned web endpoints. Public discovery & health surfaces first,
| then the admin console's shared write actions (doc 06 §7). This file is
| required from routes/web.php BEFORE the Velzon admin shell catch-all,
| inside the `web` middleware group (CSRF + session cookie included).
| `/up` is registered by the framework via the `health` key in
| bootstrap/app.php.
|
*/

Route::get('/ready', ReadinessCheck::class)->name('platform.ready');

// OAuth discovery surfaces (SEC-002 §2.1, partial): public metadata,
// JWKS, and the credential-bearing userinfo endpoint. Throttled like
// any public surface; userinfo resolves through auth.any (dual-accept
// covers Passport JWTs) with no tenant scope — it is identity-level.
Route::get('/.well-known/oauth-authorization-server', [\App\Platform\Identity\OAuth\Http\Controllers\DiscoveryController::class, 'metadata'])
    ->middleware('throttle:60,1')
    ->name('platform.oauth-metadata');
Route::get('/.well-known/jwks.json', [\App\Platform\Identity\OAuth\Http\Controllers\DiscoveryController::class, 'jwks'])
    ->middleware('throttle:60,1')
    ->name('platform.jwks');
Route::get('/oauth/userinfo', [\App\Platform\Identity\OAuth\Http\Controllers\DiscoveryController::class, 'userinfo'])
    ->middleware(['auth.any', 'throttle:60,1'])
    ->name('platform.userinfo');

// Step-up form (SEC-004 §6, doc 06 §7.5): GET renders the TOTP/recovery
// form — or, fail-closed, the enrollment-required notice — and answers
// the console's freshness probe as JSON. POST shares the API's MFA verify
// action, so the proof row it writes is exactly what `stepup:2` reads;
// return_to is clamped to same-site paths on both sides.
Route::get('/oauth/stepup', [\App\Platform\Identity\Http\Controllers\StepUpController::class, 'show'])
    ->middleware('auth')
    ->name('oauth.stepup');
Route::post('/oauth/stepup', [\App\Platform\Identity\Http\Controllers\MfaController::class, 'verify'])
    ->middleware('auth');

// Shared admin write actions (doc 06 §7.1-7.2): the console's forms and
// the /api/v1 endpoints point at the SAME controller actions — session +
// CSRF here (web group), bearer over there, one service and validation
// path underneath. Responses negotiate inside WebMutations: web form
// posts redirect back with a coded flash, /api/v1 keeps its JSON envelope
// untouched. The workspace travels as a hidden `tenant_id` form field
// (forms cannot set headers); ResolveTenantContext reads it only for
// non-api paths, so API header posture does not move. Step-up-gated
// mutations: user suspend/activate and role grant/revoke share their
// API actions behind the same guards the API uses (stepup:2 satisfied
// by the /oauth/stepup form below — doc 06 §7.5); api-keys stay
// API-only until their console surface lands.
Route::group(['prefix' => '/platform', 'middleware' => 'auth'], function (): void {
    Route::post('/policies', [\App\Platform\Authz\Http\Controllers\PolicyController::class, 'store'])
        ->middleware(['tenant', 'capability:platform:policy:configure']);
    Route::post('/policies/{policy}:activate', [\App\Platform\Authz\Http\Controllers\PolicyController::class, 'activate'])
        ->middleware(['tenant', 'capability:platform:policy:configure']);
    Route::post('/policies/{policy}:disable', [\App\Platform\Authz\Http\Controllers\PolicyController::class, 'disable'])
        ->middleware(['tenant', 'capability:platform:policy:configure']);
    Route::patch('/policies/{policy}', [\App\Platform\Authz\Http\Controllers\PolicyController::class, 'update'])
        ->middleware(['tenant', 'capability:platform:policy:configure']);
    Route::post('/roles', [\App\Platform\Authz\Http\Controllers\RoleController::class, 'store']);
    Route::post('/users/{id}:suspend', [\App\Platform\Identity\Http\Controllers\UserAdminController::class, 'suspend'])
        ->middleware(['tenant', 'capability:platform:user:manage', 'stepup:2']);
    Route::post('/users/{id}:activate', [\App\Platform\Identity\Http\Controllers\UserAdminController::class, 'activate'])
        ->middleware(['tenant', 'capability:platform:user:manage', 'stepup:2']);
    // Grant/revoke mirror the API exactly (route: stepup:2 only; the
    // action's scopeToTenant does the issuer/tenant work; SoD runs on
    // first-time grants).
    Route::post('/memberships/{membershipId}/roles', [\App\Platform\Authz\Http\Controllers\RoleController::class, 'grant'])
        ->middleware('stepup:2');
    Route::delete('/memberships/{membershipId}/roles/{assignmentId}', [\App\Platform\Authz\Http\Controllers\RoleController::class, 'revoke'])
        ->middleware('stepup:2');
    Route::post('/sod-rules', [\App\Platform\Authz\Http\Controllers\SodRuleController::class, 'store']);
    Route::patch('/sod-rules/{id}', [\App\Platform\Authz\Http\Controllers\SodRuleController::class, 'update']);
    Route::delete('/sod-rules/{id}', [\App\Platform\Authz\Http\Controllers\SodRuleController::class, 'destroy']);
});
