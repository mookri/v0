<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\Federation\Models\AuthEvent;
use App\Platform\Identity\OAuth\Jwk;
use App\Platform\Identity\OAuth\RsaKeyGenerator;
use FilesystemIterator;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\Platform\Identity\Concerns\IssuesUserTokens;
use Tests\TenantIntegrationTestCase;

/**
 * kid'd JWKS + signing-key rotation drill (ADR-0020 D3): every
 * published key carries an RFC 7638 thumbprint `kid`, the retired
 * public half overlaps the current one through
 * `federation.retired_signing_keys_dir`, rotation is attributable and
 * audited on the auth_events chain, and already-issued tokens keep
 * verifying across the rotation — the exact property a BFF fleet
 * depends on when the server rotates.
 */
final class SigningKeyRotationDrillTest extends TenantIntegrationTestCase
{
    use IssuesUserTokens;

    private string $keyDir;

    private ?string $originalKeyPath = null;

    private bool $originalKeyPermissions = true;

    protected function setUp(): void
    {
        parent::setUp();

        // File-mode keys in a sandbox directory: the drill must never
        // touch the real storage/oauth-*.key pair (the live drill env
        // signs with it).
        $this->originalKeyPath = Passport::$keyPath;
        $this->originalKeyPermissions = Passport::$validateKeyPermissions;
        Passport::$validateKeyPermissions = false;

        $this->keyDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nv-key-drill-'.bin2hex(random_bytes(6));
        mkdir($this->keyDir, 0755, true);
        Passport::$keyPath = $this->keyDir;

        [$private, $public] = $this->generateRsa();
        file_put_contents($this->keyDir.DIRECTORY_SEPARATOR.'oauth-private.key', $private);
        file_put_contents($this->keyDir.DIRECTORY_SEPARATOR.'oauth-public.key', $public);

        config([
            'passport.private_key' => null,
            'passport.public_key' => null,
            'federation.retired_signing_keys_dir' => $this->keyDir.DIRECTORY_SEPARATOR.'retired',
        ]);
    }

    protected function tearDown(): void
    {
        Passport::$keyPath = $this->originalKeyPath;
        Passport::$validateKeyPermissions = $this->originalKeyPermissions;
        self::deleteDirectory($this->keyDir);

        parent::tearDown();
    }

    public function test_jwks_publishes_a_stable_rfc7638_kid_for_the_current_key(): void
    {
        $publicPem = (string) file_get_contents($this->keyDir.DIRECTORY_SEPARATOR.'oauth-public.key');

        $keys = $this->getJson('/.well-known/jwks.json')->assertOk()->json('keys');
        $this->assertCount(1, $keys);
        $kid = $keys[0]['kid'];
        $this->assertSame(Jwk::thumbprintFromPem($publicPem), $kid);

        // RFC 7638 self-check straight from the published n/e: the kid
        // IS the canonical thumbprint, not an opaque label.
        $canonical = json_encode(['e' => $keys[0]['e'], 'kty' => 'RSA', 'n' => $keys[0]['n']], JSON_UNESCAPED_SLASHES);
        $expected = $this->base64Url(hash('sha256', (string) $canonical, true));
        $this->assertSame($expected, $kid);

        // Deterministic across requests (consumer caches stay valid).
        $this->assertSame($kid, $this->getJson('/.well-known/jwks.json')->json('keys.0.kid'));
    }

