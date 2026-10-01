<?php

namespace Database\Factories;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\BookingTransition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingTransition>
 */
final class BookingTransitionFactory extends Factory
{
    protected $model = BookingTransition::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => (string) \Illuminate\Support\Str::ulid(),
            'from_status' => BookingLifecycle::STATUS_CONFIRMED,
            'to_status' => BookingLifecycle::STATUS_ASSIGNED,
            'actor_id' => 'system',
        ];
    }
}
