<?php

/*
|--------------------------------------------------------------------------
| Platform API Surfaces (doc 06 §11)
|--------------------------------------------------------------------------
|
| Kernel-owned /api/v1/platform surfaces. Phase 0K lands the policy CRUD
| admin surface (SEC-021, T-0K01-01.4) at /api/v1/policies and the
| restricted authorization explain surface (SEC-022, T-0K01-02.3) at
| /api/v1/authz/explain, behind the `auth` + `tenant` resolution
| middleware and per-route capability requirements (SEC-022 floor).
| Identity self-service, SCIM, webhook management and usage/limits
| follow in later milestones. This file is the single mount point so
| routes/api.php carries no business routes.
|
*/

use App\Platform\Authz\Http\Controllers\AuthzExplainController;
use App\Platform\Authz\Http\Controllers\PolicyController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('platform.api_version', 'v1'))
    ->middleware(['auth', 'tenant'])
    ->name('platform.')
    ->group(function (): void {
        Route::get('policies', [PolicyController::class, 'index'])
            ->middleware('capability:platform:policy:view')
            ->name('policies.index');

        Route::post('policies', [PolicyController::class, 'store'])
            ->middleware('capability:platform:policy:configure')
            ->name('policies.store');

        Route::get('policies/{policy}', [PolicyController::class, 'show'])
            ->middleware('capability:platform:policy:view')
            ->name('policies.show');

        Route::patch('policies/{policy}', [PolicyController::class, 'update'])
            ->middleware('capability:platform:policy:configure')
            ->name('policies.update');

        // Verb sub-paths for state transitions (doc 06 §2.1 colon-verb
        // convention); raw status fields are never writable (doc 06 §2.2).
        Route::post('policies/{policy}:activate', [PolicyController::class, 'activate'])
            ->middleware('capability:platform:policy:configure')
            ->name('policies.activate');

        Route::post('policies/{policy}:disable', [PolicyController::class, 'disable'])
            ->middleware('capability:platform:policy:configure')
            ->name('policies.disable');

        // Restricted SEC-022 chain-trace debugging (T-0K01-02.3): only
        // principals holding platform:authz:explain (sensitive) may
        // explain decisions, and only for memberships visible in their
        // own tenant (AC-T-0K01-02-4).
        Route::get('authz/explain', [AuthzExplainController::class, 'explain'])
            ->middleware('capability:platform:authz:explain')
            ->name('authz.explain');
    });

