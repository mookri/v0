<?php

namespace Database\Factories;

use App\Modules\Pandit\Models\PanditAvailability;
use App\Modules\Pandit\Models\PanditProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PanditAvailability>
 */
final class PanditAvailabilityFactory extends Factory
{
    protected $model = PanditAvailability::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pandit_id' => PanditProfile::factory(),
            'day_of_week' => $this->faker->numberBetween(0, 6),
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'slot_duration_minutes' => 60,
            'is_recurring' => true,
            'specific_date' => null,
        ];
    }
}
