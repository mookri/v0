<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\Federation\Models\AuthEvent;
use App\Platform\Identity\OAuth\IssuerIdentifier;
use App\Platform\Identity\OAuth\Jwk;
use App\Platform\Identity\OAuth\RsaKeyGenerator;
use App\Platform\Identity\OAuth\Signing\RemoteSigningException;
use App\Platform\Identity\OAuth\Signing\SigningCustody;
use App\Platform\Identity\OAuth\Signing\SignsTokenRemotely;
use Laravel\Passport\Client;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Tests\Platform\Identity\Concerns\IssuesUserTokens;
use Tests\Platform\Identity\Fixtures\RecordingRemoteSigner;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * KMS custody end-to-end (ADR-0020 D3 custody, slice 3): a REAL
 * PKCE authorize + consent + code exchange with the custody switched
 * to kms mid-process — both minted tokens (ID token from the grant,
 * access token from the custody entity) are signed through the
 * SignsTokenRemotely port and verify against the custody's public
 * half, the exact key JWKS publishes; kid appears on the ID token and
 * the access token keeps league's kid-less header. A dead signer fails
 * the exchange closed (500, no tokens) with a hash-chained
 * token.signing_failed audit row.
 */
final class KmsCustodyExchangeTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;
    use IssuesUserTokens;

    private const REDIRECT = 'https://example.test/callback';

    private string $privatePem;

    private string $publicPem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeMsg91();

        $pair = RsaKeyGenerator::generate(2048);
        $this->assertNotNull($pair);
        $this->privatePem = $pair['private'];
        $this->publicPem = $pair['public'];

        config([
            'federation.domain' => 'auth.localhost',
            'federation.issuer' => 'http://auth.localhost',
            'federation.mode' => 'federated',
            'identity.hmac_secret' => 'test-hmac-secret-for-federation-32-chars!!',
            'passport.private_key' => null,
            'passport.public_key' => null,
            'federation.signing.custody' => 'kms',
            'federation.signing.kms.public_key' => $this->publicPem,
            'federation.signing.kms.key_resource' => 'projects/navdhya/locations/asia-south1/keyRings/signing/cryptoKeys/issuer/cryptoKeyVersions/1',
            'federation.signing.kms.endpoint' => 'https://kms.internal.example/v1/sign',
        ]);

        app()->singleton(
            SignsTokenRemotely::class,
            fn (): SignsTokenRemotely => new RecordingRemoteSigner($this->privatePem)
        );
        SigningCustody::materializePassportConfig();
    }

    public function test_full_exchange_mints_both_tokens_under_the_kms_custody(): void
    {
        $user = User::factory()->create();
        $client = $this->oauthWebClient('KMS web client', ['allowed_scopes' => ['openid']]);

        [, $tokens] = $this->userTokensFor($user, $client, [], [
            'scope' => 'openid',
            'nonce' => 'kms-nonce-1',
        ]);

        // ID token: kid = the custody public half's thumbprint, signed
        // through the port, verifiable with the published key.
        $this->assertArrayHasKey('id_token', $tokens);
        [$idHeader, $idClaims] = $this->decodeJwt((string) $tokens['id_token']);
        $this->assertSame(Jwk::thumbprintFromPem($this->publicPem), $idHeader['kid']);
        $this->assertSame('RS256', $idHeader['alg']);
        $this->assertSame(IssuerIdentifier::value(), $idClaims['iss']);
        $this->assertSame('kms-nonce-1', $idClaims['nonce']);
        $this->assertTrue($this->verifiesWithPublishedKey((string) $tokens['id_token']));

        // Access token: league's kid-less format, claims mirror, signed
        // through the SAME port — verifiable with the custody public
        // half too (never the missing local file key).
        [$atHeader, $atClaims] = $this->decodeJwt((string) $tokens['access_token']);
        $this->assertArrayNotHasKey('kid', $atHeader);
        $this->assertSame('RS256', $atHeader['alg']);
        $this->assertSame((string) $client->getKey(), $atClaims['aud']);
        $this->assertTrue($this->verifiesWithPublishedKey((string) $tokens['access_token']));

        $this->assertArrayHasKey('refresh_token', $tokens);
    }

    public function test_a_dead_signer_fails_the_exchange_closed_and_is_audited(): void
    {
        // Rebind the port to a dead shim AFTER the custody validated.
        app()->singleton(
            SignsTokenRemotely::class,
            fn (): SignsTokenRemotely => new class implements SignsTokenRemotely
            {
                public function sign(string $signingInput): string
                {
                    throw new RemoteSigningException('shim down');
                }
            }
        );

        $user = User::factory()->create();
        $client = $this->oauthWebClient('KMS web client', ['allowed_scopes' => ['openid']]);
        $code = $this->mintCode($user, $client);

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
            'redirect_uri' => self::REDIRECT,
            'code' => $code,
            'code_verifier' => self::VERIFIER,
        ]);

        // Fail closed: a 5xx with NO token material, ever.
        $response->assertStatus(500);
        $this->assertArrayNotHasKey('access_token', $response->json());

        // And the failure is on the hash-chained audit chain.
        $this->assertNotNull(
            AuthEvent::withoutGlobalScopes()
                ->where('event_type', 'token.signing_failed')
                ->where('decision', 'error')
                ->first()
        );
    }

    /**
     * Authorize + consent-approve (default host, acting agent); returns
     * the issued code — the ceremony half of IssuesUserTokens, kept
     * here for the failure-path test that cannot use its asserting
     * exchange (mirrors OidcIdTokenTest's private mintCode).
     */
    private function mintCode(User $user, Client $client): string
    {
        $this->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'client_id' => (string) $client->getKey(),
                'redirect_uri' => self::REDIRECT,
                'response_type' => 'code',
                'scope' => 'openid',
                'state' => 'xyz',
                'code_challenge' => self::CHALLENGE,
                'code_challenge_method' => 'S256',
                'nonce' => 'kms-nonce-1',
            ]))
            ->assertOk();

        $approve = $this->post('/oauth/authorize', [
            '_token' => csrf_token(),
            'state' => 'xyz',
            'client_id' => (string) $client->getKey(),
            'auth_token' => session('authToken'),
        ]);
        $approve->assertStatus(302);
        parse_str((string) parse_url((string) $approve->headers->get('Location'), PHP_URL_QUERY), $query);

        return (string) $query['code'];
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function decodeJwt(string $jwt): array
    {
        $parts = explode('.', $jwt);

        return [
            json_decode($this->b64urlDecode($parts[0]), true, flags: JSON_THROW_ON_ERROR),
            json_decode($this->b64urlDecode($parts[1]), true, flags: JSON_THROW_ON_ERROR),
        ];
    }

    private function verifiesWithPublishedKey(string $jwt): bool
    {
        $parts = explode('.', $jwt);

        return (new Sha256())->verify(
            $this->b64urlDecode($parts[2]),
            $parts[0].'.'.$parts[1],
            InMemory::plainText($this->publicPem),
        );
    }

    private function b64urlDecode(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'), true);
    }
}
