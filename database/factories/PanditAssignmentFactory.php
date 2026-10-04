<?php

namespace Database\Factories;

use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PanditAssignment>
 */
final class PanditAssignmentFactory extends Factory
{
    protected $model = PanditAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pandit_id' => PanditProfile::factory(),
            'booking_id' => \App\Modules\Booking\Models\Booking::factory(),
            'status' => PanditAssignment::STATUS_PENDING,
            'assigned_at' => now(),
            'confirmed_at' => null,
            'declined_at' => null,
            'decline_reason' => null,
            'compensation_minor' => $this->faker->numberBetween(50000, 500000),
        ];
    }
}
