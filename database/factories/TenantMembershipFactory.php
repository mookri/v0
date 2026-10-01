<?php

namespace Database\Factories;

use App\Models\User;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantMembership>
 */
final class TenantMembershipFactory extends Factory
{
    protected $model = TenantMembership::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'org_node_id' => null,
            'state' => TenantMembership::STATE_ACTIVE,
        ];
    }

    public function suspended(): static
    {
        return $this->state(['state' => TenantMembership::STATE_SUSPENDED]);
    }
}
