<?php

namespace Database\Factories;

use App\Platform\Authz\Models\MembershipRoleAssignment;
use App\Platform\Authz\Models\RoleScopeBinding;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RoleScopeBinding>
 */
final class RoleScopeBindingFactory extends Factory
{
    protected $model = RoleScopeBinding::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_assignment_id' => MembershipRoleAssignment::factory(),
            'scope_type' => 'organization',
            'scope_id' => fn (): string => (string) Str::ulid(),
            'scope_mode' => 'SUBTREE',
        ];
    }
}
