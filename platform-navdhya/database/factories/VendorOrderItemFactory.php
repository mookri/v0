<?php

namespace Database\Factories;

use App\Modules\Vendor\Models\VendorOrder;
use App\Modules\Vendor\Models\VendorOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorOrderItem>
 */
final class VendorOrderItemFactory extends Factory
{
    protected $model = VendorOrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => '01J0000000000000000000000',
            'order_id' => VendorOrder::factory(),
            'sku' => 'SKU-'.fake()->unique()->numberBetween(1, 999999),
            'name' => fake()->words(2, true),
            'quantity' => fake()->numberBetween(1, 5),
            'unit_minor' => fake()->numberBetween(100, 5000),
            'line_minor' => fake()->numberBetween(100, 5000),
        ];
    }
}
