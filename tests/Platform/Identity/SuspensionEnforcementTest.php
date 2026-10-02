<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\ActorTokenIssuer;
use Tests\Platform\Identity\Concerns\IssuesUserTokens;
use Tests\TenantIntegrationTestCase;

/**
 * User lifecycle enforcement (user_lifecycle_status migration): only
 * `active` users authenticate — HMAC, dual-accept JWT, and refresh all
 * fail closed for suspended/deactivated users, and suspending cascades
 * to OAuth tokens plus web sessions (bulk trigger, SEC-015 §8.1).
 */
final class SuspensionEnforcementTest extends TenantIntegrationTestCase
{
    use IssuesUserTokens;

    private function hmacFor(User $user, string $secret = 'suspension-secret'): string
    {
        config()->set('identity.hmac_secret', $secret);
        $user->forceFill(['external_id' => 'susp_'.strtolower((string) \Illuminate\Support\Str::ulid())])->save();

        return ActorTokenIssuer::mint(
            $secret,
            (string) $user->external_id,
            86400,
            gmdate('Y-m-d\TH:i:s.v\Z')
        )['token'];
    }

    public function test_suspended_user_fails_every_credential(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $tid = (string) $tenant->getKey();
        $hmac = $this->hmacFor($user);
        [$client, $tokens] = $this->userTokensFor($user);
        auth()->logout();

        $hmacHeaders = ['Authorization' => "Bearer {$hmac}", 'X-Tenant-Id' => $tid];
        $jwtHeaders = ['Authorization' => 'Bearer '.$tokens['access_token'], 'X-Tenant-Id' => $tid];
        $this->getJson('/api/v1/catalog/items', $hmacHeaders)->assertOk();
        auth()->logout();
        $this->getJson('/api/v1/catalog/items', $jwtHeaders)->assertOk();
        auth()->logout();

        $user->forceFill(['status' => 'suspended'])->save();

        // HMAC denied...
        $this->getJson('/api/v1/catalog/items', $hmacHeaders)->assertUnauthorized();
        auth()->logout();

        // ...dual-accept JWT denied...
        $this->getJson('/api/v1/catalog/items', $jwtHeaders)->assertUnauthorized();
        auth()->logout();

        // ...and refresh reads as revoked.
        $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $tokens['refresh_token'],
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
        ])->assertStatus(400);
    }

    public function test_suspend_endpoint_cascades_to_tokens_and_sessions(): void
    {
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        [$issuer] = $this->actingAsMembership($tenant, ['platform:user:manage']);
        [$user] = $this->actingAsMembership($tenant);
        [, $tokens] = $this->userTokensFor($user);

        // A live web session row for the target user.
        \Illuminate\Support\Facades\DB::connection(config('database.default'))->table('sessions')->insert([
            'id' => 'suspend-probe-session',
            'user_id' => $user->getAuthIdentifier(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'probe',
            'payload' => 'x',
            'last_activity' => time(),
        ]);

        $suspend = fn () => $this->actingAs($issuer)->postJson(
            "/api/v1/platform/users/{$user->getAuthIdentifier()}:suspend",
            ['tenant_id' => $tid],
            $this->tenantHeaders($tenant)
        );

        // Step-up gated: no fresh proof, no credential kill.
        $suspend()->assertForbidden()->assertJsonPath('error.code', 'step_up_required');

        $this->stepUp($issuer);
        $suspend()->assertOk()->assertJsonPath('data.status', 'suspended');

        // Tokens dead (no live rows left for the user)...
        $this->assertSame(0, \Laravel\Passport\Token::query()
            ->where('user_id', $user->getAuthIdentifier())->where('revoked', false)->count());

        // ...sessions gone...
        $this->assertSame(0, \Illuminate\Support\Facades\DB::connection(config('database.default'))
            ->table('sessions')->where('user_id', $user->getAuthIdentifier())->count());

        // ...and the JWT fails dual-accept.
        auth()->logout();
        $this->getJson('/api/v1/catalog/items', [
            'Authorization' => 'Bearer '.$tokens['access_token'],
            'X-Tenant-Id' => $tid,
        ])->assertUnauthorized();
    }
}
