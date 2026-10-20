<?php

namespace Tests\Unit\Platform\Identity\Otp;

use App\Platform\Identity\Otp\Msg91WidgetProvider;
use App\Platform\Identity\Otp\OtpChallengeManager;
use App\Platform\Identity\Otp\OtpChallengeStore;
use App\Platform\Identity\Otp\OtpProviderRegistry;
use App\Platform\Identity\Otp\OtpRateLimitedException;
use App\Platform\Identity\Otp\OtpSendContext;
use App\Platform\Identity\Otp\OtpSendResult;
use App\Platform\Identity\Otp\OtpVerifyOutcome;
use App\Platform\Identity\Otp\OtpVerifyRequest;
use App\Platform\Identity\Otp\PhoneVerificationProvider;
use App\Platform\Identity\Otp\StoredOtpChallenge;
use Carbon\CarbonImmutable;
use Tests\TestCase;

final class InMemoryOtpChallengeStore implements OtpChallengeStore
{
    /** @var array<string, StoredOtpChallenge> */
    public array $challenges = [];

    /** @var array<string, CarbonImmutable> */
    public array $supersededAt = [];

    public function runLocked(string $tenantId, \Closure $work): mixed
    {
        return $work();
    }

    public function insert(StoredOtpChallenge $challenge): void
    {
        $this->challenges[$challenge->id] = $challenge;
    }

    public function findLiveForUpdate(string $tenantId, array $purposes, string $challengeId, ?string $status = null): ?StoredOtpChallenge
    {
        $c = $this->challenges[$challengeId] ?? null;
        if (! $c || $c->tenantId !== $tenantId || ! in_array($c->purpose, $purposes, true)) {
            return null;
        }
        if (isset($this->supersededAt[$challengeId])) {
            return null;
        }
        if ($status !== null && $c->status !== $status) {
            return null;
        }
        return $c;
    }

    public function findLatestLive(string $tenantId, string $purpose, string $identifier): ?StoredOtpChallenge
    {
        $matching = array_filter($this->challenges, function (StoredOtpChallenge $c) use ($tenantId, $purpose, $identifier) {
            return $c->tenantId === $tenantId
                && $c->purpose === $purpose
                && $c->identifier === $identifier
                && ! isset($this->supersededAt[$c->id]);
        });

        if (empty($matching)) {
            return null;
        }

        usort($matching, fn (StoredOtpChallenge $a, StoredOtpChallenge $b) => $b->createdAt <=> $a->createdAt);
        return reset($matching);
    }

    public function countCreatedSince(string $tenantId, string $purpose, string $identifier, CarbonImmutable $since): int
    {
        return count(array_filter($this->challenges, function (StoredOtpChallenge $c) use ($tenantId, $purpose, $identifier, $since) {
            return $c->tenantId === $tenantId
                && $c->purpose === $purpose
                && $c->identifier === $identifier
                && $c->createdAt >= $since;
        }));
    }

    public function supersedeLive(string $tenantId, string $purpose, string $identifier, CarbonImmutable $now): void
    {
        foreach ($this->challenges as $id => $c) {
            if ($c->tenantId === $tenantId && $c->purpose === $purpose && $c->identifier === $identifier && ! isset($this->supersededAt[$id])) {
                $this->supersededAt[$id] = $now;
            }
        }
    }

    public function update(StoredOtpChallenge $challenge): void
    {
        $this->challenges[$challenge->id] = $challenge;
    }

    public function delete(string $challengeId): void
    {
        unset($this->challenges[$challengeId]);
    }
}

final class FakeOtpProvider implements PhoneVerificationProvider
{
    public function name(): string
    {
        return Msg91WidgetProvider::NAME;
    }

    public function send(OtpSendContext $context): OtpSendResult
    {
        return new OtpSendResult(providerRef: 'ref-' . $context->challengeId);
    }

