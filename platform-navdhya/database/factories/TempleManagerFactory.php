<?php

namespace Database\Factories;

use App\Modules\Temple\Models\TempleManager;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TempleManager>
 */
final class TempleManagerFactory extends Factory
{
    protected $model = TempleManager::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => 'tenant_' . fake()->uuid(),
            'temple_id' => \App\Modules\Temple\Models\Temple::factory(),
            'user_id' => \App\Models\User::factory(),
            'role' => fake()->randomElement([
                TempleManager::ROLE_OWNER,
                TempleManager::ROLE_ADMIN,
                TempleManager::ROLE_MANAGER,
                TempleManager::ROLE_VIEWER,
            ]),
            'invited_at' => now()->subDays(fake()->numberBetween(1, 30))->toISOString(),
            'accepted_at' => fake()->optional(0.7)->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
