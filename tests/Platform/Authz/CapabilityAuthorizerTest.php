<?php

namespace Tests\Platform\Authz;

use App\Models\User;
use App\Platform\Authz\CapabilityAuthorizer;
use App\Platform\Authz\Exceptions\AuthorizationDeniedException;
use App\Platform\Authz\Models\MembershipRoleAssignment;
use App\Platform\Authz\Models\Role;
use Tests\TenantIntegrationTestCase;

/**
 * Kernel capability authorizer — the Phase-0K floor of the SEC-022 chain.
 * Fail-closed at every step: null principal, unresolved context, missing
 * membership, absent/disabled grant all deny (CON-003).
 */
final class CapabilityAuthorizerTest extends TenantIntegrationTestCase
{
    public function test_null_principal_is_denied(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        $this->assertFalse(
            app(CapabilityAuthorizer::class)->allows(null, 'platform:policy:view'),
        );
    }

    public function test_unresolved_context_denies_even_with_a_grant(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        $membership = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $membership, ['platform:policy:view']);
        $user = User::findOrFail($membership->user_id);

        $this->clearContext();

        $this->assertFalse(
            app(CapabilityAuthorizer::class)->allows($user, 'platform:policy:view'),
            'An unresolved TenantContext must fail closed regardless of stored grants.',
        );
    }

    public function test_granted_capability_is_allowed_and_ungranted_denied(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->resolveContext($tenant, $membership);
        $this->grantCapabilities($tenant, $membership, ['platform:policy:view', 'platform:policy:configure']);

        $user = User::findOrFail($membership->user_id);
        $authorizer = app(CapabilityAuthorizer::class);

        $this->assertTrue($authorizer->allows($user, 'platform:policy:view'));
        $this->assertTrue($authorizer->allows($user, 'platform:policy:configure'));
        $this->assertFalse($authorizer->allows($user, 'platform:policy:delete'));
    }

    public function test_ensure_throws_with_capability_and_reason(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->resolveContext($tenant, $membership);

        $user = User::findOrFail($membership->user_id);

        try {
            app(CapabilityAuthorizer::class)->ensure($user, 'platform:policy:configure');
            $this->fail('Expected AuthorizationDeniedException.');
        } catch (AuthorizationDeniedException $exception) {
            $this->assertSame('platform:policy:configure', $exception->capability);
            $this->assertSame('capability_not_granted', $exception->reason);
        }
    }

    public function test_ensure_throws_unauthenticated_reason_for_null_principal(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        try {
            app(CapabilityAuthorizer::class)->ensure(null, 'platform:policy:view');
            $this->fail('Expected AuthorizationDeniedException.');
        } catch (AuthorizationDeniedException $exception) {
            $this->assertSame('unauthenticated', $exception->reason);
        }
    }

    public function test_disabled_role_does_not_grant(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->resolveContext($tenant, $membership);
        $role = $this->grantCapabilities($tenant, $membership, ['platform:policy:view']);

        $user = User::findOrFail($membership->user_id);
        $authorizer = app(CapabilityAuthorizer::class);

        $this->assertTrue($authorizer->allows($user, 'platform:policy:view'));

        $role->update(['state' => Role::STATE_DISABLED]);

        $this->assertFalse($authorizer->allows($user, 'platform:policy:view'));
    }

    public function test_revoked_assignment_denies_immediately(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->resolveContext($tenant, $membership);
        $this->grantCapabilities($tenant, $membership, ['platform:policy:view']);

        $user = User::findOrFail($membership->user_id);
        $authorizer = app(CapabilityAuthorizer::class);

        $this->assertTrue($authorizer->allows($user, 'platform:policy:view'));

        MembershipRoleAssignment::query()
            ->where('membership_id', $membership->getKey())
            ->delete();

        $this->assertFalse(
            $authorizer->allows($user, 'platform:policy:view'),
            'Revocation must deny within the same request cycle.',
        );
    }

    public function test_grants_from_another_tenant_do_not_leak(): void
    {
        $tenantA = $this->makeTenant();
        $tenantB = $this->makeTenant();

        $membershipA = $this->makeMembership($tenantA);
        $this->resolveContext($tenantA, $membershipA);
        $this->grantCapabilities($tenantA, $membershipA, ['platform:policy:view']);

        $membershipB = $this->makeMembership($tenantB);
        $userB = User::findOrFail($membershipB->user_id);

        // Resolve tenant B: user B holds no grants at all.
        $this->resolveContext($tenantB, $membershipB);
        $this->assertFalse(app(CapabilityAuthorizer::class)->allows($userB, 'platform:policy:view'));

        // And tenant A's role/permission rows are invisible from tenant B.
        $this->assertSame(0, Role::count());
    }
}
