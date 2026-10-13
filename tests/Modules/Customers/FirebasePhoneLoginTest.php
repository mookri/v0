<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Customers\Auth\FirebaseIdTokenRejectedException;
use App\Modules\Customers\Auth\FirebaseIdTokenUnavailableException;
use App\Modules\Customers\Contracts\VerifiesPhoneIdTokens;
use App\Modules\Customers\Models\FirebasePhoneAuthIntent;
use App\Platform\Identity\ActorTokenVerifier;
use App\Platform\Tenancy\Models\Tenant;
use Tests\TenantIntegrationTestCase;

/**
 * Firebase phone login (kit): the client SDK proves the phone with
 * Firebase, then the server verifies the resulting ID token against a
 * single-use intent before minting the standard actor token. Covered:
 * intent creation (no oracle, TTL, tenant gate), the exchange's binding
 * rules (phone match, phone-only provider, auth_time replay floor),
 * single-use consumption, eligibility/MFA parity with auth/login, the
 * collapsed uniform 401, the typed 503, and the fail-closed state where
 * no service account is configured.
 *
 * The verifier seam is rebound with a scripted fake (same pattern as
 * SeededOtpCodes), so no test touches credentials or the network — the
 * real kreait verifier gets exactly one test, asserting it fails closed.
 */
final class FirebasePhoneLoginTest extends TenantIntegrationTestCase
{
    private const SECRET = 'customer-firebase-test-secret';

    private const PHONE = '+911234567890';

    /** Rebinds the ID-token seam for this test: claims result or throwable. */
    private function tokens(array|\Throwable $result): void
    {
        $this->app->instance(VerifiesPhoneIdTokens::class, new class ($result) implements VerifiesPhoneIdTokens {
            public function __construct(private array|\Throwable $result)
            {
            }

            public function verify(string $idToken): array
            {
                if ($this->result instanceof \Throwable) {
                    throw $this->result;
                }

                return $this->result;
            }
        });
    }

    /**
     * @return array{phone: string, provider: string, auth_time: int}
     */
    private function claims(string $phone = self::PHONE, string $provider = 'phone', ?int $authTime = null): array
    {
        return ['phone' => $phone, 'provider' => $provider, 'auth_time' => $authTime ?? time()];
    }

    private function secret(): void
    {
        config()->set('identity.hmac_secret', self::SECRET);
    }

    private function openIntent(string $tenantId, string $phone = self::PHONE, int $ttlSeconds = FirebasePhoneAuthIntent::TTL_SECONDS): string
    {
        $intent = FirebasePhoneAuthIntent::query()->create([
            'tenant_id' => $tenantId,
            'purpose' => FirebasePhoneAuthIntent::PURPOSE_LOGIN,
            'phone' => $phone,
            'expires_at' => now()->addSeconds($ttlSeconds),
        ]);

        return (string) $intent->getKey();
    }

    private function exchange(string $tenantId, string $intentId, array $overrides = [])
    {
        return $this->postJson('/api/v1/customers/auth/firebase/exchange', array_merge([
            'tenant_id' => $tenantId,
            'intent_id' => $intentId,
            'id_token' => 'scripted-token-never-verified-here',
        ], $overrides));
    }

    private function member(Tenant $tenant, string $phone, string $externalId): User
    {
        $user = User::factory()->create(['phone' => $phone]);
        $user->forceFill(['external_id' => $externalId])->save();
        $this->makeMembership($tenant, $user);

        return $user;
    }

    private function nowIso(): string
    {
        return gmdate('Y-m-d\TH:i:s.v\Z');
    }

