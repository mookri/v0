<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\ActorTokenVerifier;
use App\Platform\Identity\Models\ActorSession;
use Tests\Support\InteractsWithMsg91;
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
    use InteractsWithMsg91;

    private const SECRET = 'revocation-test-secret-at-least-32-chars-long!';

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeMsg91();
        config()->set('identity.hmac_secret', self::SECRET);
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     *         [tenant id, phone, signup token]
     */
    private function signedUp(): array
    {
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $phone = '+911234567890';

        $challengeId = $this->postJson('/api/v1/customers/auth/signup', [
            'tenant_id' => $tid, 'phone' => $phone, 'name' => 'Ravi',
        ])->assertStatus(202)->json('data.challenge_id');

        $token = $this->postJson('/api/v1/customers/auth/verify', [
            'tenant_id' => $tid,
            'challenge_id' => $challengeId,
            'token' => $this->msg91TokenFor($phone),
        ])->assertCreated()->json('data.token');

        return [$tid, $phone, $token];
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
        [$tid, $phone, $token] = $this->signedUp();

        $headers = ['Authorization' => "Bearer {$token}", 'X-Tenant-Id' => $tid];

        $user = User::query()->where('phone', $phone)->firstOrFail();

        // I2: MFA enroll now requires step-up (stepup:2)
        $stepUp = $this->postJson('/api/v1/identity/mfa/enroll/step-up', [], $headers)
            ->assertAccepted()->assertJsonPath('data.object', 'otp_challenge');
        $challengeId = $stepUp->json('data.challenge_id');

        $stepUpToken = $this->msg91TokenFor((string) $user->email);

        $enrolled = $this->postJson('/api/v1/identity/mfa/enroll/verify', [
            'challenge_id' => $challengeId,
            'token' => $stepUpToken,
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

        // The original bearer is dead now.
        $this->bearerDead($token, $tid);
        $this->assertBearerAlive($rotated, $tid);
    }

    public function test_predecessor_login_revokes_prior_session(): void
    {
        [$tid, $phone, $token] = $this->signedUp();

        $this->assertBearerAlive($token, $tid);

        // Subsequent login presenting the prior bearer as a predecessor
        $challengeId = $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'phone' => $phone,
        ])->assertStatus(202)->json('data.challenge_id');

        $login = $this->postJson(
            '/api/v1/customers/auth/login',
            ['tenant_id' => $tid, 'challenge_id' => $challengeId, 'token' => $this->msg91TokenFor($phone)],
            ['Authorization' => "Bearer {$token}"]
        )->assertOk();

        $replacement = $login->json('data.token');
        $this->assertNotSame($token, $replacement);

        // Predecessor is dead, replacement is alive.
        $this->bearerDead($token, $tid);
        $this->assertBearerAlive($replacement, $tid);
    }
}