    public function verify(OtpVerifyRequest $request): OtpVerifyOutcome
    {
        return $request->code === 'valid-token'
            ? OtpVerifyOutcome::Match
            : OtpVerifyOutcome::Mismatch;
    }
}

final class OtpChallengeManagerTest extends TestCase
{
    private InMemoryOtpChallengeStore $store;
    private OtpChallengeManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        // Pure unit scope: the in-memory store means no database, so the
        // federation audit listeners (ADR-0020 D5 — record behavior
        // covered in AuthEventChainTest) must not write here.
        \Illuminate\Support\Facades\Event::fake([
            \App\Platform\Identity\Otp\OtpChallengeConsumed::class,
            \App\Platform\Identity\Otp\OtpChallengeRejected::class,
        ]);

        $this->store = new InMemoryOtpChallengeStore();
        $provider = new FakeOtpProvider();
        $registry = new OtpProviderRegistry([Msg91WidgetProvider::NAME => $provider]);
        $this->manager = new OtpChallengeManager($this->store, $registry);
    }

    public function test_compounding_resend_schedule_matrix(): void
    {
        $t0 = CarbonImmutable::parse('2026-10-13 12:00:00');
        $challenge = $this->manager->issue('tenant-1', 'login', '919876543210', $t0);

        // 1. Resend #1 blocked < 60s
        $t30s = $t0->addSeconds(30);
        try {
            $this->manager->resend('tenant-1', 'login', $challenge->id, $t30s);
            $this->fail('Expected OtpRateLimitedException');
        } catch (OtpRateLimitedException $e) {
            $this->assertSame(30, $e->retryAfterSeconds);
        }

        // Resend #1 allowed at >= 60s
        $t60s = $t0->addSeconds(60);
        $resend1 = $this->manager->resend('tenant-1', 'login', $challenge->id, $t60s);
        $this->assertNotNull($resend1);
        $this->assertSame(2, $resend1->resendsLeft);

        // 2. Resend #2 blocked < 180s after resend #1 (at 60 + 100 = 160s)
        $t160s = $t60s->addSeconds(100);
        try {
            $this->manager->resend('tenant-1', 'login', $challenge->id, $t160s);
            $this->fail('Expected OtpRateLimitedException');
        } catch (OtpRateLimitedException $e) {
            $this->assertSame(80, $e->retryAfterSeconds); // 180 - 100 = 80
        }

        // Resend #2 allowed at >= 180s
        $t240s = $t60s->addSeconds(180);
        $resend2 = $this->manager->resend('tenant-1', 'login', $challenge->id, $t240s);
        $this->assertNotNull($resend2);
        $this->assertSame(1, $resend2->resendsLeft);

        // 3. Resend #3 blocked < 540s after resend #2
        $t500s = $t240s->addSeconds(200);
        try {
            $this->manager->resend('tenant-1', 'login', $challenge->id, $t500s);
            $this->fail('Expected OtpRateLimitedException');
        } catch (OtpRateLimitedException $e) {
            $this->assertSame(340, $e->retryAfterSeconds); // 540 - 200 = 340
        }

        // Resend #3 allowed at >= 540s
        $t780s = $t240s->addSeconds(540);
        $resend3 = $this->manager->resend('tenant-1', 'login', $challenge->id, $t780s);
        $this->assertNotNull($resend3);
        $this->assertSame(0, $resend3->resendsLeft);

        // 4. Resend #4 blocked (max 3 reached) -> rate limited to freshRetryAfter
        try {
            $this->manager->resend('tenant-1', 'login', $challenge->id, $t780s->addSeconds(10));
            $this->fail('Expected OtpRateLimitedException on 4th resend');
        } catch (OtpRateLimitedException $e) {
            $this->assertGreaterThan(0, $e->retryAfterSeconds);
        }
    }

    public function test_fresh_challenge_is_gated_while_live_challenge_exists(): void
    {
        $t0 = CarbonImmutable::parse('2026-10-13 12:00:00');
        $this->manager->issue('tenant-1', 'login', '919876543210', $t0);

        // Attempting fresh issue before 600s -> 429
        try {
            $this->manager->issue('tenant-1', 'login', '919876543210', $t0->addSeconds(300));
            $this->fail('Expected OtpRateLimitedException');
        } catch (OtpRateLimitedException $e) {
            $this->assertGreaterThan(0, $e->retryAfterSeconds);
        }

        // After TTL + fresh cooldown (e.g. 1300s), fresh challenge succeeds
        $tFresh = $t0->addSeconds(1300);
        $fresh = $this->manager->issue('tenant-1', 'login', '919876543210', $tFresh);
        $this->assertNotNull($fresh);
    }

    public function test_hourly_cap_blocks_fourth_fresh_challenge_in_rolling_hour(): void
    {
        $t0 = CarbonImmutable::parse('2026-10-13 12:00:00');
        // Simulate 3 fresh challenges created within the hour
        for ($i = 0; $i < 3; $i++) {
            $c = new StoredOtpChallenge(
                id: 'c-' . $i,
                tenantId: 'tenant-1',
                purpose: 'login',
                identifier: '919876543210',
                codeHash: null,
                provider: Msg91WidgetProvider::NAME,
                providerRef: 'ref',
                status: 'sent',
                payload: [],
                createdAt: $t0->addMinutes($i * 10),
                expiresAt: $t0->addMinutes($i * 10 + 20),
                attempts: 0,
                maxAttempts: 3,
                resendCount: 0,
                lastSentAt: $t0->addMinutes($i * 10),
            );
            $this->store->insert($c);
            // Mark superseded so fresh gate doesn't block on live row
            $this->store->supersedeLive('tenant-1', 'login', '919876543210', $t0->addMinutes($i * 10 + 1));
        }

        // 4th fresh challenge within rolling hour
        $this->expectException(OtpRateLimitedException::class);
        $this->manager->issue('tenant-1', 'login', '919876543210', $t0->addMinutes(35));
    }

    public function test_hold_and_consume_two_phase_mfa_flow(): void
    {
        $t0 = CarbonImmutable::parse('2026-10-13 12:00:00');
        $challenge = $this->manager->issue('tenant-1', 'login', '919876543210', $t0);

        // Step 1: verify with holdOnMatch = true
        $verifyResult = $this->manager->verify(
            tenantId: 'tenant-1',
            purposes: ['login'],
            challengeId: $challenge->id,
            token: 'valid-token',
            now: $t0->addSeconds(10),
            holdOnMatch: true,
        );

        $this->assertSame(\App\Platform\Identity\Otp\OtpVerifyStatus::Held, $verifyResult->status);
        $this->assertSame('token_verified', $verifyResult->challenge?->status);

        // Row is preserved in store in status 'token_verified'
        $stored = $this->store->challenges[$challenge->id] ?? null;
        $this->assertNotNull($stored);
        $this->assertSame('token_verified', $stored->status);

        // Step 2: consumeVerified on held row succeeds and removes row
        $consumed = $this->manager->consumeVerified(
            tenantId: 'tenant-1',
            purposes: ['login'],
            challengeId: $challenge->id,
            now: $t0->addSeconds(20),
        );

        $this->assertNotNull($consumed);
        $this->assertSame($challenge->id, $consumed->id);
        $this->assertArrayNotHasKey($challenge->id, $this->store->challenges);
    }

    public function test_consume_verified_fails_on_plain_sent_row(): void
    {
        $t0 = CarbonImmutable::parse('2026-10-13 12:00:00');
        $challenge = $this->manager->issue('tenant-1', 'login', '919876543210', $t0);

        // Trying to consume row still in 'sent' status
        $consumed = $this->manager->consumeVerified(
            tenantId: 'tenant-1',
            purposes: ['login'],
            challengeId: $challenge->id,
            now: $t0->addSeconds(10),
        );

        $this->assertNull($consumed);
    }
}
