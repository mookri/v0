<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\OAuth\IssuerIdentifier;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Client;
use Tests\Platform\Identity\Concerns\IssuesUserTokens;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * A-SEC-004-7: OIDC prompt at the authorize endpoint — the full
 * none|login|consent set, enforced and PINNED here (Passport
 * implements the semantics natively but not the validation, and the
 * platform doctrine refuses silent semantic loss):
 *
 *  - prompt=none with an active grant silently approves (no consent
 *    page — the SSO silent flow), without a grant answers
 *    consent_required, guest answers login_required (the latter
 *    pinned in AuthorizationResponseIssuerTest with iss),
 *  - prompt=login forces re-authentication even on a fresh session
 *    (session destroyed, promptedForLogin loop-guard parked, resume
 *    lands on the unified ceremony), and the resume passes once the
 *    flag is set,
 *  - prompt=consent forces the consent screen despite an active
 *    grant,
 *  - `none` combined with any other value, an unknown value, and a
 *    non-string (array injection) all fail invalid_request — with
 *    state + RFC 9207 iss on validated-redirect delivery, on ANY
 *    request (validation is not gated to openid scope: prompt needs
 *    no OIDC claims, unlike max_age — AuthorizationResponseIssuerTest
 *    pins prompt=none + non-openid scope still answering
 *    login_required, never a login bounce).
 *
 * Every error redirect carries state + iss; nothing parks a re-auth
 * challenge from a malformed prompt.
 */
