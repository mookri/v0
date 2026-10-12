<?php

namespace Tests\Unit\Platform\Identity\Otp;

use App\Platform\Identity\Otp\CodeSource;
use App\Platform\Identity\Otp\LocalCodeProvider;
use App\Platform\Identity\Otp\OtpSendContext;
use App\Platform\Identity\Otp\OtpSendResult;
use App\Platform\Identity\Otp\OtpVerifyOutcome;
use App\Platform\Identity\Otp\OtpVerifyRequest;
use PHPUnit\Framework\TestCase;

/**
 * Verification Fabric S46: LocalCodeProvider is the extracted,
 * behavior-identical local path (generate → record → constant-time
 * hash compare). Anything asserted here is the exact semantics
 * CustomerOtpService had before the port existed.
 */
final class LocalCodeProviderTest extends TestCase
{
    public function test_name_is_local(): void
    {
        $provider = new LocalCodeProvider(new InMemoryCodeSource());

        $this->assertSame('local', $provider->name());
    }

    public function test_send_generates_records_and_returns_code_without_ref(): void
    {
        $source = new InMemoryCodeSource();
        $provider = new LocalCodeProvider($source);

        $result = $provider->send(new OtpSendContext(
            challengeId: 'chl-1',
            identifier: '+911234567890',
            purpose: 'login',
            codeLength: 6,
        ));

        $this->assertSame('000001', $result->code);
        $this->assertNull($result->providerRef);
        $this->assertFalse($result->isEmpty());
        // The drill seam (lastCodeFor) reads through the same record.
        $this->assertSame('000001', $source->lastFor('chl-1'));
    }

    public function test_verify_matches_only_the_exact_code(): void
    {
        $provider = new LocalCodeProvider(new InMemoryCodeSource());
        $hash = hash('sha256', '123456');

        $match = $provider->verify(new OtpVerifyRequest($hash, null, '123456'));
        $mismatch = $provider->verify(new OtpVerifyRequest($hash, null, '123457'));

        $this->assertSame(OtpVerifyOutcome::Match, $match);
        $this->assertSame(OtpVerifyOutcome::Mismatch, $mismatch);
    }

    public function test_verify_without_hash_fails_closed(): void
    {
        $provider = new LocalCodeProvider(new InMemoryCodeSource());

        $outcome = $provider->verify(new OtpVerifyRequest(null, null, '123456'));

        $this->assertSame(OtpVerifyOutcome::Mismatch, $outcome);
    }

    public function test_send_result_is_empty_only_when_both_sides_are_absent(): void
    {
        $this->assertTrue((new OtpSendResult())->isEmpty());
        $this->assertFalse((new OtpSendResult(code: '123456'))->isEmpty());
        $this->assertFalse((new OtpSendResult(providerRef: 'ref'))->isEmpty());
    }
}

/**
 * Minimal deterministic CodeSource (mirrors SeededOtpCodes semantics).
 */
final class InMemoryCodeSource implements CodeSource
{
    /** @var array<string, string> */
    private array $issued = [];

    private int $seq = 0;

    public function generate(int $length): string
    {
        $this->seq++;

        return str_pad((string) $this->seq, $length, '0', STR_PAD_LEFT);
    }

    public function record(string $challengeId, string $code): void
    {
        $this->issued[$challengeId] = $code;
    }

    public function lastFor(string $challengeId): ?string
    {
        return $this->issued[$challengeId] ?? null;
    }
}
