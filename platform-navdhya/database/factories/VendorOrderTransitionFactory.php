<?php

namespace Database\Factories;

use App\Modules\Vendor\Models\VendorOrder;
use App\Modules\Vendor\Models\VendorOrderTransition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorOrderTransition>
 */
final class VendorOrderTransitionFactory extends Factory
{
    protected $model = VendorOrderTransition::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => '01J0000000000000000000000',
            'order_id' => VendorOrder::factory(),
            'from_status' => null,
            'to_status' => VendorOrder::STATUS_NEW,
            'actor_user_id' => null,
            'note' => null,
        ];
    }
}
