<?php

namespace Database\Factories;

use App\Modules\Customers\Models\CustomerNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerNotification>
 */
final class CustomerNotificationFactory extends Factory
{
    protected $model = CustomerNotification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'kind' => 'booking',
            'category' => 'transactional',
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->sentence(10),
            'dedup_key' => null,
            'read_at' => null,
        ];
    }
}
