<?php

namespace Tests\Platform\Identity;

use App\Platform\Identity\OAuth\OAuthClientProfile;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Tests\TenantIntegrationTestCase;

/**
 * Device-code ops console (SEC-002 §3.4): list with masked user codes
 * and state, revoke by user code (idempotent, typo-guarded), and
 * client-revoke cascading to outstanding pairings.
 */
final class DeviceConsoleTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: Client, 1: string} client + plaintext secret
     */
    private function posClient(string $tenantId): array
    {
        $client = new Client();
        $client->forceFill([
            'name' => 'POS terminal 02',
            'secret' => 'pos-console-secret',
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

        return [$client, 'pos-console-secret'];
    }

    private function issueCode(Client $client, string $secret): string
    {
        /** @var string $userCode */
        $userCode = $this->postJson('/oauth/device/code', [
            'client_id' => (string) $client->getKey(),
            'client_secret' => $secret,
        ])->assertOk()->json('user_code');

        return $userCode;
    }

    public function test_list_masks_codes_and_revoke_kills_pairing(): void
    {
        $tenant = $this->makeTenant();
        [$client, $secret] = $this->posClient((string) $tenant->getKey());
        $userCode = $this->issueCode($client, $secret);

        $masked = substr($userCode, 0, 2).'****'.substr($userCode, -2);

        Artisan::call('platform:device:list');
        $output = Artisan::output();
        $this->assertStringContainsString($masked, $output);
        $this->assertStringContainsString('pending', $output);

        // Full code never appears in operator output.
        Artisan::call('platform:device:list', ['--client' => (string) $client->getKey()]);
        $this->assertSame(0, Artisan::call('platform:device:revoke', ['user-code' => $userCode]));

        $model = Passport::deviceCodeModel();
        $this->assertTrue((bool) $model::query()->where('user_code', $userCode)->firstOrFail()->getAttribute('revoked'));

        // Idempotent second revoke; unknown codes fail (typo guard).
        $this->assertSame(0, Artisan::call('platform:device:revoke', ['user-code' => $userCode]));
        $this->assertSame(1, Artisan::call('platform:device:revoke', ['user-code' => 'NOPE-WXYZ']));
    }

    public function test_stale_filter_and_client_revoke_cascade(): void
    {
        $tenant = $this->makeTenant();
        [$client, $secret] = $this->posClient((string) $tenant->getKey());
        $userCode = $this->issueCode($client, $secret);

        $model = Passport::deviceCodeModel();
        $model::query()->where('user_code', $userCode)
            ->update(['expires_at' => gmdate('Y-m-d H:i:s', time() - 60)]);

        Artisan::call('platform:device:list', ['--stale' => true]);
        $output = Artisan::output();
        $this->assertStringContainsString('expired', $output);

        // Client revocation cascades to outstanding pairings.
        $this->assertSame(0, Artisan::call('platform:client:revoke', ['id' => (string) $client->getKey()]));
        $this->assertTrue((bool) $model::query()->where('user_code', $userCode)->firstOrFail()->getAttribute('revoked'));
    }
}
