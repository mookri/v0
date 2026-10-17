<?php

namespace Tests\Platform\Identity;

use App\Platform\Identity\Federation\RevocationStatusCache;
use Tests\Platform\Identity\Concerns\IssuesUserTokens;
use Tests\TenantIntegrationTestCase;

/**
 * ADR-0020 D2: the 30-second revocation contract. Ordinary API calls
 * accept status no staler than federation.revocation_status_ttl_seconds;
 * local revocations invalidate immediately; the critical-op gate
 * re-reads authoritative state and ignores the cache entirely.
 */
final class RevocationStatusCacheTest extends TenantIntegrationTestCase
{
    use IssuesUserTokens;

    protected function setUp(): void
    {
        parent::setUp();

        // 9c: the RFC 7009 endpoint now chains a token.revoked row,
        // whose pseudonymization requires the identity secret.
        config(['identity.hmac_secret' => 'revocation-status-cache-test-32!!']);
    }

    public function test_valid_token_cached_under_the_bound_then_denies_after_ttl(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [, $tokens] = $this->userTokensFor($user);
        auth()->logout();

        $headers = [
            'Authorization' => 'Bearer '.$tokens['access_token'],
            'X-Tenant-Id' => (string) $tenant->getKey(),
        ];

        // Warm the status cache through a real request.
        $this->getJson('/api/v1/catalog/items', $headers)->assertOk();
        auth()->logout();

        // Cross-process revocation simulation: direct DB flip, no
        // in-process invalidation hook fired (another node revoked it).
        $jti = $this->jtiOf($tokens['access_token']);
        \Laravel\Passport\Passport::token()->newQuery()->whereKey($jti)->update(['revoked' => true]);

        // Within the bound the cached verdict still holds (documented,
        // bounded staleness — this IS the D2 contract for ordinary APIs).
        $this->getJson('/api/v1/catalog/items', $headers)->assertOk();
        auth()->logout();

        // Past the bound the cache expired: authoritative read denies.
        \Illuminate\Support\Facades\Cache::flush();
        $this->getJson('/api/v1/catalog/items', $headers)->assertUnauthorized();
    }

    public function test_local_revocation_invalidates_immediately(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [$client, $tokens] = $this->userTokensFor($user);
        auth()->logout();

        $headers = [
            'Authorization' => 'Bearer '.$tokens['access_token'],
            'X-Tenant-Id' => (string) $tenant->getKey(),
        ];

        // Warm the cache.
        $this->getJson('/api/v1/catalog/items', $headers)->assertOk();
        auth()->logout();

        // RFC 7009 revoke through the real endpoint (fires the D2
        // invalidation hook): the very next request must deny — the
        // bound degrades to zero on the revoking node.
        $this->postJson('/api/oauth/revoke', [
            'token' => $tokens['access_token'],
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
        ])->assertOk();
        auth()->logout();

        $this->getJson('/api/v1/catalog/items', $headers)->assertUnauthorized();
    }

    public function test_client_verdict_cached_then_client_revocation_bites(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [$client, $tokens] = $this->userTokensFor($user);
        auth()->logout();

        $cache = app(RevocationStatusCache::class);

        // Warm both verdicts.
        $this->assertTrue($cache->clientActive((string) $client->getKey()));
        $this->assertNotNull($cache->validToken($this->jtiOf($tokens['access_token'])));

        // Cross-process client revocation (direct row flip).
        $client->forceFill(['revoked' => true])->save();

        // Cached verdict survives within the bound...
        $this->assertTrue($cache->clientActive((string) $client->getKey()));

        // ...and the local invalidation hook (console/HTTP revoke path)
        // zeroes it at once.
        $cache->invalidateClient((string) $client->getKey());
        $this->assertFalse($cache->clientActive((string) $client->getKey()));
    }

    public function test_negative_verdicts_are_never_cached(): void
    {
        $cache = app(RevocationStatusCache::class);

        $this->assertNull($cache->validToken('no-such-jti'));
        $this->assertFalse($cache->clientActive('no-such-client'));

        // A token appearing later (fresh mint) must be visible without
        // waiting for any TTL — nothing negative was stored.
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [, $tokens] = $this->userTokensFor($user);

        $this->assertNotNull($cache->validToken($this->jtiOf($tokens['access_token'])));
    }

    public function test_critical_op_gate_ignores_the_cache(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant, ['platform:user:manage']);
        [, $tokens] = $this->userTokensFor($user);
        $this->stepUp($user);
        auth()->logout();

        $jti = $this->jtiOf($tokens['access_token']);
        $headers = [
            'Authorization' => 'Bearer '.$tokens['access_token'],
            'X-Tenant-Id' => (string) $tenant->getKey(),
            'Content-Type' => 'application/json',
        ];

        // Warm the ordinary-path cache with a passing read.
        $this->getJson('/api/v1/catalog/items', $headers)->assertOk();
        auth()->logout();

        // Cross-process revocation: the ordinary path would still accept
        // within the bound, but a CRITICAL op (suspend: step-up + live
        // authz gate) re-reads authoritative state and denies now.
        \Laravel\Passport\Passport::token()->newQuery()->whereKey($jti)->update(['revoked' => true]);

        $victim = \App\Models\User::factory()->create();
        $this->postJson("/api/v1/platform/users/{$victim->getAuthIdentifier()}:suspend", [
            'tenant_id' => (string) $tenant->getKey(),
        ], $headers)->assertUnauthorized();
    }

    public function test_cache_store_outage_falls_back_to_authoritative_reads(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [, $tokens] = $this->userTokensFor($user);
        auth()->logout();

        $headers = [
            'Authorization' => 'Bearer '.$tokens['access_token'],
            'X-Tenant-Id' => (string) $tenant->getKey(),
        ];

        // Dead cache store, scoped to this component only: get/put/forget
        // all throw. Other cache users (throttling) keep the real store —
        // the D4 posture is that a dead accelerator never bypasses or
        // breaks a security decision, not that the whole cache dies.
        $throwing = $this->createMock(\Illuminate\Contracts\Cache\Repository::class);
        $throwing->method('get')->willThrowException(new \RuntimeException('store down'));
        $throwing->method('put')->willThrowException(new \RuntimeException('store down'));
        $throwing->method('forget')->willThrowException(new \RuntimeException('store down'));
        $this->app->instance(RevocationStatusCache::class, new RevocationStatusCache($throwing));

        $this->getJson('/api/v1/catalog/items', $headers)->assertOk();
        auth()->logout();

        \Laravel\Passport\Passport::token()->newQuery()->whereKey($this->jtiOf($tokens['access_token']))->update(['revoked' => true]);
        $this->getJson('/api/v1/catalog/items', $headers)->assertUnauthorized();
    }

    private function jtiOf(string $jwt): string
    {
        $payload = explode('.', $jwt)[1];
        $decoded = base64_decode(strtr($payload, '-_', '+/'), true);

        return (string) json_decode((string) $decoded, true)['jti'];
    }
}
