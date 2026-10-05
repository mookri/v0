<?php

namespace Database\Factories;

use App\Modules\Vendor\Models\Vendor;
use App\Modules\Vendor\Models\VendorOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorOrder>
 */
final class VendorOrderFactory extends Factory
{
    protected $model = VendorOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => '01J0000000000000000000000',
            'vendor_id' => Vendor::factory(),
            'reference' => 'REF-'.fake()->unique()->numberBetween(100000, 999999),
            'customer_name' => fake()->name(),
            'status' => VendorOrder::STATUS_NEW,
            'currency' => 'INR',
            'total_minor' => fake()->numberBetween(100, 50000),
        ];
    }
}
