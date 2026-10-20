<?php

namespace Database\Factories;

use App\Modules\Vendor\Models\Vendor;
use App\Modules\Vendor\Models\VendorInventory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorInventory>
 */
final class VendorInventoryFactory extends Factory
{
    protected $model = VendorInventory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => '01J0000000000000000000000',
            'vendor_id' => Vendor::factory(),
            'sku' => 'SKU-'.fake()->unique()->numberBetween(1, 999999),
            'on_hand' => fake()->numberBetween(0, 50),
        ];
    }
}
