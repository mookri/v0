<?php

namespace Database\Factories;

use App\Modules\Customers\Models\SupportMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportMessage>
 */
final class SupportMessageFactory extends Factory
{
    protected $model = SupportMessage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => (string) \Illuminate\Support\Str::ulid(),
            'user_id' => \App\Models\User::factory(),
            'body' => $this->faker->paragraph(2),
        ];
    }
}
