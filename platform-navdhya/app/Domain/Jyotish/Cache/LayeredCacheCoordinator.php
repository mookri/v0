<?php

namespace App\Domain\Jyotish\Cache;

/**
 * Layered Cache Coordinator (T-JYOPT-02 / JY-OPT-02).
 *
 * Enforces 4-tier layered key partitioning:
 * 1. snap:*   - Raw tropical/geocentric astronomical positions (immortal; JD + ephemeris model only).
 * 2. sid:*    - Sidereal projection (snap hash + ayanamsa code).
 * 3. chart:*  - House cusps, Bhava Chalit, Lagna (sid hash + lat/lon + house system).
 * 4. render:* - Locale, theme, SVG output (chart hash + locale + chart style).
 *
 * Switching Ayanamsa or chart style reuses earlier tiers without re-running ephemeris.
 */
final class LayeredCacheCoordinator
{
    public static function snapshotKey(float $julianDay, string $ephemerisModel = 'meeus_truncated'): string
    {
        return 'snap:' . CalculationCacheHasher::hashEphemerisSnapshot($julianDay, $ephemerisModel);
    }

    public static function siderealKey(string $snapshotKey, string $ayanamsa): string
    {
        return 'sid:' . hash('sha256', $snapshotKey . '|ayanamsa:' . strtolower($ayanamsa));
    }

    public static function chartKey(
        string $siderealKey,
        float $latitude,
        float $longitude,
        string $houseSystem = 'whole_sign'
    ): string {
        $locStr = sprintf('lat:%.6f|lon:%.6f|house:%s', round($latitude, 6), round($longitude, 6), strtolower($houseSystem));
        return 'chart:' . hash('sha256', $siderealKey . '|' . $locStr);
    }

    public static function renderKey(
        string $chartKey,
        string $chartStyle = 'north',
        string $locale = 'en',
        string $theme = 'sacred_ember'
    ): string {
        $renderStr = sprintf('style:%s|locale:%s|theme:%s', strtolower($chartStyle), strtolower($locale), strtolower($theme));
        return 'render:' . hash('sha256', $chartKey . '|' . $renderStr);
    }
}

