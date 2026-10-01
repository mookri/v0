<?php

namespace Database\Factories;

use App\Platform\Authz\Models\Role;
use App\Platform\Authz\Models\RolePermission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RolePermission>
 */
final class RolePermissionFactory extends Factory
{
    protected $model = RolePermission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_id' => Role::factory(),
            'capability_code' => 'platform:policy:view',
        ];
    }
}
