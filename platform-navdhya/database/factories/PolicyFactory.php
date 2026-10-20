<?php

namespace Database\Factories;

use App\Platform\Authz\Models\Policy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Policy>
 */
final class PolicyFactory extends Factory
{
    protected $model = Policy::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'policy-'.$this->faker->unique()->regexify('[a-z0-9]{8}'),
            'description' => $this->faker->sentence(),
            'effect' => 'allow',
            'capability_selector' => ['module' => 'demo', 'resource' => 'widget', 'action' => 'view'],
            'scope_bindings' => [],
            'conditions' => [],
            'priority' => 0,
            'status' => 'draft',
            'version' => 1,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function deny(): static
    {
        return $this->state(['effect' => 'deny']);
    }

    /**
     * Platform-default policy (tenant_id NULL, LD-15).
     */
    public function platformDefault(): static
    {
        return $this->state(['tenant_id' => null]);
    }
}
