<?php

namespace Tests\Platform\Identity;

use App\Platform\Identity\Federation\RuntimeRole;
use App\Platform\Identity\OAuth\CustodyAccessToken;
use App\Platform\Identity\OAuth\Signing\KmsHttpSigner;
use App\Platform\Identity\OAuth\Signing\RemoteSigningException;
use App\Platform\Identity\OAuth\Signing\RemoteSigningSigner;
use App\Platform\Identity\OAuth\Signing\SigningCustody;
use App\Platform\Identity\OAuth\Signing\SignsTokenRemotely;
use App\Platform\Identity\OAuth\RsaKeyGenerator;
use DateTimeImmutable;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Bridge\Client;
use Laravel\Passport\Bridge\Scope;
use Laravel\Passport\Passport;
use League\OAuth2\Server\CryptKey;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use RuntimeException;
use Tests\KernelTestCase;
use Tests\Platform\Identity\Fixtures\RecordingRemoteSigner;

/**
 * Remote signing mint path (ADR-0020 D3 custody, slice 2): under
 * AUTH_SIGNING_CUSTODY=kms every mint signature rides the
 * SignsTokenRemotely port — the lcobucci adapter delegates signing and
 * verifies locally, the KMS HTTP signer speaks the documented shim
 * contract and fails closed on every deviation, the access-token
 * entity mirrors league's claim layout byte-for-byte (only the signer
 * differs), Passport's key slots materialize from the custody (public
 * half for verification/JWKS, the same public PEM as a structural
 * sentinel in the private slot), and the issuer boots under kms only
 * once the signer port is bound. File custody stays byte-exact league
 * behavior, and the SEC-003 access-TTL ceiling is enforced at config
 * load.
 */
final class RemoteSigningMintTest extends KernelTestCase
{
    private string $privatePem;

    private string $publicPem;

    protected function setUp(): void
    {
        parent::setUp();

        $pair = RsaKeyGenerator::generate(2048);
        $this->assertNotNull($pair);
        $this->privatePem = $pair['private'];
        $this->publicPem = $pair['public'];

        config([
            'passport.private_key' => null,
            'passport.public_key' => null,
            'federation.signing.custody' => 'file',
            'federation.signing.kms.public_key' => '',
            'federation.signing.kms.key_resource' => '',
            'federation.signing.kms.endpoint' => '',
            'federation.signing.kms.auth_token' => '',
            'federation.signing.kms.timeout_seconds' => 3,
        ]);
    }

    public function test_file_custody_still_mints_with_stock_local_rs256(): void
    {
        $this->assertInstanceOf(Sha256::class, SigningCustody::resolve()->jwtSigner());
    }

    public function test_remote_signer_signs_through_the_port_and_verifies_locally(): void
    {
        $port = new RecordingRemoteSigner($this->privatePem);
        $signer = new RemoteSigningSigner($port);

        $this->assertSame('RS256', $signer->algorithmId());

        $signature = $signer->sign('header.payload', InMemory::plainText('the-key-argument-is-ignored'));

        // The port received the exact JWT signing input ...
        $this->assertSame('header.payload', $port->lastInput);

        // ... and the signature is the real RS256 over it, verifiable
        // with the custody public half — and tampering fails.
        $this->assertTrue($signer->verify($signature, 'header.payload', InMemory::plainText($this->publicPem)));
        $this->assertFalse($signer->verify($signature, 'header.payloadX', InMemory::plainText($this->publicPem)));
    }

    public function test_kms_http_signer_posts_the_documented_contract(): void
    {
        $wire = $this->rawRs256Signature('signing.input');

        Http::fake([
            'kms.internal/*' => Http::response(['signature' => self::b64url($wire)], 200),
        ]);

        $signer = new KmsHttpSigner('https://kms.internal/v1/sign', 'shim-token', 2);

        $this->assertSame($wire, $signer->sign('signing.input'));

        Http::assertSent(function (ClientRequest $request): bool {
            $payload = json_decode($request->body(), true);

            return $request->url() === 'https://kms.internal/v1/sign'
                && $request->hasHeader('Authorization', 'Bearer shim-token')
                && is_array($payload)
                && ($payload['algorithm'] ?? null) === 'RS256'
                && ($payload['signing_input'] ?? null) === self::b64url('signing.input');
        });
    }

