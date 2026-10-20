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
use App\Platform\Authz\Http\Middleware\CheckCapability;
use App\Platform\Authz\Http\Middleware\RequiresAcr;
use App\Platform\Identity\Http\Middleware\AuthenticateAny;
use App\Platform\Tenancy\Http\Middleware\ResolveTenantContext;
use Illuminate\Support\Facades\Route;

Route::prefix(config('platform.api_version', 'v1'))
    ->middleware(['auth', ResolveTenantContext::class])
    ->name('platform.')
    ->group(function (): void {
        Route::get('policies', [PolicyController::class, 'index'])
            ->middleware([CheckCapability::class . ':platform:policy:view'])
            ->name('policies.index');

        Route::post('policies', [PolicyController::class, 'store'])
            ->middleware([CheckCapability::class . ':platform:policy:configure'])
            ->name('policies.store');

        Route::get('policies/{policy}', [PolicyController::class, 'show'])
            ->middleware([CheckCapability::class . ':platform:policy:view'])
            ->name('policies.show');

        Route::patch('policies/{policy}', [PolicyController::class, 'update'])
            ->middleware([CheckCapability::class . ':platform:policy:configure'])
            ->name('policies.update');

        // Verb sub-paths for state transitions (doc 06 §2.1 colon-verb
        // convention); raw status fields are never writable (doc 06 §2.2).
        Route::post('policies/{policy}:activate', [PolicyController::class, 'activate'])
            ->middleware([CheckCapability::class . ':platform:policy:configure'])
            ->name('policies.activate');

        Route::post('policies/{policy}:disable', [PolicyController::class, 'disable'])
            ->middleware([CheckCapability::class . ':platform:policy:configure'])
            ->name('policies.disable');

        // Restricted SEC-022 chain-trace debugging (T-0K01-02.3): only
        // principals holding platform:authz:explain (sensitive) may
        // explain decisions, and only for memberships visible in their
        // own tenant (AC-T-0K01-02-4).
        Route::get('authz/explain', [AuthzExplainController::class, 'explain'])
            ->middleware([CheckCapability::class . ':platform:authz:explain'])
            ->name('authz.explain');
    });

