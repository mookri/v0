<?php

namespace App\Domain\Jyotish\Cache;

/**
 * Content-addressed HMAC calculation cache key hasher (T-JYOPT-01 / JY-OPT-01).
 *
 * Computes canonical HMAC-SHA256 digests over sorted calculation identity tuples.
 * Prevents geographic coordinates from leaking into plaintext cache key spaces.
 */
final class CalculationCacheHasher
{
    private const DEFAULT_SECRET = 'navdhya-jyotish-cache-hmac-v1';

    public static function hashIdentity(CalculationIdentity $identity, ?string $secret = null): string
    {
        $normalized = $identity->toNormalizedArray();
        ksort($normalized);

        $payload = json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $key = $secret ?? (getenv('APP_KEY') ?: self::DEFAULT_SECRET);

        return hash_hmac('sha256', $payload, $key);
    }

    /**
     * Ephemeris snapshot hash depends ONLY on time and ephemeris model (immortal, independent of location/ayanamsa).
     */
    public static function hashEphemerisSnapshot(float $julianDay, string $ephemerisModel = 'meeus_truncated'): string
    {
        $payload = sprintf('jd:%.7f|model:%s', round($julianDay, 7), strtolower($ephemerisModel));
        return hash('sha256', $payload);
    }
}

