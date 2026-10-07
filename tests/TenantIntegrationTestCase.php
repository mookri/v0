<?php

namespace Tests;

use App\Models\User;
use App\Platform\Audit\AuditLog;
use App\Platform\Authz\Models\MembershipRoleAssignment;
use App\Platform\Authz\Models\Role;
use App\Platform\Authz\Models\RolePermission;
use App\Platform\Identity\Models\MfaStepUp;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\Middleware\StartSession;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * Base class for kernel tests that touch the database (doc 02, doc 14 §3).
 *
 * RefreshDatabase runs the migrations on BOTH kernel connections (each
 * migration declares its connection explicitly) and wraps every test in a
 * transaction on each, so central + tenant_shared writes roll back
 * together. Ships the actingAsMembership / grant / audit helpers promised
 * by KernelTestCase.
 */
abstract class TenantIntegrationTestCase extends KernelTestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    protected $connectionsToTransact = ['central', 'tenant_shared'];

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makeTenant(array $attributes = []): Tenant
    {
        return Tenant::factory()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makeMembership(Tenant $tenant, ?User $user = null, array $attributes = []): TenantMembership
    {
        return TenantMembership::factory()->create([
            'tenant_id' => $tenant->getKey(),
            'user_id' => ($user ?? User::factory()->create())->id,
            ...$attributes,
        ]);
    }

    protected function resolveContext(Tenant $tenant, ?TenantMembership $membership = null): void
    {
        // Explicit scope transition: clear-then-set. Production code must
        // never resolve twice without clearing (TenantContext enforces it).
        app(TenantContext::class)->clear();
        app(TenantContext::class)->set(
            (string) $tenant->getKey(),
            $membership !== null ? (string) $membership->getKey() : null,
        );
    }

    protected function clearContext(): void
    {
        app(TenantContext::class)->clear();
    }

    /**
     * Authenticate a user holding an active membership in the tenant,
     * granted the given capability codes through a fresh role.
     *
     * @param  list<string>  $capabilities
     * @return array{0: User, 1: TenantMembership}
     */
    protected function actingAsMembership(Tenant $tenant, array $capabilities = []): array
    {
        $user = User::factory()->create();
        $membership = $this->makeMembership($tenant, $user);

        if ($capabilities !== []) {
            $this->grantCapabilities($tenant, $membership, $capabilities);
        }

        $this->actingAs($user);

        return [$user, $membership];
    }

    /**
     * @param  list<string>  $codes
     */
    protected function grantCapabilities(Tenant $tenant, TenantMembership $membership, array $codes): Role
    {
        $context = app(TenantContext::class);
        $alreadyResolved = $context->isResolved();

        if (! $alreadyResolved) {
            $this->resolveContext($tenant, $membership);
        }

        $role = Role::factory()->create();

        foreach ($codes as $code) {
            RolePermission::create(['role_id' => $role->getKey(), 'capability_code' => $code]);
        }

        MembershipRoleAssignment::create([
            'membership_id' => $membership->getKey(),
            'role_id' => $role->getKey(),
        ]);

        if (! $alreadyResolved) {
            $this->clearContext();
        }

        return $role;
    }

    /**
     * Log in over the web surface and return [cookie name, wire value],
     * then drop the resolved guard/session singletons (fresh-process
     * simulation) and switch JSON requests to credentialed mode, so
     * later API requests prove the cookie instead of in-memory state.
     *
     * @return array{string, string}
     */
    protected function loginSessionFor(User $user): array
    {
        // Persistent driver: the array driver is not session-persistent
        // and never issues a cookie to replay.
        config(['session.driver' => 'database']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(302);
        $this->assertAuthenticated();

        $name = (string) config('session.cookie');
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $name) {
                // Web-group cookie: encrypted on the wire, replayed verbatim.
                $value = $cookie->getValue();

                $this->resetSessionState();

                // JSON requests only carry cookies for credentialed (fetch
                // same-origin) calls, like the admin shell's.
                $this->withCredentials();

                return [$name, $value];
            }
        }

        $this->fail('login did not issue a session cookie');
    }

    /**
     * Drop the guard's in-memory user and the resolved session singletons so
     * the next request starts from clean process state and proves its cookie,
     * not this test's login call. StartSession is pinned to the manager it
     * was first resolved with, so it must be forgotten too or it would keep
     * driving a stale store.
     */
    protected function resetSessionState(): void
    {
        $this->app['auth']->forgetGuards();
        $this->app->forgetInstance('session');
        $this->app->forgetInstance('session.store');
        $this->app->forgetInstance(StartSession::class);
    }

    protected function assertAuditRecorded(Tenant $tenant, string $action): void
    {
        $count = AuditLog::withoutGlobalScopes()
            ->where('tenant_id', $tenant->getKey())
            ->where('action', $action)
            ->count();

        PHPUnit::assertGreaterThan(
            0,
            $count,
            sprintf("Expected an audit record with action '%s' for tenant %s.", $action, (string) $tenant->getKey()),
        );
    }

    /**
     * Satisfy the step-up gate for a user: enable their MFA flag and
     * record a fresh proof (bypasses TOTP crypto — the crypto path is
     * covered by MfaTest; this helper tests gate behavior).
     */
    protected function stepUp(User $user, int $minutesAgo = 0): void
    {
        $user->forceFill(['mfa_enabled' => true])->save();

        MfaStepUp::query()->create([
            'user_id' => $user->getAuthIdentifier(),
            'verified_at' => now()->subMinutes($minutesAgo)->toDateTimeString(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function tenantHeaders(Tenant $tenant): array
    {
        return ['X-Tenant-Id' => (string) $tenant->getKey()];
    }
}
