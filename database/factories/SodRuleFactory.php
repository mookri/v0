<?php

namespace Database\Factories;

use App\Platform\Authz\Models\SodRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SodRule>
 */
final class SodRuleFactory extends Factory
{
    protected $model = SodRule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'code' => 'sod.test_'.$this->faker->unique()->slug(2),
            'name' => 'Test conflict rule',
            'kind' => SodRule::KIND_CONFLICT,
            'role_set' => null,
            'permission_set' => [$this->faker->unique()->word().':approve'],
            'severity' => SodRule::SEVERITY_BLOCK,
            'status' => SodRule::STATUS_ACTIVE,
        ];
    }

    public function warn(): static
    {
        return $this->state(['severity' => SodRule::SEVERITY_WARN]);
    }

    public function forTenant(string $tenantId): static
    {
        return $this->state(['tenant_id' => $tenantId]);
    }
}
