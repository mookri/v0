<?php

namespace Tests\Platform\Identity\Fixtures;

use App\Platform\Identity\OAuth\Signing\SignsTokenRemotely;
use RuntimeException;

/**
 * In-process test double for the SignsTokenRemotely port: signs with a
 * local test key while recording exactly what the mint path handed it
 * — the remote custody's observable contract without any network.
 * Shared by RemoteSigningMintTest and KmsCustodyExchangeTest.
 */
final class RecordingRemoteSigner implements SignsTokenRemotely
{
    public ?string $lastInput = null;

    public function __construct(private readonly string $privatePem)
    {
    }

    public function sign(string $signingInput): string
    {
        $this->lastInput = $signingInput;

        if (! openssl_sign($signingInput, $signature, $this->privatePem, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('test fixture failed to sign');
        }

        return $signature;
    }
}
