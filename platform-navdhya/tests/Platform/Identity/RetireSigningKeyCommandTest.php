<?php

namespace Tests\Platform\Identity;

use App\Platform\Identity\Federation\Models\AuthEvent;
use App\Platform\Identity\OAuth\Jwk;
use App\Platform\Identity\OAuth\RsaKeyGenerator;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TenantIntegrationTestCase;

/**
 * auth:retire-signing-key (ADR-0020 D3, custody extension): the
 * custody-agnostic overlap-window archiver for key swaps that happen
 * OUTSIDE this repository (KMS key versions, deployment-managed PEMs).
 * Archives the CURRENT public half to <kid>.pub before the swap,
 * records an attributable signing_key.retired audit row, is idempotent
 * on re-run, and fails closed before touching anything when the
 * retired dir or the current public half is unusable.
 */
final class RetireSigningKeyCommandTest extends TenantIntegrationTestCase
{
    private string $retiredDir;

    private string $publicPem;

    protected function setUp(): void
    {
        parent::setUp();

        $pair = RsaKeyGenerator::generate(2048);
        $this->assertNotNull($pair);
        $this->publicPem = $pair['public'];

        $this->retiredDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nv-retire-'.bin2hex(random_bytes(6));

        config([
            'identity.hmac_secret' => 'test-hmac-secret-for-federation-32-chars!!',
            'passport.public_key' => $this->publicPem,
            'passport.private_key' => $this->publicPem,
            'federation.retired_signing_keys_dir' => $this->retiredDir,
        ]);
    }

    protected function tearDown(): void
    {
        self::deleteDirectory($this->retiredDir);

        parent::tearDown();
    }

    public function test_archives_the_current_public_half_and_audits_the_retirement(): void
    {
        $this->artisan('auth:retire-signing-key')->assertExitCode(0);

        $kid = Jwk::thumbprintFromPem($this->publicPem);
        $this->assertNotNull($kid);

        $archive = $this->retiredDir.DIRECTORY_SEPARATOR.$kid.'.pub';
        $this->assertFileExists($archive);
        $this->assertSame($this->publicPem, file_get_contents($archive));

        $event = AuthEvent::withoutGlobalScopes()
            ->where('event_type', 'signing_key.retired')
            ->orderByDesc('id')
            ->first();
        $this->assertNotNull($event);
        $this->assertSame('allow', $event->decision);
        $this->assertSame($kid, $event->signing_key_id);
    }

    public function test_is_idempotent_and_audits_once(): void
    {
        $this->artisan('auth:retire-signing-key')->assertExitCode(0);
        $this->artisan('auth:retire-signing-key')->assertExitCode(0);

        $kid = Jwk::thumbprintFromPem($this->publicPem);
        $this->assertFileExists($this->retiredDir.DIRECTORY_SEPARATOR.$kid.'.pub');

        $this->assertSame(1, AuthEvent::withoutGlobalScopes()
            ->where('event_type', 'signing_key.retired')
            ->where('signing_key_id', $kid)
            ->count());
    }

    public function test_refuses_without_a_retired_keys_dir(): void
    {
        config(['federation.retired_signing_keys_dir' => '']);

        $this->artisan('auth:retire-signing-key')
            ->expectsOutputToContain('retired_signing_keys_dir is required')
            ->assertExitCode(1);
    }

    public function test_refuses_when_the_current_public_half_is_unresolvable(): void
    {
        // An unknown custody fails closed at resolution — the command
        // aborts before writing anything, archive or audit.
        config(['federation.signing.custody' => 'yubikey']);

        $this->artisan('auth:retire-signing-key')
            ->expectsOutputToContain('Current signing public half is unreadable')
            ->assertExitCode(1);

        $this->assertSame([], glob($this->retiredDir.DIRECTORY_SEPARATOR.'*.pub') ?: []);
        $this->assertSame(0, AuthEvent::withoutGlobalScopes()
            ->where('event_type', 'signing_key.retired')
            ->count());
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
