<?php

namespace Database\Factories;

use App\Modules\Vendor\Models\Vendor;
use App\Modules\Vendor\Models\VendorPayout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorPayout>
 */
final class VendorPayoutFactory extends Factory
{
    protected $model = VendorPayout::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => '01J0000000000000000000000',
            'vendor_id' => Vendor::factory(),
            'period' => now()->format('Y-m'),
            'status' => VendorPayout::STATUS_PENDING,
            'amount_minor' => fake()->numberBetween(100, 100000),
            'currency' => 'INR',
            'reference' => null,
        ];
    }
}
