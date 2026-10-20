<?php

namespace Database\Factories;

use App\Modules\Customers\Models\SupportTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
final class SupportTicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'subject' => $this->faker->sentence(6),
            'category' => 'booking',
            'status' => SupportTicket::STATUS_OPEN,
        ];
    }
}
