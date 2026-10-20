<?php

namespace Database\Factories;

use App\Modules\Onboarding\Models\PartnerApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartnerApplication>
 */
final class PartnerApplicationFactory extends Factory
{
    protected $model = PartnerApplication::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => '01J0000000000000000000000',
            'identity_id' => 'user-'.$this->faker->unique()->regexify('[a-z0-9]{8}'),
            'track' => 'pandit',
            'track_version' => 'pandit-v1',
            'status' => PartnerApplication::STATUS_DRAFT,
            'market' => 'in',
        ];
    }
}