    public function test_rotation_drill_overlaps_keys_and_audits_both_halves(): void
    {
        // A real token signed by the CURRENT key (the "already issued"
        // half of the drill).
        [$client, $tokens] = $this->userTokensFor(User::factory()->create());
        $oldKid = Jwk::thumbprintFromPem((string) file_get_contents($this->keyDir.DIRECTORY_SEPARATOR.'oauth-public.key'));
        $this->assertNotNull($oldKid);

        $this->artisan('auth:rotate-signing-key')->assertSuccessful();

        $newPem = (string) file_get_contents($this->keyDir.DIRECTORY_SEPARATOR.'oauth-public.key');
        $newKid = Jwk::thumbprintFromPem($newPem);
        $this->assertNotNull($newKid);
        $this->assertNotSame($oldKid, $newKid);

        // The old public half was archived for the overlap window (and
        // the old private half was destroyed, not archived).
        $archivedPath = $this->keyDir.DIRECTORY_SEPARATOR.'retired'.DIRECTORY_SEPARATOR.$oldKid.'.pub';
        $this->assertFileExists($archivedPath);
        $this->assertFileDoesNotExist($this->keyDir.DIRECTORY_SEPARATOR.'retired'.DIRECTORY_SEPARATOR.$oldKid.'.priv');

        // JWKS dual-publishes: current first, retired after.
        $keys = $this->getJson('/.well-known/jwks.json')->assertOk()->json('keys');
        $this->assertSame([$newKid, $oldKid], array_column($keys, 'kid'));
        $this->assertSame($oldKid, Jwk::thumbprintFromPem((string) file_get_contents($archivedPath)));

        // Consumer view: the pre-rotation token still verifies against
        // the retired key's source PEM, and NOT against the new one —
        // the two keys are cryptographically distinct.
        $oldPem = (string) file_get_contents($archivedPath);
        $this->assertTrue($this->verifies($tokens['access_token'], $oldPem));
        $this->assertFalse($this->verifies($tokens['access_token'], $newPem));

        // Fresh process semantics (keys are read per process): a real
        // worker restarted after rotation resolves BOTH of these cold —
        // the simulation must too, or it isn't a restart:
        //  1. the container's AuthorizationServer singleton (rebuilt on
        //     the rotated key files), and
        //  2. the token route's controller instance — Route caches it
        //     after first dispatch (with the AuthorizationServer it
        //     captured at construction, pre-rotation), so keeping it
        //     would keep signing through the retired key.
        $this->app->forgetInstance(AuthorizationServer::class);
        $tokenRoute = \Illuminate\Support\Facades\Route::getRoutes()->getByName('passport.token');
        $this->assertNotNull($tokenRoute, 'token route registered');
        $tokenRoute->controller = null;

        $refreshed = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
            'refresh_token' => $tokens['refresh_token'],
        ])->assertOk()->json();

        // The rebuilt (fresh-process) server signs ONLY with the new
        // key: the refreshed token verifies against the current public
        // half and no longer against the retired one — rotation took
        // effect for new issuances, while pre-rotation tokens above
        // still verify inside the overlap window.
        $this->assertTrue($this->verifies($refreshed['access_token'], $newPem), 'refreshed verifies under NEW key');
        $this->assertFalse($this->verifies($refreshed['access_token'], $oldPem), 'refreshed no longer verifies under OLD key');

        // Rotation is attributable (D3/D5): retired + issued on the
        // hash-chained audit chain, chain still intact.
        $this->assertNotNull(AuthEvent::query()
            ->where('event_type', 'signing_key.retired')
            ->where('decision', 'allow')
            ->where('signing_key_id', $oldKid)
            ->first());
        $this->assertNotNull(AuthEvent::query()
            ->where('event_type', 'signing_key.issued')
            ->where('decision', 'allow')
            ->where('signing_key_id', $newKid)
            ->first());
        $this->artisan('auth:verify-chain')->assertExitCode(0);
    }

    public function test_rotation_refuses_config_managed_keys(): void
    {
        config(['passport.private_key' => 'env-managed-private-pem']);

        $this->artisan('auth:rotate-signing-key')->assertFailed();
    }

    public function test_rotation_requires_a_retired_keys_directory(): void
    {
        config(['federation.retired_signing_keys_dir' => '']);

        $this->artisan('auth:rotate-signing-key')->assertFailed();
    }

    public function test_jwks_skips_unreadable_retired_files(): void
    {
        $retiredDir = $this->keyDir.DIRECTORY_SEPARATOR.'retired';
        mkdir($retiredDir, 0755, true);
        file_put_contents($retiredDir.DIRECTORY_SEPARATOR.'garbage.pub', 'not a pem');

        $keys = $this->getJson('/.well-known/jwks.json')->assertOk()->json('keys');

        $this->assertCount(1, $keys);
        $this->assertSame(
            Jwk::thumbprintFromPem((string) file_get_contents($this->keyDir.DIRECTORY_SEPARATOR.'oauth-public.key')),
            $keys[0]['kid'],
        );
    }

    /**
     * Consumer-side signature check against a PEM public key (league
     * mints kid-less token headers, so consumers try the key set).
     */
    private function verifies(string $jwt, string $publicPem): bool
    {
        $configuration = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText('unused'),
            InMemory::plainText($publicPem),
        );

        try {
            $token = $configuration->parser()->parse($jwt);

            // NB: validate() RETURNS a verdict (unlike assert(), which
            // throws) — ignoring the bool would accept everything.
            return $configuration->validator()->validate(
                $token,
                new SignedWith(new Sha256(), InMemory::plainText($publicPem)),
            );
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array{0: string, 1: string} private PEM, public PEM
     */
    private function generateRsa(): array
    {
        $pair = RsaKeyGenerator::generate(2048);
        $this->assertNotNull($pair);

        return [$pair['private'], $pair['public']];
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($dir);
    }
}
