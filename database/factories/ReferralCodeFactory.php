<?php

namespace Database\Factories;

use App\Modules\Customers\Models\ReferralCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferralCode>
 */
final class ReferralCodeFactory extends Factory
{
    protected $model = ReferralCode::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'code' => ReferralCode::generate(),
        ];
    }
}
