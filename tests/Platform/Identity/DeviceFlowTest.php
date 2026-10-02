<?php

namespace Tests\Platform\Identity;

use App\Platform\Identity\OAuth\OAuthClientProfile;
use Laravel\Passport\Client;
use Tests\TenantIntegrationTestCase;

/**
 * Device flow (SEC-002 §3.4, RFC 8628): code issuance, pending polls,
 * slow-down on fast polling, web approval, token issuance on poll,
 * denial, and expiry (expired_token). POS clients are per-tenant,
 * per-terminal records.
 */
final class DeviceFlowTest extends TenantIntegrationTestCase
{
    private const DEVICE_GRANT = 'urn:ietf:params:oauth:grant-type:device_code';

    /**
     * @return array{0: Client, 1: string} client + plaintext secret
     */
    private function posClient(string $tenantId): array
    {
        $client = new Client();
        $client->forceFill([
            'name' => 'POS terminal 01',
            'secret' => 'pos-secret-12345',
            'redirect_uris' => [],
            'grant_types' => ['urn:ietf:params:oauth:grant-type:device_code', 'refresh_token'],
            'revoked' => false,
        ]);
        $client->save();

        OAuthClientProfile::factory()->create([
            'client_id' => (string) $client->getKey(),
            'client_type' => OAuthClientProfile::TYPE_POS_DEVICE,
            'tenant_id' => $tenantId,
            'allowed_grants' => ['urn:ietf:params:oauth:grant-type:device_code', 'refresh_token'],
        ]);

        return [$client, 'pos-secret-12345'];
    }

    /**
     * @return array<string, mixed>
     */
    private function requestCode(Client $client, string $secret): array
    {
        return $this->postJson('/oauth/device/code', [
            'client_id' => (string) $client->getKey(),
            'client_secret' => $secret,
        ])->assertOk()->json();
    }

    /**
     * @return array<string, mixed>
     */
    private function poll(Client $client, string $secret, string $deviceCode): array
    {
        $response = $this->postJson('/oauth/token', [
            'grant_type' => self::DEVICE_GRANT,
            'device_code' => $deviceCode,
            'client_id' => (string) $client->getKey(),
            'client_secret' => $secret,
        ]);

        return ['status' => $response->getStatusCode(), 'body' => $response->json()];
    }

    public function test_pending_then_approved_then_tokens(): void
    {
        $tenant = $this->makeTenant();
        [$operator] = $this->actingAsMembership($tenant);
        [$client, $secret] = $this->posClient((string) $tenant->getKey());

        $code = $this->requestCode($client, $secret);
        $this->assertArrayHasKey('device_code', $code);
        $this->assertArrayHasKey('user_code', $code);
        $this->assertArrayHasKey('verification_uri', $code);

        // Pending before approval; fast second poll slows down.
        $this->assertSame('authorization_pending', $this->poll($client, $secret, $code['device_code'])['body']['error']);
        $this->assertSame('slow_down', $this->poll($client, $secret, $code['device_code'])['body']['error']);

        // Operator approves on the web verification UI (session auth).
        $this->actingAs($operator)->get('/oauth/device/authorize?user_code='.urlencode($code['user_code']))->assertOk();
        $approval = $this->actingAs($operator)->post('/oauth/device/authorize', [
            '_token' => csrf_token(),
            'auth_token' => session('authToken'),
        ]);
        // Approval redirects back to the device entry (standard flow).
        $approval->assertRedirect('/oauth/device');

        // wait for the interval so the poll is not throttled to slow_down
        sleep(6);

        $approved = $this->poll($client, $secret, $code['device_code']);
        $this->assertSame(200, $approved['status']);
        $this->assertArrayHasKey('access_token', $approved['body']);
    }

    public function test_denied_device_fails_poll(): void
    {
        $tenant = $this->makeTenant();
        [$operator] = $this->actingAsMembership($tenant);
        [$client, $secret] = $this->posClient((string) $tenant->getKey());

        $code = $this->requestCode($client, $secret);

        $this->actingAs($operator)->get('/oauth/device/authorize?user_code='.urlencode($code['user_code']))->assertOk();
        $this->actingAs($operator)->delete('/oauth/device/authorize', [
            '_token' => csrf_token(),
            'auth_token' => session('authToken'),
        ])->assertRedirect('/oauth/device');

        sleep(6);

        $denied = $this->poll($client, $secret, $code['device_code']);
        $this->assertSame(401, $denied['status']);
        $this->assertSame('access_denied', $denied['body']['error']);
    }

    public function test_expired_device_code_fails_poll(): void
    {
        $tenant = $this->makeTenant();
        [$client, $secret] = $this->posClient((string) $tenant->getKey());

        $code = $this->requestCode($client, $secret);

        // Age the code past its lifetime (RFC 8628 §3.5).
        $model = \Laravel\Passport\Passport::deviceCodeModel();
        $model::query()->where('user_code', $code['user_code'])
            ->update(['expires_at' => gmdate('Y-m-d H:i:s', time() - 60)]);

        sleep(6);

        $expired = $this->poll($client, $secret, $code['device_code']);
        $this->assertSame(400, $expired['status']);
        $this->assertSame('expired_token', $expired['body']['error']);
    }
}
