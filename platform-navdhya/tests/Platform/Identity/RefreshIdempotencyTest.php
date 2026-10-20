<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\OAuth\RefreshIdempotencyStore;
use App\Platform\Identity\OAuth\TokenReuseDetected;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Tests\Platform\Identity\Concerns\IssuesUserTokens;
use Tests\Support\FailOnceRefreshTokenRepository;
use Tests\TenantIntegrationTestCase;

/**
 * D4 refresh contract (ADR-0020): rotation is atomic (old consumed +
 * successor created in one transaction — a failure mid-way leaves the
 * presented token usable, never a dead-end), and a retry with the SAME
 * client + Idempotency-Key + request fingerprint recovers the original
 * response inside federation.refresh_retry_window_seconds. Every other
 * retry (different key, different fingerprint, elapsed window, store
 * down) presents a consumed token and is reuse: lineage kill + the
 * `session.reuse_detected` audit event.
 */
final class RefreshIdempotencyTest extends TenantIntegrationTestCase
{
    use IssuesUserTokens;

    /**
     * @return array<string, string>
     */
    private function refreshPayload(Client $client, string $refreshToken): array
    {
        return [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
        ];
    }

    public function test_same_key_retry_recovers_the_original_response_inside_the_window(): void
    {
        $user = User::factory()->create();
        [$client, $tokens] = $this->userTokensFor($user);
        $payload = $this->refreshPayload($client, $tokens['refresh_token']);

        $original = $this->withHeaders(['Idempotency-Key' => 'retry-key-0001'])
            ->postJson('/oauth/token', $payload);
        $original->assertOk();
        $issued = Passport::token()->newQuery()->count();

        $replay = $this->withHeaders(['Idempotency-Key' => 'retry-key-0001'])
            ->postJson('/oauth/token', $payload);
        $replay->assertOk();
        $replay->assertHeader('Idempotent-Replay', 'true');

        // Byte-identical: the recovery returns the ORIGINAL response —
        // no token was re-minted, nothing rotated twice.
        $this->assertSame($original->getContent(), $replay->getContent());
        $this->assertSame($issued, Passport::token()->newQuery()->count());

        // The lineage stayed alive: the recovered (successor) refresh
        // token still exchanges normally under a fresh key.
        $successor = (string) json_decode($original->getContent(), true)['refresh_token'];
        $rotated = $this->withHeaders(['Idempotency-Key' => 'retry-key-0002'])
            ->postJson('/oauth/token', $this->refreshPayload($client, $successor));
        $rotated->assertOk();
        $rotated->assertHeaderMissing('Idempotent-Replay');
        $this->assertNotSame($successor, $rotated->json('refresh_token'));
    }

    public function test_retry_with_a_different_key_is_reuse_and_kills_the_lineage(): void
    {
        Event::fake([TokenReuseDetected::class]);

        $user = User::factory()->create();
        [$client, $tokens] = $this->userTokensFor($user);
        $payload = $this->refreshPayload($client, $tokens['refresh_token']);

        $original = $this->withHeaders(['Idempotency-Key' => 'retry-key-0001'])
            ->postJson('/oauth/token', $payload);
        $original->assertOk();

        // Same consumed token, different key: not the original request
        // — a mismatched retry is reuse.
        $reuse = $this->withHeaders(['Idempotency-Key' => 'retry-key-0002'])
            ->postJson('/oauth/token', $payload);
        $reuse->assertStatus(400);
        Event::assertDispatched(TokenReuseDetected::class);

        // Family dead: the successor from the first rotation is revoked
        // with the rest of the lineage.
        $successor = (string) json_decode($original->getContent(), true)['refresh_token'];
        $this->withHeaders(['Idempotency-Key' => 'retry-key-0003'])
            ->postJson('/oauth/token', $this->refreshPayload($client, $successor))
            ->assertStatus(400);
    }

    public function test_retry_after_the_window_is_reuse(): void
    {
        Event::fake([TokenReuseDetected::class]);

        $user = User::factory()->create();
        [$client, $tokens] = $this->userTokensFor($user);
        $payload = $this->refreshPayload($client, $tokens['refresh_token']);

        $original = $this->withHeaders(['Idempotency-Key' => 'retry-key-0001'])
            ->postJson('/oauth/token', $payload);
        $original->assertOk();

        // Past federation.refresh_retry_window_seconds (60): the record
        // expired — the same "retry" now presents a consumed token.
        $this->travel(61)->seconds();

        $late = $this->withHeaders(['Idempotency-Key' => 'retry-key-0001'])
            ->postJson('/oauth/token', $payload);
        $late->assertStatus(400);
        Event::assertDispatched(TokenReuseDetected::class);
    }