    public function test_intent_is_single_use_and_exchange_mints_the_standard_session(): void
    {
        $this->secret();
        $this->tokens($this->claims());
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $user = $this->member($tenant, self::PHONE, 'cust_fbexchange');

        $intent = $this->postJson('/api/v1/customers/auth/firebase/intent', [
            'tenant_id' => $tid, 'phone' => self::PHONE,
        ])->assertStatus(202);
        $this->assertSame('firebase_phone_intent', $intent->json('data.object'));
        $this->assertSame(600, $intent->json('data.expires_in'));
        $intentId = $intent->json('data.intent_id');

        $this->assertDatabaseHas('firebase_phone_auth_intents', [
            'id' => $intentId, 'tenant_id' => $tid, 'phone' => self::PHONE,
        ], 'central');

        $logged = $this->exchange($tid, $intentId)->assertOk();
        $this->assertSame('customer_session', $logged->json('data.object'));
        $this->assertSame($tid, $logged->json('data.tenant_id'));
        $this->assertSame('cust_fbexchange', $logged->json('data.actor.user_id'));

        // Same HMAC actor token every other login path issues.
        $actor = ActorTokenVerifier::verify(self::SECRET, $logged->json('data.token'), $this->nowIso());
        $this->assertNotNull($actor);
        $this->assertSame('cust_fbexchange', $actor['user_id']);

        // Single-use: the consumed row is gone, so replaying the same
        // intent + token collapses to the uniform 401.
        $this->assertDatabaseMissing('firebase_phone_auth_intents', ['id' => $intentId], 'central');
        $this->exchange($tid, $intentId)->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');

        $this->assertAuditRecorded($tenant, 'customers.auth.logged_in');
        $this->assertDatabaseHas('buyer_profiles', [
            'tenant_id' => $tid, 'user_id' => $user->getAuthIdentifier(),
        ], 'tenant_shared');
    }

    public function test_exchange_rejects_a_token_bound_to_a_different_phone(): void
    {
        $this->secret();
        // The claim is genuine — for someone else's number.
        $this->tokens($this->claims('+919999888777'));
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $this->member($tenant, self::PHONE, 'cust_fbphonemismatch');
        $intentId = $this->openIntent($tid);

        $this->exchange($tid, $intentId)->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');

        // A failed attempt must NOT consume the intent (only success does).
        $this->assertDatabaseHas('firebase_phone_auth_intents', ['id' => $intentId], 'central');
    }