// Platform-global exemption (TEN-006 §4.3): tenant self-service is how
// clients discover enterable tenants, so it mounts WITHOUT the `tenant`
// middleware. Dual-accept auth (auth.hmac: session passthrough + frontend
// bearer verification); StartSession runs group-wide so cookie sessions
// resolve on every surface here, not just identity.
Route::prefix(config('platform.api_version', 'v1'))
    ->middleware(['auth.any', \Illuminate\Session\Middleware\StartSession::class])
    ->name('platform.')
    ->group(function (): void {
        Route::get('platform/tenants', [\App\Platform\Tenancy\Http\Controllers\TenantSelfServiceController::class, 'index'])
            ->name('tenants.index');

        // Composed OpenAPI discovery (api-platform §9.1): serves the
        // api:compose artifact as JSON to authenticated clients.
        Route::get('openapi.json', function () {
            $path = base_path('docs/api/openapi.v1.yaml');
            if (! is_file($path)) {
                return response()->json(['error' => ['code' => 'openapi_not_composed', 'message' => 'Run api:compose.', 'request_id' => (string) request()->attributes->get('request_id', '')]], 404);
            }

            return response()->json(\Symfony\Component\Yaml\Yaml::parseFile($path));
        })->name('openapi.json');

        Route::get('identity/profile', [\App\Platform\Identity\Http\Controllers\IdentitySelfServiceController::class, 'profile'])
            ->name('identity.profile');
        Route::patch('identity/profile', [\App\Platform\Identity\Http\Controllers\IdentitySelfServiceController::class, 'updateProfile'])
            ->name('identity.profile.update');
        Route::get('identity/sessions', [\App\Platform\Identity\Http\Controllers\IdentitySelfServiceController::class, 'sessions'])
            ->name('identity.sessions');
        Route::post('identity/sessions:revoke-others', [\App\Platform\Identity\Http\Controllers\IdentitySelfServiceController::class, 'revokeOtherSessions'])
            ->name('identity.sessions.revoke-others');
        Route::post('identity/invites', [\App\Platform\Identity\Http\Controllers\InviteController::class, 'issue'])
            ->name('identity.invites.issue');
        Route::post('identity/invites/{id}:revoke', [\App\Platform\Identity\Http\Controllers\InviteController::class, 'revoke'])
            ->name('identity.invites.revoke');
        Route::get('platform/api-keys', [\App\Platform\Identity\Http\Controllers\ApiKeyController::class, 'index'])
            ->name('api-keys.index');
        Route::post('platform/api-keys', [\App\Platform\Identity\Http\Controllers\ApiKeyController::class, 'issue'])
            ->name('api-keys.issue');
        Route::post('platform/api-keys/{id}:revoke', [\App\Platform\Identity\Http\Controllers\ApiKeyController::class, 'revoke'])
            ->name('api-keys.revoke');
        Route::post('platform/api-keys/{id}:rotate', [\App\Platform\Identity\Http\Controllers\ApiKeyController::class, 'rotate'])
            ->name('api-keys.rotate');
        Route::get('platform/users', [\App\Platform\Identity\Http\Controllers\UserAdminController::class, 'index'])
            ->middleware(['tenant', 'capability:platform:user:view'])
            ->name('users.index');
        Route::post('platform/users/{id}:suspend', [\App\Platform\Identity\Http\Controllers\UserAdminController::class, 'suspend'])
            ->middleware(['tenant', 'capability:platform:user:manage'])
            ->name('users.suspend');
        Route::post('platform/users/{id}:activate', [\App\Platform\Identity\Http\Controllers\UserAdminController::class, 'activate'])
            ->middleware(['tenant', 'capability:platform:user:manage'])
            ->name('users.activate');
        Route::get('platform/roles', [\App\Platform\Authz\Http\Controllers\RoleController::class, 'index'])
            ->name('roles.index');
        Route::post('platform/roles', [\App\Platform\Authz\Http\Controllers\RoleController::class, 'store'])
            ->name('roles.store');
        Route::post('platform/memberships/{membershipId}/roles', [\App\Platform\Authz\Http\Controllers\RoleController::class, 'grant'])
            ->name('memberships.roles.grant');
        Route::delete('platform/memberships/{membershipId}/roles/{assignmentId}', [\App\Platform\Authz\Http\Controllers\RoleController::class, 'revoke'])
            ->name('memberships.roles.revoke');
        Route::get('platform/sod-rules', [\App\Platform\Authz\Http\Controllers\SodRuleController::class, 'index'])
            ->name('sod-rules.index');
        Route::post('platform/sod-rules', [\App\Platform\Authz\Http\Controllers\SodRuleController::class, 'store'])
            ->name('sod-rules.store');
        Route::patch('platform/sod-rules/{id}', [\App\Platform\Authz\Http\Controllers\SodRuleController::class, 'update'])
            ->name('sod-rules.update');
        Route::delete('platform/sod-rules/{id}', [\App\Platform\Authz\Http\Controllers\SodRuleController::class, 'destroy'])
            ->name('sod-rules.destroy');
    });

// Invite acceptance carries its own credential (the token) — public route
// by design, rate-limited at the edge/product tier, never authenticated.
Route::prefix(config('platform.api_version', 'v1'))
    ->name('platform.')
    ->group(function (): void {
        Route::post('identity/invites:accept', [\App\Platform\Identity\Http\Controllers\InviteController::class, 'accept'])
            ->middleware('throttle:10,1')
            ->name('identity.invites.accept');
    });
