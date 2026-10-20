<?php

namespace Database\Factories;

use App\Modules\Temple\Models\TemplePuja;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemplePuja>
 */
final class TemplePujaFactory extends Factory
{
    protected $model = TemplePuja::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => 'tenant_' . fake()->uuid(),
            'temple_id' => \App\Modules\Temple\Models\Temple::factory(),
            'puja_id' => fake()->uuid(),
            'price_override_minor' => fake()->optional()->numberBetween(1000, 50000),
            'capacity' => fake()->optional()->numberBetween(1, 50),
            'schedule_json' => json_encode([
                '1' => [['start' => '06:00', 'end' => '07:00', 'capacity' => 10], ['start' => '08:00', 'end' => '09:00', 'capacity' => 10]],
                '5' => [['start' => '18:00', 'end' => '19:30', 'capacity' => 20]],
            ]),
            'status' => fake()->randomElement([TemplePuja::STATUS_ACTIVE, TemplePuja::STATUS_INACTIVE]),
        ];
    }
}