    public function test_changed_request_fingerprint_is_reuse(): void
    {
        Event::fake([TokenReuseDetected::class]);

        $user = User::factory()->create();
        [$client, $tokens] = $this->userTokensFor($user);
        $payload = $this->refreshPayload($client, $tokens['refresh_token']);

        $original = $this->withHeaders(['Idempotency-Key' => 'retry-key-0001'])
            ->postJson('/oauth/token', $payload);
        $original->assertOk();

        // Same key, same consumed token, altered request: not the same
        // exchange — mismatched fingerprint is reuse.
        $altered = $payload;
        $altered['scope'] = 'read';

        $mismatch = $this->withHeaders(['Idempotency-Key' => 'retry-key-0001'])
            ->postJson('/oauth/token', $altered);
        $mismatch->assertStatus(400);
        Event::assertDispatched(TokenReuseDetected::class);
    }

    public function test_same_key_with_a_new_refresh_token_rotates_normally(): void
    {
        $user = User::factory()->create();
        [$client, $tokens] = $this->userTokensFor($user);

        $original = $this->withHeaders(['Idempotency-Key' => 'retry-key-0001'])
            ->postJson('/oauth/token', $this->refreshPayload($client, $tokens['refresh_token']));
        $original->assertOk();

        // Same key but a different, unconsumed token: a different
        // fingerprint — no recovery applies, this is an ordinary
        // rotation (reuse protection stays scoped to consumed tokens).
        $successor = (string) json_decode($original->getContent(), true)['refresh_token'];
        $second = $this->withHeaders(['Idempotency-Key' => 'retry-key-0001'])
            ->postJson('/oauth/token', $this->refreshPayload($client, $successor));
        $second->assertOk();
        $second->assertHeaderMissing('Idempotent-Replay');
        $this->assertNotSame($successor, $second->json('refresh_token'));
    }

    public function test_cache_outage_disables_recovery_and_fails_closed_to_reuse(): void
    {
        Event::fake([TokenReuseDetected::class]);

        // Dead store scoped to this component only (constructor-injected
        // repository): the exchange itself must never depend on it.
        $throwing = $this->createMock(\Illuminate\Contracts\Cache\Repository::class);
        $throwing->method('get')->willThrowException(new \RuntimeException('store down'));
        $throwing->method('add')->willThrowException(new \RuntimeException('store down'));
        $this->app->instance(
            RefreshIdempotencyStore::class,
            new RefreshIdempotencyStore($throwing)
        );

        $user = User::factory()->create();
        [$client, $tokens] = $this->userTokensFor($user);
        $payload = $this->refreshPayload($client, $tokens['refresh_token']);

        // Issuance is unaffected: a dead accelerator never breaks a
        // security decision (D4 failure matrix).
        $original = $this->withHeaders(['Idempotency-Key' => 'retry-key-0001'])
            ->postJson('/oauth/token', $payload);
        $original->assertOk();

        // Nothing was recoverable, so the retry presents a consumed
        // token: reuse detection still fires — the outage never
        // bypasses the security check.
        $retry = $this->withHeaders(['Idempotency-Key' => 'retry-key-0001'])
            ->postJson('/oauth/token', $payload);
        $retry->assertStatus(400);
        Event::assertDispatched(TokenReuseDetected::class);
    }

    public function test_rotation_failure_rolls_back_and_leaves_the_presented_token_usable(): void
    {
        // MySQL-unavailable cell (D4): fail the LAST write of the
        // rotation — after old tokens were already revoked and the new
        // access token persisted — to prove one transaction, not a
        // half-state.
        $this->app->bind(
            \Laravel\Passport\Bridge\RefreshTokenRepository::class,
            static fn ($app): FailOnceRefreshTokenRepository => new FailOnceRefreshTokenRepository($app['events'])
        );

        $user = User::factory()->create();
        [$client, $tokens] = $this->userTokensFor($user);

        $oldAccess = Passport::token()->newQuery()
            ->where('user_id', $user->getKey())
            ->where('client_id', (string) $client->getKey())
            ->firstOrFail();
        $oldRefresh = Passport::refreshToken()->newQuery()
            ->where('access_token_id', $oldAccess->getKey())
            ->firstOrFail();
        $accessCountBefore = Passport::token()->newQuery()->count();

        FailOnceRefreshTokenRepository::$failNext = true;

        $failed = $this->postJson('/oauth/token', $this->refreshPayload($client, $tokens['refresh_token']));
        $failed->assertStatus(500);

        // Nothing changed: old credential unconsumed, successor never
        // created, no orphaned access token.
        $this->assertDatabaseHas('oauth_refresh_tokens', ['id' => $oldRefresh->getKey(), 'revoked' => false], 'central');
        $this->assertDatabaseHas('oauth_access_tokens', ['id' => $oldAccess->getKey(), 'revoked' => false], 'central');
        $this->assertSame($accessCountBefore, Passport::token()->newQuery()->count());

        // A failed rotation is a retry, never a dead-end: the very same
        // presented token exchanges on the next attempt (auto-recovered
        // authoritative state — not re-auth).
        $recovered = $this->postJson('/oauth/token', $this->refreshPayload($client, $tokens['refresh_token']));
        $recovered->assertOk();
        $this->assertNotSame($tokens['refresh_token'], $recovered->json('refresh_token'));
    }
}