// Platform-global exemption (TEN-006 §4.3): tenant self-service is how
// clients discover enterable tenants, so it mounts WITHOUT the `tenant`
// middleware. Dual-accept auth (auth.hmac: session passthrough + frontend
// bearer verification); StartSession runs group-wide so cookie sessions
// resolve on every surface here, not just identity.
Route::prefix(config('platform.api_version', 'v1'))
    ->middleware([AuthenticateAny::class, \Illuminate\Session\Middleware\StartSession::class])
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
        Route::post('identity/sessions/{id}:revoke', [\App\Platform\Identity\Http\Controllers\IdentitySelfServiceController::class, 'revokeSession'])
            ->name('identity.sessions.revoke');
        Route::get('identity/mfa', [\App\Platform\Identity\Http\Controllers\MfaController::class, 'status'])
            ->name('identity.mfa.status');
        Route::post('identity/mfa/enroll/step-up', [\App\Platform\Identity\Http\Controllers\MfaController::class, 'enrollStepUp'])
            ->name('identity.mfa.enroll.step-up');
        Route::post('identity/mfa/enroll/verify', [\App\Platform\Identity\Http\Controllers\MfaController::class, 'enrollVerify'])
            ->name('identity.mfa.enroll.verify');
        Route::post('identity/mfa/enroll', [\App\Platform\Identity\Http\Controllers\MfaController::class, 'enroll'])
            ->name('identity.mfa.enroll');
        Route::post('identity/mfa/confirm', [\App\Platform\Identity\Http\Controllers\MfaController::class, 'confirm'])
            ->middleware('throttle:10,1')
            ->name('identity.mfa.confirm');
        Route::post('identity/mfa/verify', [\App\Platform\Identity\Http\Controllers\MfaController::class, 'verify'])
            ->middleware('throttle:10,1')
            ->name('identity.mfa.verify');
        Route::post('identity/mfa/revoke', [\App\Platform\Identity\Http\Controllers\MfaController::class, 'revoke'])
            ->middleware('throttle:10,1')
            ->name('identity.mfa.revoke');
        Route::post('identity/mfa/recovery-codes', [\App\Platform\Identity\Http\Controllers\MfaController::class, 'recoveryCodes'])
            ->middleware('throttle:10,1')
            ->name('identity.mfa.recovery-codes');
        Route::get('identity/invites', [\App\Platform\Identity\Http\Controllers\InviteController::class, 'index'])
            ->name('identity.invites.index');
        Route::post('identity/invites', [\App\Platform\Identity\Http\Controllers\InviteController::class, 'issue'])
            ->name('identity.invites.issue');
        Route::post('identity/invites/{id}:revoke', [\App\Platform\Identity\Http\Controllers\InviteController::class, 'revoke'])
            ->name('identity.invites.revoke');
        // Machine API keys enforce `platform:apikey:view|manage` INSIDE
        // ApiKeyController::scopeToTenant (same seam as role/SoD: these
        // routes carry `tenant_id` in the query/body, ResolveTenantContext
        // is header-only for `api/*`). Do not "tidy" into route middleware.
        Route::get('platform/api-keys', [\App\Platform\Identity\Http\Controllers\ApiKeyController::class, 'index'])
            ->name('api-keys.index');
        Route::post('platform/api-keys', [\App\Platform\Identity\Http\Controllers\ApiKeyController::class, 'issue'])
            ->middleware([RequiresAcr::class . ':2', \App\Platform\Identity\Http\Middleware\RequiresCurrentAuthz::class])
            ->name('api-keys.issue');
        Route::post('platform/api-keys/{id}:revoke', [\App\Platform\Identity\Http\Controllers\ApiKeyController::class, 'revoke'])
            ->middleware([RequiresAcr::class . ':2', \App\Platform\Identity\Http\Middleware\RequiresCurrentAuthz::class])
            ->name('api-keys.revoke');
        Route::post('platform/api-keys/{id}:rotate', [\App\Platform\Identity\Http\Controllers\ApiKeyController::class, 'rotate'])
            ->middleware([RequiresAcr::class . ':2', \App\Platform\Identity\Http\Middleware\RequiresCurrentAuthz::class])
            ->name('api-keys.rotate');
        Route::get('platform/oauth/clients', [\App\Platform\Identity\Http\Controllers\OAuthClientController::class, 'index'])
            ->middleware([ResolveTenantContext::class, CheckCapability::class . ':platform:user:view'])
            ->name('oauth-clients.index');
        Route::post('platform/oauth/clients', [\App\Platform\Identity\Http\Controllers\OAuthClientController::class, 'store'])
            ->middleware([ResolveTenantContext::class, CheckCapability::class . ':platform:user:manage', RequiresAcr::class . ':2', \App\Platform\Identity\Http\Middleware\RequiresCurrentAuthz::class])
            ->name('oauth-clients.store');
        Route::get('platform/oauth/clients/{id}', [\App\Platform\Identity\Http\Controllers\OAuthClientController::class, 'show'])
            ->middleware([ResolveTenantContext::class, CheckCapability::class . ':platform:user:view'])
            ->name('oauth-clients.show');
        Route::post('platform/oauth/clients/{id}:revoke', [\App\Platform\Identity\Http\Controllers\OAuthClientController::class, 'revoke'])
            ->middleware([ResolveTenantContext::class, CheckCapability::class . ':platform:user:manage', RequiresAcr::class . ':2', \App\Platform\Identity\Http\Middleware\RequiresCurrentAuthz::class])
            ->name('oauth-clients.revoke');
        Route::get('platform/users', [\App\Platform\Identity\Http\Controllers\UserAdminController::class, 'index'])
            ->middleware([ResolveTenantContext::class, CheckCapability::class . ':platform:user:view'])
            ->name('users.index');
        Route::post('platform/users/{id}:suspend', [\App\Platform\Identity\Http\Controllers\UserAdminController::class, 'suspend'])
            ->middleware([ResolveTenantContext::class, CheckCapability::class . ':platform:user:manage', RequiresAcr::class . ':2', \App\Platform\Identity\Http\Middleware\RequiresCurrentAuthz::class])
            ->name('users.suspend');
        Route::post('platform/users/{id}:activate', [\App\Platform\Identity\Http\Controllers\UserAdminController::class, 'activate'])
            ->middleware([ResolveTenantContext::class, CheckCapability::class . ':platform:user:manage', RequiresAcr::class . ':2', \App\Platform\Identity\Http\Middleware\RequiresCurrentAuthz::class])
            ->name('users.activate');
        Route::post('platform/users/{id}:deactivate', [\App\Platform\Identity\Http\Controllers\UserAdminController::class, 'deactivate'])
            ->middleware([ResolveTenantContext::class, CheckCapability::class . ':platform:user:manage', RequiresAcr::class . ':2', \App\Platform\Identity\Http\Middleware\RequiresCurrentAuthz::class])
            ->name('users.deactivate');
        // Read-only audit trail for the console (W2): tenant-scoped, gated by
        // platform:audit:view. AuditLog rows are hash-chained and written in
        // the same transaction as the domain change, so this only reads.
        Route::get('platform/audit/logs', [\App\Platform\Audit\Http\Controllers\AuditLogController::class, 'index'])
            ->middleware([ResolveTenantContext::class, CheckCapability::class . ':platform:audit:view'])
            ->name('audit.logs.index');
        // W2 cross-module read surfaces (stats / settings / billing): each is
        // a tenant-scoped aggregate or config read, gated by its own capability.
        Route::get('platform/stats', [\App\Http\Controllers\Ops\StatsController::class, 'index'])
            ->middleware([ResolveTenantContext::class, CheckCapability::class . ':platform:ops:view'])
            ->name('stats.index');
        Route::get('platform/settings', [\App\Http\Controllers\Ops\SettingsController::class, 'index'])
            ->middleware([ResolveTenantContext::class, CheckCapability::class . ':platform:config:manage'])
            ->name('settings.index');
        Route::get('platform/billing', [\App\Http\Controllers\Ops\BillingController::class, 'index'])
            ->middleware([ResolveTenantContext::class, CheckCapability::class . ':platform:billing:view'])
            ->name('billing.index');
        // W2 cross-module ops roll-ups (bookings / content / seo / payouts):
        // platform-wide read surfaces for the admin console, all gated by
        // platform:ops:view. bookings + payouts are tenant-scoped; content + seo
        // are central (global) tables.
        Route::get('platform/ops/bookings', [\App\Http\Controllers\Ops\BookingsController::class, 'index'])
            ->middleware([ResolveTenantContext::class, CheckCapability::class . ':platform:ops:view'])
            ->name('ops.bookings.index');
        Route::get('platform/ops/content', [\App\Http\Controllers\Ops\ContentController::class, 'index'])
            ->middleware([ResolveTenantContext::class, CheckCapability::class . ':platform:ops:view'])
            ->name('ops.content.index');
        Route::get('platform/ops/seo', [\App\Http\Controllers\Ops\SeoController::class, 'index'])
            ->middleware([ResolveTenantContext::class, CheckCapability::class . ':platform:ops:view'])
            ->name('ops.seo.index');
        Route::get('platform/ops/payouts', [\App\Http\Controllers\Ops\PayoutsController::class, 'index'])
            ->middleware([ResolveTenantContext::class, CheckCapability::class . ':platform:ops:view'])
            ->name('ops.payouts.index');
        // W2 security + system read surfaces: security is tenant-scoped (MFA
        // adoption is reported platform-wide since MFA proofs are user-keyed),
        // system health is platform-global.
        Route::get('platform/security', [\App\Http\Controllers\Ops\SecurityController::class, 'index'])
            ->middleware([ResolveTenantContext::class, CheckCapability::class . ':platform:ops:view'])
            ->name('security.index');
        Route::get('platform/system', [\App\Http\Controllers\Ops\SystemController::class, 'index'])
            ->middleware([CheckCapability::class . ':platform:ops:view'])
            ->name('system.index');
        // Role + SoD administration enforces `platform:role:view|manage` and
        // `platform:sod:view|configure` INSIDE RoleController /
        // SodRuleController::scopeToTenant rather than through a
        // `ResolveTenantContext` + `CheckCapability` middleware pair. These
        // routes carry `tenant_id` in the query/body (their wire contract),
        // while ResolveTenantContext reads that header EXCLUSIVELY for
        // `api/*` — a correct fail-closed posture this change does not
        // amend — and the engine needs a TenantContext the controller only
        // owns once it has scoped. The gate therefore runs at that seam,
        // before any row is read or written. Do not "tidy" these into route
        // middleware without also moving them onto the X-Tenant-Id contract.
        Route::get('platform/roles', [\App\Platform\Authz\Http\Controllers\RoleController::class, 'index'])
            ->name('roles.index');
        Route::post('platform/roles', [\App\Platform\Authz\Http\Controllers\RoleController::class, 'store'])
            ->name('roles.store');
        Route::get('platform/memberships/{membershipId}/roles', [\App\Platform\Authz\Http\Controllers\RoleController::class, 'assignments'])
            ->name('memberships.roles.index');
        Route::post('platform/memberships/{membershipId}/roles', [\App\Platform\Authz\Http\Controllers\RoleController::class, 'grant'])
            ->middleware([RequiresAcr::class . ':2', \App\Platform\Identity\Http\Middleware\RequiresCurrentAuthz::class])
            ->name('memberships.roles.grant');
        Route::delete('platform/memberships/{membershipId}/roles/{assignmentId}', [\App\Platform\Authz\Http\Controllers\RoleController::class, 'revoke'])
            ->middleware([RequiresAcr::class . ':2', \App\Platform\Identity\Http\Middleware\RequiresCurrentAuthz::class])
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

// RFC 7009 revocation carries the client credential in the body (like
// invite acceptance): public route by design, throttled, never session
// authenticated. Lives under /api next to Passport's own /oauth/*
// vendor surface (this file is api-prefixed by the route provider).
Route::name('oauth.')
    ->group(function (): void {
        Route::post('oauth/revoke', [\App\Platform\Identity\OAuth\Http\Controllers\RevokeController::class, '__invoke'])
            ->middleware('throttle:10,1')
            ->name('revoke');
        Route::post('oauth/introspect', [\App\Platform\Identity\OAuth\Http\Controllers\IntrospectController::class, '__invoke'])
            ->middleware('throttle:10,1')
            ->name('introspect');
        // RFC 9126 pushed authorization: client-authenticated back
        // channel (secret_post or private_key_jwt), league-shape JSON
        // only, never a redirect — throttled like the token endpoint
        // (one push per login flow per BFF instance).
        Route::post('oauth/par', [\App\Platform\Identity\OAuth\Http\Controllers\PushedAuthorizationRequestController::class, '__invoke'])
            ->middleware('throttle:60,1')
            ->name('par');
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
