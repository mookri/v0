<?php

namespace Database\Factories;

use App\Modules\Customers\Models\BuyerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BuyerProfile>
 */
final class BuyerProfileFactory extends Factory
{
    protected $model = BuyerProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'display_name' => $this->faker->name(),
            'locale' => 'en',
            'status' => BuyerProfile::STATUS_ACTIVE,
        ];
    }
}
