<?php

namespace Database\Factories;

use App\Platform\Authz\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * tenant_id is filled from the ambient TenantContext by BelongsToTenant
 * at create time (fail-closed outside a resolved context).
 *
 * @extends Factory<Role>
 */
final class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'role_'.$this->faker->unique()->regexify('[a-z0-9]{8}'),
            'name' => ucfirst($this->faker->unique()->words(2, true)),
            'state' => Role::STATE_ACTIVE,
        ];
    }
}