final class PromptEnforcementTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;
    use IssuesUserTokens;

    private const HOST = 'http://auth.localhost';

    private const REDIRECT = 'https://example.test/callback';

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeMsg91();

        config([
            'federation.domain' => 'auth.localhost',
            'federation.issuer' => 'http://auth.localhost',
            'federation.mode' => 'federated',
            'identity.hmac_secret' => 'test-hmac-secret-for-federation-32-chars!!',
        ]);
    }

    /**
     * @param  array<string, mixed>  $profileOverrides
     */
    private function oidcClient(array $profileOverrides = []): Client
    {
        return $this->oauthWebClient('OIDC web client', array_merge([
            'allowed_scopes' => ['openid'],
        ], $profileOverrides));
    }

    /**
     * An active, non-revoked token row for the user+client — the
     * "remembered grant" Passport's silent path looks for.
     */
    private function grantFor(User $user, Client $client): void
    {
        $client->tokens()->create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'user_id' => $user->getKey(),
            'name' => 'prompt-enforcement-grant',
            'scopes' => ['openid'],
            'revoked' => false,
            'expires_at' => now()->addHour(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function authorizeParams(Client $client, array $overrides = []): array
    {
        return array_merge([
            'client_id' => (string) $client->getKey(),
            'redirect_uri' => self::REDIRECT,
            'response_type' => 'code',
            'scope' => 'openid',
            'state' => 'xyz',
            'code_challenge' => self::CHALLENGE,
            'code_challenge_method' => 'S256',
            'nonce' => 'prompt-nonce-1',
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function freshCeremony(): array
    {
        return [
            'auth_time' => time() - 30,
            'amr' => ['otp'],
            'acr' => 'aal1',
            'sid' => '01JPROMPTFRESHSESSION0000001',
        ];
    }

    private function startOtp(string $phone): string
    {
        $res = $this->postJson(self::HOST.'/auth/login/otp', ['phone' => $phone]);
        $res->assertStatus(202);

        return (string) $res->json('data.challenge_id');
    }

    private function loginViaOtp(string $phone): TestResponse
    {
        return $this->postJson(self::HOST.'/auth/login/verify', [
            'challenge_id' => $this->startOtp($phone),
            'token' => $this->msg91TokenFor($phone),
        ]);
    }

    /** @return array<string, mixed> the query of a redirect Location */
    private function redirectQuery(TestResponse $res): array
    {
        $location = (string) $res->headers->get('Location');
        $this->assertStringStartsWith(self::REDIRECT.'?', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        return $query;
    }

    public function test_prompt_none_silently_approves_with_an_active_grant(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();
        $this->grantFor($user, $client);

        $res = $this->actingAs($user)->get(self::HOST.'/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, ['prompt' => 'none'])
        ));

        // The SSO silent flow: straight to the code, no consent page,
        // no error — the grant is remembered and interaction is not
        // required.
        $res->assertStatus(302);
        $query = $this->redirectQuery($res);
        $this->assertArrayHasKey('code', $query);
        $this->assertArrayNotHasKey('error', $query);
        $this->assertSame('xyz', $query['state']);
    }

    public function test_prompt_none_without_a_grant_returns_consent_required(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        $res = $this->actingAs($user)->get(self::HOST.'/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, ['prompt' => 'none'])
        ));

        $res->assertStatus(302);
        $query = $this->redirectQuery($res);
        $this->assertSame('consent_required', $query['error']);
        $this->assertSame('xyz', $query['state']);
        $this->assertSame(IssuerIdentifier::value(), $query['iss']);
    }

    public function test_prompt_login_forces_re_authentication_even_on_a_fresh_session(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        $this->withSession(['oidc.ceremony' => $this->freshCeremony()]);

        $res = $this->actingAs($user)->get(self::HOST.'/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, ['prompt' => 'login'])
        ));

        // The session is destroyed and the browser is handed to the
        // unified ceremony — freshness of the old session is
        // irrelevant, the user must authenticate AGAIN.
        $res->assertStatus(302);
        $this->assertStringEndsWith('/auth/login', (string) $res->headers->get('Location'));
        $this->assertGuest();

        // The loop-guard flag is parked (so the resume after login is
        // not bounced again) and the old ceremony facts are gone.
        $this->assertTrue(session('promptedForLogin'));
        $this->assertNull(session('oidc.ceremony'));
    }

    public function test_prompt_login_resume_passes_once_the_flag_is_parked(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        // Resume after the ceremony: authenticated again, guard flag
        // present — the forced logout must NOT re-trigger (login
        // loop), the request proceeds to consent.
        $this->withSession([
            'promptedForLogin' => true,
            'oidc.ceremony' => $this->freshCeremony(),
        ]);

        $this->actingAs($user)->get(self::HOST.'/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, ['prompt' => 'login'])
        ))->assertOk();

        // Consumed: the flag does not linger into later requests.
        $this->assertNull(session('promptedForLogin'));
    }

    public function test_completed_ceremony_satisfies_prompt_login(): void
    {
        $client = $this->oidcClient();

        // Guest + prompt=login: bounced to the ceremony (no session to
        // destroy — the login IS the re-authentication).
        $bounce = $this->get(self::HOST.'/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, ['prompt' => 'login'])
        ));
        $bounce->assertStatus(302);
        $this->assertStringEndsWith('/auth/login', (string) $bounce->headers->get('Location'));

        // The OTP ceremony completes and hands back the resume target…
        $login = $this->loginViaOtp('9898989871');
        $login->assertOk();
        $resume = (string) $login->json('data.redirect');
        $this->assertStringStartsWith('/oauth/authorize?', $resume);

        // …and the resumed authorize request passes the prompt.
        $this->get(self::HOST.$resume)->assertOk();
    }

    public function test_prompt_consent_forces_the_screen_despite_an_active_grant(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();
        $this->grantFor($user, $client);

        // Same grant silently approves under prompt=none (test above);
        // prompt=consent must render the consent page anyway.
        $this->actingAs($user)->get(self::HOST.'/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, ['prompt' => 'consent'])
        ))->assertOk();
    }

    public function test_prompt_none_with_other_values_is_invalid_request(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        $res = $this->actingAs($user)->get(self::HOST.'/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, ['prompt' => 'none login'])
        ));

        // OIDC Core's MUST: none may never be combined. Rejection is
        // loud (invalid_request), never a silent downgrade to one of
        // the values, and never a re-auth trigger.
        $res->assertStatus(302);
        $query = $this->redirectQuery($res);
        $this->assertSame('invalid_request', $query['error']);
        $this->assertStringContainsString('none', (string) $query['error_description']);
        $this->assertSame('xyz', $query['state']);
        $this->assertSame(IssuerIdentifier::value(), $query['iss']);
        $this->assertNull(session('oidc.reauth'));
    }

    public function test_unknown_prompt_value_is_invalid_request(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        $res = $this->actingAs($user)->get(self::HOST.'/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, ['prompt' => 'select_account'])
        ));

        // Loud rejection over silent semantic loss: an interaction
        // type this issuer does not implement must never degrade into
        // an ordinary flow.
        $res->assertStatus(302);
        $query = $this->redirectQuery($res);
        $this->assertSame('invalid_request', $query['error']);
        $this->assertStringContainsString('select_account', (string) $query['error_description']);
        $this->assertSame('xyz', $query['state']);
        $this->assertSame(IssuerIdentifier::value(), $query['iss']);
    }

    public function test_array_prompt_is_invalid_request(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        // ?prompt[]=none — array injection (and the duplicated
        // ?prompt=a&prompt=b shape, same bag) is a rejection, never a
        // coerced string.
        $res = $this->actingAs($user)->get(self::HOST.'/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, ['prompt' => ['none']])
        ));

        $res->assertStatus(302);
        $query = $this->redirectQuery($res);
        $this->assertSame('invalid_request', $query['error']);
        $this->assertStringContainsString('space-delimited string', (string) $query['error_description']);
        $this->assertSame('xyz', $query['state']);
        $this->assertSame(IssuerIdentifier::value(), $query['iss']);
    }

    public function test_prompt_validation_applies_without_openid_scope(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        // Unlike max_age (auth_time is an OIDC-only concept), prompt's
        // interaction flags need no OIDC claims — and Passport applies
        // its native prompt handling for pure OAuth too. Validation is
        // therefore UNCONDITIONAL: a malformed prompt is rejected on a
        // non-openid request exactly as on an openid one (never a
        // per-scope blind spot).
        $res = $this->actingAs($user)->get(self::HOST.'/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, [
                'scope' => '',
                'nonce' => '',
                'prompt' => 'none login',
            ])
        ));

        $res->assertStatus(302);
        $query = $this->redirectQuery($res);
        $this->assertSame('invalid_request', $query['error']);
        $this->assertSame('xyz', $query['state']);
        $this->assertSame(IssuerIdentifier::value(), $query['iss']);
    }

    public function test_empty_prompt_is_treated_as_absent(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        $this->actingAs($user)->get(self::HOST.'/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, ['prompt' => ''])
        ))->assertOk();
    }
}
