<?php

namespace Tests\TenantIsolation;

use App\Models\User;
use App\Modules\Customers\Models\BuyerProfile;
use App\Modules\Customers\Models\CustomerAddress;
use App\Modules\Customers\Models\CustomerNotification;
use App\Modules\Customers\Models\CustomerSetting;
use App\Modules\Customers\Models\SavedItem;
use App\Platform\Audit\AuditLog;
use App\Platform\Authz\Models\CapabilityMetadata;
use App\Platform\Authz\Models\MembershipRoleAssignment;
use App\Platform\Authz\Models\Policy;
use App\Platform\Authz\Models\Role;
use App\Platform\Authz\Models\RolePermission;
use App\Platform\Authz\Models\RoleScopeBinding;
use App\Platform\Tenancy\Models\TenantMembership;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;
use Tests\TenantIsolation\Concerns\AssertTenantIsolation;

/**
 * Tenant Isolation Suite standing matrix (TEN-018, AC-T-0K01-01-5): every
 * BelongsToTenant table introduced by the Phase 0K kernel authorization
 * work — cross-tenant reads return no rows, cross-tenant writes affect
 * zero rows, and unresolvable contexts fail closed.
 */
final class Phase0kIsolationMatrixTest extends TenantIntegrationTestCase
{
    use AssertTenantIsolation;

    public function test_policies_are_isolated(): void
    {
        $this->assertTenantIsolationMatrix(
            Policy::class,
            fn (): array => [
                'name' => 'iso-'.Str::lower(Str::ulid()),
                'effect' => 'allow',
                'capability_selector' => ['module' => 'sales', 'resource' => 'order', 'action' => 'view'],
                'status' => 'draft',
            ],
            ['description' => 'isolation probe'],
        );
    }

    public function test_capability_metadata_is_isolated(): void
    {
        $this->assertTenantIsolationMatrix(
            CapabilityMetadata::class,
            fn (): array => [
                'code' => 'iso:probe_'.Str::lower(Str::ulid()).':view',
                'name' => 'Isolation probe',
                'module' => 'iso',
                'resource' => 'probe',
                'action' => 'view',
                'version' => 1,
                'sensitive' => false,
            ],
            ['name' => 'isolation probe'],
        );
    }

    public function test_role_scope_bindings_are_isolated(): void
    {
        $this->assertTenantIsolationMatrix(
            RoleScopeBinding::class,
            fn (): array => [
                'role_assignment_id' => MembershipRoleAssignment::factory()->create()->getKey(),
                'scope_type' => 'organization',
                'scope_id' => (string) Str::ulid(),
                'scope_mode' => 'SUBTREE',
            ],
            ['scope_mode' => 'NODE'],
        );
    }

    public function test_roles_are_isolated(): void
    {
        $this->assertTenantIsolationMatrix(
            Role::class,
            fn (): array => [
                'code' => 'iso_'.Str::lower(Str::ulid()),
                'name' => 'Isolation role',
                'state' => Role::STATE_ACTIVE,
            ],
            ['name' => 'isolation probe'],
        );
    }

    public function test_role_permissions_are_isolated(): void
    {
        $this->assertTenantIsolationMatrix(
            RolePermission::class,
            fn (): array => [
                'role_id' => Role::factory()->create()->getKey(),
                'capability_code' => 'platform:policy:view',
            ],
            ['capability_code' => 'platform:policy:configure'],
        );
    }

    public function test_membership_role_assignments_are_isolated(): void
    {
        $this->assertTenantIsolationMatrix(
            MembershipRoleAssignment::class,
            function (): array {
                $tenantId = app(TenantContext::class)->tenantId();

                return [
                    'membership_id' => TenantMembership::factory()
                        ->create([
                            'tenant_id' => $tenantId,
                            'user_id' => User::factory()->create()->id,
                        ])->getKey(),
                    'role_id' => Role::factory()->create()->getKey(),
                ];
            },
            ['role_id' => (string) Str::ulid()],
        );
    }

    public function test_buyer_profiles_are_isolated(): void
    {
        $this->assertTenantIsolationMatrix(
            BuyerProfile::class,
            fn (): array => [
                'user_id' => User::factory()->create()->id,
                'display_name' => 'Isolation buyer',
            ],
            ['display_name' => 'isolation probe'],
        );
    }

    public function test_customer_addresses_are_isolated(): void
    {
        $this->assertTenantIsolationMatrix(
            CustomerAddress::class,
            fn (): array => [
                'user_id' => User::factory()->create()->id,
                'label' => 'home',
                'line1' => '1 Isolation Road',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'postal_code' => '400001',
            ],
            ['city' => 'isolation probe'],
        );
    }

    public function test_saved_items_are_isolated(): void
    {
        $this->assertTenantIsolationMatrix(
            SavedItem::class,
            fn (): array => [
                'user_id' => User::factory()->create()->id,
                'ref_type' => 'puja',
                'ref_id' => (string) Str::ulid(),
            ],
            ['ref_type' => 'content'],
        );
    }

    public function test_customer_notifications_are_isolated(): void
    {
        $this->assertTenantIsolationMatrix(
            CustomerNotification::class,
            fn (): array => [
                'user_id' => User::factory()->create()->id,
                'kind' => 'booking',
                'title' => 'Isolation notice',
            ],
            ['title' => 'isolation probe'],
        );
    }

    public function test_customer_settings_are_isolated(): void
    {
        $this->assertTenantIsolationMatrix(
            CustomerSetting::class,
            fn (): array => [
                'user_id' => User::factory()->create()->id,
                'key' => 'locale',
                'value' => 'hi',
            ],
            ['value' => 'en'],
        );
    }

    public function test_audit_logs_are_isolated(): void
    {
        $this->assertTenantIsolationMatrix(
            AuditLog::class,
            fn (): array => [
                'actor' => ['principal_id' => 'user:1', 'kind' => 'user', 'user_id' => '1'],
                'action' => 'iso.probe',
                'hash' => hash('sha256', (string) Str::ulid()),
                'occurred_at' => now(),
            ],
            ['action' => 'iso.probed'],
        );
    }
}
