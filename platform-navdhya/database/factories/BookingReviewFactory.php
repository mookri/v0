<?php

namespace Database\Factories;

use App\Modules\Booking\Models\BookingReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingReview>
 */
final class BookingReviewFactory extends Factory
{
    protected $model = BookingReview::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => (string) \Illuminate\Support\Str::ulid(),
            'user_id' => \App\Models\User::factory(),
            'rating' => 5,
            'comment' => $this->faker->sentence(12),
        ];
    }
}
