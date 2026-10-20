<?php

namespace Database\Factories;

use App\Modules\Customers\Models\ReferralAttribution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferralAttribution>
 */
final class ReferralAttributionFactory extends Factory
{
    protected $model = ReferralAttribution::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'referrer_user_id' => \App\Models\User::factory(),
            'referred_user_id' => \App\Models\User::factory(),
            'code' => 'REF-TESTCODE',
        ];
    }
}
