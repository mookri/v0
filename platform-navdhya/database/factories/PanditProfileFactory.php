<?php

namespace Database\Factories;

use App\Modules\Pandit\Models\PanditProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PanditProfile>
 */
final class PanditProfileFactory extends Factory
{
    protected $model = PanditProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'display_name' => $this->faker->name(),
            'bio' => $this->faker->sentence(),
            'languages' => ['hi', 'en'],
            'specializations' => ['griha-pravesh'],
            'experience_years' => $this->faker->numberBetween(1, 30),
            'certification_refs' => [],
            'verification_status' => PanditProfile::VERIFICATION_VERIFIED,
            'verification_documents' => [],
            'base_rate_minor' => $this->faker->numberBetween(50000, 500000),
            'currency' => 'INR',
            'market' => 'in',
            'rating_avg' => '4.5',
            'rating_count' => $this->faker->numberBetween(0, 100),
            'total_bookings' => $this->faker->numberBetween(0, 100),
            'status' => PanditProfile::STATUS_ACTIVE,
        ];
    }
}
