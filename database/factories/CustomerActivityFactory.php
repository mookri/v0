<?php

namespace Database\Factories;

use App\Modules\Customers\Models\CustomerActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerActivity>
 */
final class CustomerActivityFactory extends Factory
{
    protected $model = CustomerActivity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'kind' => 'login',
            'label' => 'Signed in',
        ];
    }
}
