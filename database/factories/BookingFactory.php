<?php

namespace Database\Factories;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
final class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'service_sku' => 'PUJA-LAKSHMI',
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => now()->addDays(3),
            'duration_minutes' => 60,
            'version' => 1,
        ];
    }
}
