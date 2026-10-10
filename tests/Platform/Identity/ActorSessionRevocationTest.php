<?php

namespace Tests\Platform\Identity;

use App\Modules\Customers\Auth\CustomerOtpService;
use App\Modules\Customers\Auth\SeededOtpCodes;
use App\Platform\Identity\ActorTokenIssuer;
use App\Platform\Identity\ActorTokenVerifier;
use App\Platform\Identity\Models\ActorSession;
use Tests\TenantIntegrationTestCase;

/**
 * HMAC actor-session revocation + rotation (M5 / TOKEN-001, gates G3 +
 * G4): logout kills the presented jti server-side, MFA completion and a
 * presented-predecessor login rotate the session (predecessor dies, the
 * envelope carries the replacement), and every session of an actor can
 * be revoked in one sweep. A revoked or missing jti fails closed with
 * the same 401 as any other miss.
 */
final class ActorSessionRevocationTest extends TenantIntegrationTestCase
{
    private const SECRET = 'revocation-test-secret';

    private function otp(): SeededOtpCodes
    {
        $codes = new SeededOtpCodes();
        $this->app->singleton(CustomerOtpService::class, static fn (): CustomerOtpService => new CustomerOtpService($codes));
        config()->set('identity.hmac_secret', self::SECRET);

        return $codes;
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: SeededOtpCodes}
     *         [tenant id, phone, signup token, seeded codes]
     */
    private function signedUp(): array
    {
        $codes = $this->otp();
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();

        $challengeId = $this->postJson('/api/v1/customers/auth/signup', [
            'tenant_id' => $tid, 'phone' => '+911234567890', 'name' => 'Ravi',
        ])->assertStatus(202)->json('data.challenge_id');

        $token = $this->postJson('/api/v1/customers/auth/verify', [
            'tenant_id' => $tid, 'challenge_id' => $challengeId, 'code' => $codes->lastFor($challengeId),
        ])->assertCreated()->json('data.token');

        return [$tid, '+911234567890', $token, $codes];
    }

    private function nowIso(): string
    {
        return gmdate('Y-m-d\TH:i:s.v\Z');
    }

    private function assertBearerAlive(string $token, string $tid): void
    {
        $this->getJson('/api/v1/catalog/items', [
            'Authorization' => "Bearer {$token}",
            'X-Tenant-Id' => $tid,
        ])->assertOk();
        auth()->logout();
    }

    private function bearerDead(string $token, string $tid): void
    {
        $this->getJson('/api/v1/catalog/items', [
            'Authorization' => "Bearer {$token}",
            'X-Tenant-Id' => $tid,
        ])->assertUnauthorized();
        auth()->logout();
    }

    public function test_logout_revokes_the_presented_bearer(): void
    {
        [$tid, , $token] = $this->signedUp();

        $this->assertBearerAlive($token, $tid);

        $this->postJson('/api/v1/customers/auth/logout', [], [
            'Authorization' => "Bearer {$token}",
            'X-Tenant-Id' => $tid,
        ])->assertOk()->assertJsonPath('data.signed_out', true);
        auth()->logout();

        // G4: the credential itself died — not merely the client copy.
        $this->bearerDead($token, $tid);

        $claims = ActorTokenVerifier::verify(self::SECRET, $token, $this->nowIso());
        $this->assertNull($claims, 'Verifier must fail closed on the revoked jti.');
        $this->assertNotNull(
            ActorSession::query()->whereNotNull('revoked_at')->first(),
            'The session row must carry the revocation stamp.'
        );
    }

    public function test_mfa_completion_rotates_and_kills_the_predecessor(): void
    {
        [$tid, , $token] = $this->signedUp();

        $headers = ['Authorization' => "Bearer {$token}", 'X-Tenant-Id' => $tid];

        // I2: MFA enroll now requires step-up (stepup:2)
        $stepUp = $this->postJson('/api/v1/identity/mfa/enroll/step-up', [], $headers)
            ->assertAccepted()->assertJsonPath('data.object', 'otp_challenge');
        $challengeId = $stepUp->json('data.challenge_id');

        // Use the StepUpOtpService test store for the OTP code
        $otpCode = \App\Platform\Identity\StepUpOtpService::getTestCode($challengeId);
        $this->assertNotNull($otpCode);

        $enrolled = $this->postJson('/api/v1/identity/mfa/enroll/verify', [
            'challenge_id' => $challengeId,
            'code' => $otpCode,
        ], $headers)->assertCreated()->assertJsonPath('data.object', 'mfa_enrollment');
        parse_str((string) parse_url($enrolled->json('data.otpauth_url'), PHP_URL_QUERY), $query);
        $secret = $query['secret'];

        $this->postJson('/api/v1/identity/mfa/confirm', ['code' => \OTPHP\TOTP::createFromSecret($secret)->now()], $headers)
            ->assertOk();
        auth()->logout();

        // G3: the elevation rotates the session — the envelope carries
        // the replacement, the predecessor dies with this very request.
        $verified = $this->postJson('/api/v1/identity/mfa/verify', [
            'code' => \OTPHP\TOTP::createFromSecret($secret)->now(),
        ], $headers)->assertOk()->assertJsonPath('data.verified', true);

        $rotated = $verified->json('data.token');
        $this->assertNotEmpty($rotated);
        $this->assertNotSame($token, $rotated);
        $this->assertNotSame(
            ActorTokenVerifier::verify(self::SECRET, $token, $this->nowIso())['session_id'] ?? null,
            $verified->json('data.actor.session_id')
        );
        auth()->logout();

        $this->bearerDead($token, $tid);
        $this->assertBearerAlive($rotated, $tid);
    }

    public function test_login_with_presented_predecessor_revokes_it(): void
    {
        [$tid, $phone, $predecessor, $codes] = $this->signedUp();

        $challengeId = $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'purpose' => 'login', 'identity' => $phone,
        ])->assertStatus(202)->json('data.challenge_id');

        $logged = $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid, 'challenge_id' => $challengeId, 'code' => $codes->lastFor($challengeId),
        ], ['Authorization' => "Bearer {$predecessor}"])->assertOk();
        auth()->logout();

        $successor = $logged->json('data.token');
        $this->assertNotEmpty($successor);

        // I15: the login minted its replacement and revoked the session
        // the caller presented.
        $this->bearerDead($predecessor, $tid);
        $this->assertBearerAlive($successor, $tid);
    }

    public function test_revoke_for_user_kills_every_session(): void
    {
        $this->signedUp();

        config()->set('identity.hmac_secret', self::SECRET);
        $first = ActorTokenIssuer::mint(self::SECRET, 'sweep-probe', 86400, $this->nowIso())['token'];
        $second = ActorTokenIssuer::mint(self::SECRET, 'sweep-probe', 86400, $this->nowIso())['token'];

        $this->assertSame(2, ActorSession::query()->where('user_id', 'sweep-probe')->whereNull('revoked_at')->count());

        ActorSession::revokeForUser('sweep-probe');

        $this->assertSame(0, ActorSession::query()->where('user_id', 'sweep-probe')->whereNull('revoked_at')->count());
        $this->assertNull(ActorTokenVerifier::verify(self::SECRET, $first, $this->nowIso()));
        $this->assertNull(ActorTokenVerifier::verify(self::SECRET, $second, $this->nowIso()));
    }
}
