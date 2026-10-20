<?php

namespace Tests\Support;

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;

/**
 * D4 atomic-rotation chaos injection (ADR-0020): fails the NEXT
 * refresh-token persist — the last write of league's rotate-then-issue
 * sequence — so a test can prove the rotation transaction rolls the
 * whole rotation back (old credential unconsumed, successor never
 * created) instead of leaving a consumed token with no successor. The
 * flag self-clears on failure so the same test can then prove the
 * presented token is still usable.
 */
final class FailOnceRefreshTokenRepository extends \Laravel\Passport\Bridge\RefreshTokenRepository
{
    public static bool $failNext = false;

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        if (static::$failNext) {
            static::$failNext = false;

            throw new \RuntimeException('simulated store outage during refresh rotation');
        }

        parent::persistNewRefreshToken($refreshTokenEntity);
    }
}
