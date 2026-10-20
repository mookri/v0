<?php

namespace Database\Factories;

use App\Modules\Vendor\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vendor>
 */
final class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => '01J0000000000000000000000',
            'application_id' => (string) Str::ulid(),
            'owner_user_id' => fake()->numberBetween(1, 1000000),
            'name' => fake()->company().' Store',
            'description' => fake()->sentence(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'status' => Vendor::STATUS_ACTIVE,
            'verification_status' => Vendor::VERIFICATION_PENDING,
        ];
    }
}
