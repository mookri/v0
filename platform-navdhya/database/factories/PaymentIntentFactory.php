<?php

namespace Database\Factories;

use App\Modules\Payment\Models\PaymentIntent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentIntent>
 */
final class PaymentIntentFactory extends Factory
{
    protected $model = PaymentIntent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // ULIDs, never UUIDs (F-DB3c: MySQL string ordering).
            'tenant_id' => (string) \Illuminate\Support\Str::ulid(),
            'user_id' => 1,
            'booking_id' => (string) \Illuminate\Support\Str::ulid(),
            'amount_minor' => $this->faker->numberBetween(10000, 1000000),
            'currency' => 'INR',
            'status' => PaymentIntent::STATUS_PENDING,
            'provider' => 'razorpay',
            'test_mode' => true,
            'provider_order_id' => 'order_test_'.$this->faker->unique()->regexify('[A-Z0-9]{10}'),
            'provider_payment_id' => null,
            'quote_snapshot' => null,
        ];
    }
}
