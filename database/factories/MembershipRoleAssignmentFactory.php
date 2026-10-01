<?php

namespace Database\Factories;

use App\Platform\Authz\Models\MembershipRoleAssignment;
use App\Platform\Authz\Models\Role;
use App\Platform\Tenancy\Exceptions\TenantContextMissingException;
use App\Platform\Tenancy\Models\TenantMembership;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * The generated membership belongs to the ambient TenantContext's tenant
 * so assignment, role and membership stay in one tenant scope.
 *
 * @extends Factory<MembershipRoleAssignment>
 */
final class MembershipRoleAssignmentFactory extends Factory
{
    protected $model = MembershipRoleAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'membership_id' => fn (): TenantMembership => TenantMembership::factory()->create([
                'tenant_id' => $this->contextTenantId(),
            ]),
            'role_id' => Role::factory(),
        ];
    }

    private function contextTenantId(): string
    {
        $tenantId = app(TenantContext::class)->tenantId();

        if ($tenantId === null) {
            throw new TenantContextMissingException('resolving membership factory tenant');
        }

        return $tenantId;
    }
}
