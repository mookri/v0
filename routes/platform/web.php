<?php

use App\Platform\Observability\Health\ReadinessCheck;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Platform Web Surfaces (doc 06 §11)
|--------------------------------------------------------------------------
|
| Kernel-owned, public discovery & health endpoints. This file is
| required from routes/web.php BEFORE the Velzon admin shell catch-all
| (inside the `web` middleware group). `/up` is registered by the
| framework via the `health` key in bootstrap/app.php.
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
