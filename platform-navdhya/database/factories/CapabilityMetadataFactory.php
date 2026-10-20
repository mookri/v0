<?php

namespace Database\Factories;

use App\Platform\Authz\Models\CapabilityMetadata;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CapabilityMetadata>
 */
final class CapabilityMetadataFactory extends Factory
{
    protected $model = CapabilityMetadata::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $resource = $this->faker->unique()->regexify('[a-z]{6}');

        return [
            'code' => 'demo:'.$resource.':view',
            'name' => 'View '.$resource,
            'module' => 'demo',
            'resource' => $resource,
            'action' => 'view',
            'version' => 1,
            'sensitive' => false,
        ];
    }

    public function sensitive(): static
    {
        return $this->state(['sensitive' => true]);
    }
}
