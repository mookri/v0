<?php

namespace Database\Factories;

use App\Platform\Identity\OAuth\PushedAuthorizationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PushedAuthorizationRequest>
 */
final class PushedAuthorizationRequestFactory extends Factory
{
    protected $model = PushedAuthorizationRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'request_uri_hash' => bin2hex(random_bytes(32)),
            'client_id' => (string) \Illuminate\Support\Str::uuid(),
            'params' => ['response_type' => 'code', 'client_id' => (string) \Illuminate\Support\Str::uuid()],
            'expires_at' => now()->addSeconds(PushedAuthorizationRequest::TTL_SECONDS),
        ];
    }
}
