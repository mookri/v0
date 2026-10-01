<?php

namespace Database\Factories;

use App\Platform\Identity\Models\Invite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invite>
 */
final class InviteFactory extends Factory
{
    protected $model = Invite::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => '01J0000000000000000000000',
            'track' => 'pandit',
            'roles' => [],
            'identifier' => $this->faker->unique()->safeEmail(),
            'token_hash' => hash('sha256', $this->faker->unique()->uuid()),
            'state' => Invite::STATE_ISSUED,
            'expires_at' => now()->addDay(),
        ];
    }
}
