<?php

namespace Database\Factories;

use App\Modules\Temple\Models\Temple;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Temple>
 */
final class TempleFactory extends Factory
{
    protected $model = Temple::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cities = ['Chennai', 'Bangalore', 'Hyderabad', 'Mumbai', 'Delhi', 'Kolkata', 'Pune', 'Ahmedabad'];
        $states = ['Tamil Nadu', 'Karnataka', 'Telangana', 'Maharashtra', 'Delhi', 'West Bengal', 'Gujarat'];
        $types = [Temple::TYPE_INDIVIDUAL, Temple::TYPE_TRUST];
        $statuses = [Temple::STATUS_ACTIVE, Temple::STATUS_INACTIVE];
        $verificationStatuses = [Temple::VERIFICATION_VERIFIED, Temple::VERIFICATION_PENDING, Temple::VERIFICATION_REJECTED];

        return [
            'tenant_id' => 'tenant_' . fake()->uuid(),
            'slug' => fake()->unique()->slug(3) . '_' . fake()->uuid(),
            'name' => fake()->company() . ' Temple',
            'trust_name' => fake()->company() . ' Trust',
            'type' => fake()->randomElement($types),
            'address' => fake()->streetAddress(),
            'city' => fake()->randomElement($cities),
            'state' => fake()->randomElement($states),
            'lat' => fake()->latitude(-90, 90),
            'lng' => fake()->longitude(-180, 180),
            'images' => [fake()->imageUrl(), fake()->imageUrl()],
            'amenities' => fake()->randomElements(['parking', 'wheelchair_access', 'prasad_counter', 'shoe_rack', 'drinking_water', 'restroom', 'cloakroom', 'elevator'], fake()->numberBetween(1, 5)),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement($statuses),
            'verification_status' => fake()->randomElement($verificationStatuses),
            'owner_id' => \App\Models\User::factory(),
        ];
    }
}
