<?php

namespace Database\Factories;

use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
final class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 't'.$this->faker->unique()->regexify('[a-z0-9]{10}'),
            'slug' => 't-'.$this->faker->unique()->regexify('[a-z0-9]{10}'),
            'name' => $this->faker->company(),
            'db_mode' => 'shared',
            'state' => Tenant::STATE_ACTIVE,
            'status' => Tenant::STATE_ACTIVE,
            'locale' => 'en',
        ];
    }

    public function suspended(): static
    {
        return $this->state(['state' => Tenant::STATE_SUSPENDED]);
    }
}
