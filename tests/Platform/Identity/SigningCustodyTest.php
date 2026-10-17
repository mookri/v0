<?php

namespace Tests\Platform\Identity;

use App\Platform\Identity\Federation\FederationConfigurationException;
use App\Platform\Identity\Federation\RuntimeRole;
use App\Platform\Identity\OAuth\RsaKeyGenerator;
use App\Platform\Identity\OAuth\Signing\FileSigningKeyProvider;
use App\Platform\Identity\OAuth\Signing\KmsSigningKeyProvider;
use App\Platform\Identity\OAuth\Signing\SigningCustody;
use FilesystemIterator;
use Laravel\Passport\Passport;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\KernelTestCase;

/**
 * Signing-key custody (ADR-0020 D3, custody extension): AUTH_SIGNING_CUSTODY
 * resolves the token-signing key source behind one port. The default file
 * custody keeps today's exact availability semantics; the KMS custody
 * validates its configuration at boot and still refuses the issuer boot
 * until the remote signer ships — an issuer must never publish one key in
 * JWKS and sign with another. Unknown custodies fail closed: custody is a
 * security boundary, not a default.
 */
final class SigningCustodyTest extends KernelTestCase
{
    private string $keyDir;

    private ?string $originalKeyPath = null;

    private bool $originalKeyPermissions = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Sandbox the Passport key path (like the rotation drill): the
        // real storage pair must stay invisible to these assertions.
        $this->originalKeyPath = Passport::$keyPath;
        $this->originalKeyPermissions = Passport::$validateKeyPermissions;
        Passport::$validateKeyPermissions = false;

        $this->keyDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nv-custody-'.bin2hex(random_bytes(6));
        mkdir($this->keyDir, 0755, true);
        Passport::$keyPath = $this->keyDir;

        config([
            'passport.private_key' => null,
            'passport.public_key' => null,
            'federation.signing.custody' => 'file',
            'federation.signing.kms.public_key' => '',
            'federation.signing.kms.key_resource' => '',
            'federation.signing.kms.endpoint' => '',
        ]);
    }

    protected function tearDown(): void
    {
        Passport::$keyPath = $this->originalKeyPath;
        Passport::$validateKeyPermissions = $this->originalKeyPermissions;
        self::deleteDirectory($this->keyDir);

        parent::tearDown();
    }

    public function test_default_custody_is_file(): void
    {
        $this->assertInstanceOf(FileSigningKeyProvider::class, SigningCustody::resolve());
    }

    public function test_file_custody_validates_and_publishes_the_key_file_pair(): void
    {
        $pair = RsaKeyGenerator::generate(2048);
        $this->assertNotNull($pair);
        file_put_contents($this->keyDir.DIRECTORY_SEPARATOR.'oauth-private.key', $pair['private']);
        file_put_contents($this->keyDir.DIRECTORY_SEPARATOR.'oauth-public.key', $pair['public']);

        config(['federation.runtime' => 'authorization_server', 'federation.mode' => 'federated']);

        // The issuer boots on file custody, and JWKS's source resolves
        // the same public half the private file signs with.
        $this->assertSame('authorization_server', RuntimeRole::current());
        $provider = SigningCustody::resolve();
        $provider->assertUsable();
        $this->assertSame($pair['public'], $provider->publicKeyPem());
    }

    public function test_file_custody_fails_when_nothing_is_available(): void
    {
        config(['federation.runtime' => 'authorization_server', 'federation.mode' => 'federated']);

        $this->expectException(FederationConfigurationException::class);
        $this->expectExceptionMessage('file signing custody requires');

        RuntimeRole::current();
    }

    public function test_unknown_custody_fails_closed(): void
    {
        config(['federation.signing.custody' => 'yubikey']);

        $this->expectException(FederationConfigurationException::class);
        $this->expectExceptionMessage("AUTH_SIGNING_CUSTODY must be 'file' or 'kms', got 'yubikey'");

        SigningCustody::resolve();
    }

    public function test_unknown_custody_refuses_the_issuer_boot(): void
    {
        config([
            'federation.signing.custody' => 'yubikey',
            'federation.runtime' => 'authorization_server',
            'federation.mode' => 'federated',
        ]);

        $this->expectException(FederationConfigurationException::class);

        RuntimeRole::current();
    }

    public function test_kms_custody_requires_its_configuration(): void
    {
        config([
            'federation.signing.custody' => 'kms',
            'federation.runtime' => 'authorization_server',
            'federation.mode' => 'federated',
        ]);

        $this->expectException(FederationConfigurationException::class);
        $this->expectExceptionMessage('AUTH_SIGNING_CUSTODY=kms requires federation.signing.kms.public_key');

        RuntimeRole::current();
    }

    public function test_kms_custody_rejects_an_unparsable_public_key(): void
    {
        config([
            'federation.signing.custody' => 'kms',
            'federation.signing.kms.public_key' => 'not a pem',
            'federation.signing.kms.key_resource' => 'projects/navdhya/locations/asia-south1/keyRings/signing/cryptoKeys/issuer/cryptoKeyVersions/1',
            'federation.signing.kms.endpoint' => 'https://kms.internal.example/v1/sign',
            'federation.runtime' => 'authorization_server',
            'federation.mode' => 'federated',
        ]);

        $this->expectException(FederationConfigurationException::class);
        $this->expectExceptionMessage('federation.signing.kms.public_key is not a parsable asymmetric public key');

        RuntimeRole::current();
    }

    public function test_kms_custody_refuses_the_issuer_boot_until_the_remote_signer_is_wired(): void
    {
        $pair = RsaKeyGenerator::generate(2048);
        $this->assertNotNull($pair);

        config([
            'federation.signing.custody' => 'kms',
            'federation.signing.kms.public_key' => $pair['public'],
            'federation.signing.kms.key_resource' => 'projects/navdhya/locations/asia-south1/keyRings/signing/cryptoKeys/issuer/cryptoKeyVersions/1',
            'federation.signing.kms.endpoint' => 'https://kms.internal.example/v1/sign',
            'federation.runtime' => 'authorization_server',
            'federation.mode' => 'federated',
        ]);

        // Config-complete + parsable key: the custody RESOLVES (the
        // JWKS-side accessor works) but the issuer boot still refuses —
        // never publish one key and sign with another.
        $provider = SigningCustody::resolve();
        $this->assertInstanceOf(KmsSigningKeyProvider::class, $provider);
        $this->assertSame($pair['public'], $provider->publicKeyPem());

        $this->expectException(FederationConfigurationException::class);
        $this->expectExceptionMessage('not wired to the token mint path yet');

        RuntimeRole::current();
    }

    public function test_non_issuer_runtimes_boot_regardless_of_custody(): void
    {
        // No key material anywhere: monolith (direct) and application
        // (federated) runtimes never needed signing custody — the
        // bool only gates the issuer. Regression pin for the delegation
        // added in RuntimeRole::current().
        config(['federation.runtime' => 'monolith', 'federation.mode' => 'direct']);
        $this->assertSame('monolith', RuntimeRole::current());

        config(['federation.runtime' => 'application', 'federation.mode' => 'federated',
            'federation.domain' => 'auth.localhost', 'federation.issuer' => 'http://auth.localhost']);
        $this->assertSame('application', RuntimeRole::current());
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