    public function test_kms_http_signer_fails_closed_on_a_non_success_response(): void
    {
        Http::fake(['*' => Http::response('nope', 500)]);

        $signer = new KmsHttpSigner('https://kms.internal/v1/sign', '', 2);

        $this->expectException(RemoteSigningException::class);
        $this->expectExceptionMessage('HTTP 500');

        $signer->sign('x.y');
    }

    public function test_kms_http_signer_fails_closed_on_a_missing_signature_field(): void
    {
        Http::fake(['*' => Http::response(['error' => 'busy'], 200)]);

        $signer = new KmsHttpSigner('https://kms.internal/v1/sign', '', 2);

        $this->expectException(RemoteSigningException::class);
        $this->expectExceptionMessage('no usable signature field');

        $signer->sign('x.y');
    }

    public function test_kms_http_signer_fails_closed_on_a_malformed_signature(): void
    {
        Http::fake(['*' => Http::response(['signature' => '!!!not-base64!!!'], 200)]);

        $signer = new KmsHttpSigner('https://kms.internal/v1/sign', '', 2);

        $this->expectException(RemoteSigningException::class);
        $this->expectExceptionMessage('not valid base64url');

        $signer->sign('x.y');
    }

    public function test_issuer_boots_under_kms_custody_once_the_signer_port_is_bound(): void
    {
        config([
            'federation.signing.custody' => 'kms',
            'federation.signing.kms.public_key' => $this->publicPem,
            'federation.signing.kms.key_resource' => 'projects/navdhya/locations/asia-south1/keyRings/signing/cryptoKeys/issuer/cryptoKeyVersions/1',
            'federation.signing.kms.endpoint' => 'https://kms.internal.example/v1/sign',
            'federation.runtime' => 'authorization_server',
            'federation.mode' => 'federated',
        ]);

        app()->singleton(
            SignsTokenRemotely::class,
            fn (): SignsTokenRemotely => new RecordingRemoteSigner($this->privatePem)
        );

        // The flip: config-valid kms + bound port = a bootable issuer
        // (slice 1 refused unconditionally; the refusal now keys on the
        // missing port — see SigningCustodyTest).
        $this->assertSame('authorization_server', RuntimeRole::current());

        // The custody-aware entity is registered for every deployment
        // (file custody delegates to league's stock mint) ...
        $this->assertSame(CustodyAccessToken::class, Passport::$accessTokenEntity);

        // ... and Passport's key slots are materialized from the
        // custody: verification + JWKS read the KMS public half; the
        // private slot holds the SAME public PEM as a structural
        // sentinel.
        SigningCustody::materializePassportConfig();
        $this->assertSame($this->publicPem, config('passport.public_key'));
        $this->assertSame($this->publicPem, config('passport.private_key'));

        // The sentinel passes league's CryptKey form check (a public
        // PEM is structurally valid), so the AuthorizationServer still
        // constructs — while openssl_sign would reject it, meaning any
        // un-intercepted signing path fails loudly instead of minting
        // under a key consumers don't have.
        $sentinel = new CryptKey((string) config('passport.private_key'), null, false);
        $this->assertSame($this->publicPem, $sentinel->getKeyContents());

        // Minting routes through the port-backed signer.
        $this->assertInstanceOf(RemoteSigningSigner::class, SigningCustody::resolve()->jwtSigner());
    }

