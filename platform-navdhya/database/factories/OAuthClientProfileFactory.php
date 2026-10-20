<?php

namespace Database\Factories;

use App\Platform\Identity\OAuth\OAuthClientProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OAuthClientProfile>
 */
final class OAuthClientProfileFactory extends Factory
{
    protected $model = OAuthClientProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => (string) \Illuminate\Support\Str::uuid(),
            'client_type' => OAuthClientProfile::TYPE_PARTNER,
            'tenant_id' => null,
            'allowed_grants' => ['client_credentials'],
            'allowed_scopes' => [],
            'require_pkce' => true,
            'require_par' => false,
            'rate_tier' => 'standard',
        ];
    }
}
