<?php

namespace Database\Factories;

use App\Platform\Identity\Models\ApiKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiKey>
 */
final class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => '01J0000000000000000000000',
            'name' => $this->faker->words(2, true).' integration',
            'prefix' => 'sk_test_'.$this->faker->unique()->regexify('[A-Za-z0-9]{12}'),
            'secret_hash' => hash('sha256', $this->faker->unique()->uuid()),
            'scopes' => ['read'],
            'rate_tier' => 'standard',
        ];
    }
}
