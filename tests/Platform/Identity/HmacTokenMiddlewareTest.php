<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use Tests\TenantIntegrationTestCase;

/**
 * HMAC dual-accept middleware: frontend tokens authenticate where the
 * secret matches and the external id links to a user; everything else
 * fails closed with the stable code. No auto-provisioning.
 */
final class HmacTokenMiddlewareTest extends TenantIntegrationTestCase
{
    private function withSecret(string $secret, callable $fn): void
    {
        config()->set('identity.hmac_secret', $secret);
        try {
            $fn();
        } finally {
            config()->set('identity.hmac_secret', '');
        }
    }

    private function probe(string $token): \Illuminate\Testing\TestResponse
    {
        return $this->getJson('/api/v1/identity/profile', ['Authorization' => "Bearer {$token}"]);
    }

    public function test_middleware_unit_links_user_directly(): void
    {
        config()->set('identity.hmac_secret', 'bridge-secret');
        $user = User::factory()->create();
        $user->forceFill(['external_id' => 'u9'])->save();

        $payload = $this->b64url(json_encode([
            'v' => 1, 'u' => 'u9', 's' => 'sess_9', 'r' => 'partner',
            'm' => null, 'o' => null, 'e' => '2099-01-01T00:00:00.000Z',
        ]));
        $sig = $this->b64url(hash_hmac('sha256', $payload, 'bridge-secret', true));

        $middleware = $this->app->make(\App\Platform\Identity\Http\Middleware\AuthenticateHmacToken::class);
        $request = \Illuminate\Http\Request::create('/api/v1/identity/profile', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$payload}.{$sig}",
        ]);
        $ran = false;
        $response = $middleware->handle($request, static function () use (&$ran) {
            $ran = true;

            return new \Symfony\Component\HttpFoundation\Response('next', 200);
        });

        $this->assertTrue($ran);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($user->id, auth()->user()->id);
        $this->assertSame('u9', $request->attributes->get('actor')['user_id']);
    }

    public function test_valid_token_links_and_authenticates(): void
    {
        $secret = 'bridge-secret';
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $user->forceFill(['external_id' => 'u9'])->save();
        $this->makeMembership($tenant, $user);

        // Mint with the frontend algorithm (HMAC-SHA256, base64url segments).
        $payload = $this->b64url(json_encode([
            'v' => 1, 'u' => 'u9', 's' => 'sess_9', 'r' => 'partner',
            'm' => null, 'o' => null, 'e' => '2099-01-01T00:00:00.000Z',
        ]));
        $sig = $this->b64url(hash_hmac('sha256', $payload, $secret, true));
        $token = "{$payload}.{$sig}";

        $response = null;
        $this->withSecret($secret, function () use (&$response, $token): void {
            $response = $this->probe($token);
        });
        $response->assertOk()->assertJsonPath('data.email', $user->email);
    }

    public function test_wrong_secret_unknown_link_and_malformed_deny(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        $payload = $this->b64url(json_encode([
            'v' => 1, 'u' => 'ghost-external', 's' => 's1', 'r' => '',
            'm' => null, 'o' => null, 'e' => '2099-01-01T00:00:00.000Z',
        ]));
        $goodSig = $this->b64url(hash_hmac('sha256', $payload, 'bridge-secret', true));

        $this->withSecret('bridge-secret', function () use ($payload, $goodSig): void {
            $anon = function (string $token) {
                auth()->logout();
                return $this->getJson('/api/v1/identity/profile', ['Authorization' => "Bearer {$token}"]);
            };
            // Unknown external id: fail closed, no provisioning.
            $anon("{$payload}.{$goodSig}")->assertUnauthorized();
            // Tampered payload.
            $anon($payload.'x.'.$goodSig)->assertUnauthorized();
            // Missing header.
            auth()->logout();
            $this->getJson('/api/v1/identity/profile')->assertUnauthorized();
        });

        // Wrong secret.
        $this->withSecret('other-secret', function () use ($payload, $goodSig): void {
            auth()->logout();
            $this->getJson('/api/v1/identity/profile', ['Authorization' => "Bearer {$payload}.{$goodSig}"])
                ->assertUnauthorized();
        });
    }

    private function b64url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
