<?php

namespace App\Platform\Identity\OAuth;

use Defuse\Crypto\Key;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * D4 replay response: returns the recorded body byte-for-byte — the
 * original exchange's access + refresh material, already rotated,
 * never re-minted — and marks the response `Idempotent-Replay` so
 * capture middleware can tell a recovery from a fresh issuance (a
 * replay must never re-record or extend the retry window).
 */
final class ReplayResponseType implements ResponseTypeInterface
{
    public function __construct(private readonly string $body)
    {
    }

    public function setAccessToken(AccessTokenEntityInterface $accessToken): void
    {
        // Not used: a replay never mints tokens.
    }

    public function setRefreshToken(RefreshTokenEntityInterface $refreshToken): void
    {
        // Not used: a replay never mints tokens.
    }

    public function setEncryptionKey(Key|string|null $key = null): void
    {
        // Not used: the body is stored, not generated.
    }

    public function generateHttpResponse(ResponseInterface $response): ResponseInterface
    {
        $response = $response
            ->withStatus(200)
            ->withHeader('pragma', 'no-cache')
            ->withHeader('cache-control', 'no-store')
            ->withHeader('content-type', 'application/json; charset=UTF-8')
            ->withHeader('Idempotent-Replay', 'true');

        $response->getBody()->write($this->body);

        return $response;
    }
}