    public function test_exchange_rejects_a_token_whose_auth_time_predates_the_intent(): void
    {
        $this->secret();
        // Token proven an hour ago — stale proof cannot satisfy an intent
        // opened afterwards (replay floor).
        $this->tokens($this->claims(self::PHONE, 'phone', time() - 3600));
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $this->member($tenant, self::PHONE, 'cust_fbstale');
        $intentId = $this->openIntent($tid);

        $this->exchange($tid, $intentId)->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_exchange_rejects_non_phone_sign_in_providers(): void
    {
        $this->secret();
        $this->tokens($this->claims(self::PHONE, 'password'));
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $this->member($tenant, self::PHONE, 'cust_fbprovider');
        $intentId = $this->openIntent($tid);

        $this->exchange($tid, $intentId)->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_intent_is_no_oracle_and_exchange_creates_no_account(): void
    {
        $this->secret();
        $this->tokens($this->claims('+915555555555'));
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();

        // Intent creation succeeds for an unknown phone — it reveals
        // nothing about whether an account exists.
        $intentId = $this->postJson('/api/v1/customers/auth/firebase/intent', [
            'tenant_id' => $tid, 'phone' => '+915555555555',
        ])->assertStatus(202)->json('data.intent_id');

        // Verified phone proof is not account creation (login only).
        $this->exchange($tid, $intentId)->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
        $this->assertDatabaseMissing('users', ['phone' => '+915555555555'], 'central');
    }

    public function test_exchange_denies_accounts_without_active_membership(): void
    {
        $this->secret();
        $this->tokens($this->claims());
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        User::factory()->create(['phone' => self::PHONE]); // no membership
        $intentId = $this->openIntent($tid);

        $this->exchange($tid, $intentId)->assertStatus(403)->assertJsonPath('error.code', 'membership_inactive');
        $this->assertDatabaseHas('firebase_phone_auth_intents', ['id' => $intentId], 'central');
    }

    public function test_exchange_demands_totp_when_mfa_enrolled(): void
    {
        $this->secret();
        $this->tokens($this->claims());
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();

        $user = $this->member($tenant, self::PHONE, 'cust_fbmfa');
        $service = new \App\Platform\Identity\MfaService();
        $service->enroll($user);
        $secret = \Illuminate\Support\Facades\Crypt::decryptString($user->fresh()->mfa_secret);
        $totp = \OTPHP\TOTP::createFromSecret($secret);
        $service->confirm($user->fresh(), $totp->now(), time());

        // No TOTP, wrong TOTP: same single 401 (no oracle on MFA state).
        $this->exchange($tid, $this->openIntent($tid))
            ->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
        $this->exchange($tid, $this->openIntent($tid), ['totp_code' => '000000'])
            ->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');

        // Correct TOTP: full two-factor login.
        $this->exchange($tid, $this->openIntent($tid), ['totp_code' => $totp->now()])
            ->assertOk()->assertJsonPath('data.actor.user_id', 'cust_fbmfa');
    }

    public function test_unknown_foreign_and_expired_intents_collapse_to_one_401(): void
    {
        $this->secret();
        $this->tokens($this->claims());
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $this->member($tenant, self::PHONE, 'cust_fbcollapse');
        $other = (string) $this->makeTenant()->getKey();

        $foreignIntent = $this->openIntent($other);
        $expiredIntent = $this->openIntent($tid, self::PHONE, -1);

        // Bogus id, another tenant's intent, expired row: identical 401.
        $this->exchange($tid, 'intent_does_not_exist')
            ->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
        $this->exchange($tid, $foreignIntent)
            ->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
        $this->exchange($tid, $expiredIntent)
            ->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
    }

    // NOTE: one scripted result per test — Route::getController() freezes
    // the resolved controller (and this constructor-injected seam) after
    // a route's first dispatch, so rebinding mid-test would never reach
    // a second exchange. Fresh app per test keeps each fake honest.

    public function test_rejected_verifier_stays_the_collapsed_401(): void
    {
        $this->secret();
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $this->member($tenant, self::PHONE, 'cust_fbverifier');

        // A rejected token is a normal auth outcome — collapsed 401,
        // never a dependency error.
        $this->tokens(new FirebaseIdTokenRejectedException('scripted rejection'));
        $this->exchange($tid, $this->openIntent($tid))
            ->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_unavailable_verifier_maps_to_the_typed_503(): void
    {
        $this->secret();
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $this->member($tenant, self::PHONE, 'cust_fbverifier');

        // A Firebase outage must never masquerade as a wrong token
        // (S48/S50 taxonomy): typed 503, symbolic report, intent kept.
        $intentId = $this->openIntent($tid);
        $this->tokens(new FirebaseIdTokenUnavailableException('firebase idtoken verify unavailable [scripted]'));

        $this->exchange($tid, $intentId)
            ->assertServiceUnavailable()->assertJsonPath('error.code', 'dependency_unavailable');

        // Failed before the single-use gate: the intent was not burned.
        $this->assertDatabaseHas('firebase_phone_auth_intents', ['id' => $intentId], 'central');
    }

    public function test_real_verifier_fails_closed_without_service_account_credentials(): void
    {
        $this->secret();
        // No scripted fake: the production binding runs, and with no
        // service account configured it must fail closed as the typed
        // 503 BEFORE any network call — never as a 401, never as a session.
        config()->set('otp-providers.audiences.customer.firebase.service_account', '');

        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $this->member($tenant, self::PHONE, 'cust_fbfailclosed');
        $intentId = $this->openIntent($tid);

        $this->exchange($tid, $intentId)
            ->assertServiceUnavailable()->assertJsonPath('error.code', 'dependency_unavailable');

        // The failure happened before the single-use gate: no session was
        // minted and the intent was not burned — only success consumes it.
        $this->assertDatabaseHas('firebase_phone_auth_intents', ['id' => $intentId], 'central');
    }

    public function test_intent_route_gates_tenant_and_phone(): void
    {
        // Unknown tenant: the shared 404, not an oracle-shaped 202.
        $this->postJson('/api/v1/customers/auth/firebase/intent', [
            'tenant_id' => 'tenant_missing', 'phone' => self::PHONE,
        ])->assertNotFound()->assertJsonPath('error.code', 'not_found');

        // Malformed phone: 422 before any row exists.
        $tid = (string) $this->makeTenant()->getKey();
        $this->postJson('/api/v1/customers/auth/firebase/intent', [
            'tenant_id' => $tid, 'phone' => 'not-a-phone',
        ])->assertStatus(422);

        $this->assertDatabaseCount('firebase_phone_auth_intents', 0, 'central');
    }
}