    public function test_access_token_mint_is_claim_identical_across_custodies(): void
    {
        $client = new Client('client-1', 'drill', ['http://localhost/callback']);
        $scopes = [new Scope('profile')];

        $newEntity = function () use ($client, $scopes): CustodyAccessToken {
            $entity = new CustodyAccessToken('8', $scopes, $client);
            $entity->setIdentifier('jti-fixed-for-parity');
            $entity->setExpiryDateTime(new DateTimeImmutable('@1790000000'));

            return $entity;
        };

        // File custody: league's stock mint over the local private key.
        config(['passport.private_key' => $this->privatePem]);
        $file = $newEntity();
        $file->setPrivateKey(new CryptKey($this->privatePem, null, false));
        $fileToken = $file->toString();

        // Remote custody: the port (holding the SAME private half in
        // this test) signs the mirrored claim layout; the entity's key
        // argument is the materialized sentinel.
        config([
            'federation.signing.custody' => 'kms',
            'federation.signing.kms.public_key' => $this->publicPem,
            'federation.signing.kms.key_resource' => 'resource',
            'federation.signing.kms.endpoint' => 'https://kms.internal.example/v1/sign',
        ]);
        app()->singleton(
            SignsTokenRemotely::class,
            fn (): SignsTokenRemotely => new RecordingRemoteSigner($this->privatePem)
        );
        $remote = $newEntity();
        $remote->setPrivateKey(new CryptKey($this->publicPem, null, false));
        $remoteToken = $remote->toString();

        // Header parity: identical alg/typ, kid-less on both sides —
        // league's exact format, only the signature differs.
        $this->assertSame(self::jwtPart($fileToken, 0), self::jwtPart($remoteToken, 0));

        // Claim parity: every claim league pins is byte-equal in the
        // same key order; only the build-time stamps (iat/nbf = now())
        // may differ across the two constructions.
        $fileClaims = self::jwtPart($fileToken, 1);
        $remoteClaims = self::jwtPart($remoteToken, 1);
        $this->assertSame(array_keys($fileClaims), array_keys($remoteClaims));

        foreach (['aud', 'jti', 'exp', 'sub', 'scopes'] as $claim) {
            $this->assertSame($fileClaims[$claim], $remoteClaims[$claim], "claim {$claim}");
        }

        $this->assertLessThanOrEqual(2, abs($fileClaims['iat'] - $remoteClaims['iat']));
        $this->assertLessThanOrEqual(2, abs($fileClaims['nbf'] - $remoteClaims['nbf']));

        // Session-stamped parity (A-SEC-004-6): the same `sid` rides
        // both custodies in the same final position — the stamp never
        // forks the claim layout between local and remote signing.
        config([
            'federation.signing.custody' => 'file',
            'passport.private_key' => $this->privatePem,
        ]);
        $stampedFile = $newEntity();
        $stampedFile->withSessionId('sid-parity-1');
        $stampedFile->setPrivateKey(new CryptKey($this->privatePem, null, false));
        $stampedFileClaims = self::jwtPart($stampedFile->toString(), 1);

        $stampedRemote = $newEntity();
        $stampedRemote->withSessionId('sid-parity-1');
        $stampedRemote->setPrivateKey(new CryptKey($this->publicPem, null, false));
        $stampedRemoteClaims = self::jwtPart($stampedRemote->toString(), 1);

        $this->assertSame(
            ['aud', 'jti', 'iat', 'nbf', 'exp', 'sub', 'scopes', 'sid'],
            array_keys($stampedFileClaims),
        );
        $this->assertSame(array_keys($stampedFileClaims), array_keys($stampedRemoteClaims));
        $this->assertSame('sid-parity-1', $stampedFileClaims['sid']);
        $this->assertSame('sid-parity-1', $stampedRemoteClaims['sid']);

        // The remote-custody token's signature verifies against the
        // custody's public half — the exact key JWKS publishes.
        $parts = explode('.', $remoteToken);
        $verified = (new Sha256())->verify(
            self::b64urlDecode($parts[2]),
            $parts[0].'.'.$parts[1],
            InMemory::plainText($this->publicPem),
        );
        $this->assertTrue($verified);
    }

    public function test_access_ttl_is_capped_at_the_sec003_ceiling(): void
    {
        // config/federation.php clamps AUTH_ACCESS_TOKEN_TTL to
        // SEC-003's 900 s maximum so the probe's advertised value is
        // always the value Passport mints (shorter values, e.g. 90 s,
        // pass through).
        $this->assertLessThanOrEqual(900, (int) config('federation.access_token_ttl_seconds'));
    }

    public function test_remote_signer_failures_propagate_even_when_the_audit_write_fails(): void
    {
        // No database in this harness: the D5 audit write inside the
        // failure path throws too — it must never mask the signing
        // error the caller needs.
        $signer = new RemoteSigningSigner(new class implements SignsTokenRemotely
        {
            public function sign(string $signingInput): string
            {
                throw new RemoteSigningException('shim down');
            }
        });

        $this->expectException(RemoteSigningException::class);
        $this->expectExceptionMessage('shim down');

        $signer->sign('h.p', InMemory::plainText('ignored'));
    }

    private function rawRs256Signature(string $payload): string
    {
        if (! openssl_sign($payload, $signature, $this->privatePem, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('test fixture failed to sign');
        }

        return $signature;
    }

    /**
     * Decoded JWT header (index 0) or claims (index 1) array.
     */
    private static function jwtPart(string $jwt, int $index): array
    {
        $decoded = json_decode(self::b64urlDecode(explode('.', $jwt)[$index]), true, flags: JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    private static function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function b64urlDecode(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'), true);
    }
}
