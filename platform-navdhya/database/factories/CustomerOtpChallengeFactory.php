<?php

namespace Database\Factories;

use App\Modules\Customers\Models\CustomerOtpChallenge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerOtpChallenge>
 */
final class CustomerOtpChallengeFactory extends Factory
{
    protected $model = CustomerOtpChallenge::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => '01J0000000000000000000000',
            'purpose' => CustomerOtpChallenge::PURPOSE_LOGIN,
            'identifier' => '+91'.$this->faker->unique()->numerify('##########'),
            'code_hash' => hash('sha256', '000000'),
            'payload' => null,
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'max_attempts' => 3,
        ];
    }
}
