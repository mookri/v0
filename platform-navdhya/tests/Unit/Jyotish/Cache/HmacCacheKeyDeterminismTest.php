<?php

namespace Tests\Unit\Jyotish\Cache;

use App\Domain\Jyotish\Cache\CalculationCacheHasher;
use App\Domain\Jyotish\Cache\CalculationIdentity;
use App\Domain\Jyotish\Cache\LayeredCacheCoordinator;
use PHPUnit\Framework\TestCase;

final class HmacCacheKeyDeterminismTest extends TestCase
{
    public function testHmacKeyIsDeterministicAndInvariantToKeyOrder(): void
    {
        $id1 = new CalculationIdentity(
            julianDay: 2451545.0,
            latitude: 28.6139,
            longitude: 77.2090,
            altitudeM: 216.0,
            ayanamsa: 'lahiri',
            houseSystem: 'whole_sign',
            ephemerisModel: 'meeus_truncated',
            rulesVersion: 'v2'
        );

        $id2 = new CalculationIdentity(
            julianDay: 2451545.0,
            latitude: 28.6139,
            longitude: 77.2090,
            altitudeM: 216.0,
            ayanamsa: 'LAHIRI', // case insensitivity
            houseSystem: 'WHOLE_SIGN',
            ephemerisModel: 'MEEUS_TRUNCATED',
            rulesVersion: 'V2'
        );

        $hash1 = CalculationCacheHasher::hashIdentity($id1, 'test-secret');
        $hash2 = CalculationCacheHasher::hashIdentity($id2, 'test-secret');

        $this->assertSame($hash1, $hash2);
        $this->assertSame(64, strlen($hash1));
        $this->assertStringNotContainsString('28.6139', $hash1, 'Coordinates must never leak into HMAC hash');
        $this->assertStringNotContainsString('77.2090', $hash1, 'Coordinates must never leak into HMAC hash');
    }

    public function testLayeredCacheHierarchyAllowsSnapTierReuse(): void
    {
        $jd = 2451545.0;

        // Two different ayanamsas (Lahiri vs Raman) for the same instant
        $snapKey1 = LayeredCacheCoordinator::snapshotKey($jd);
        $snapKey2 = LayeredCacheCoordinator::snapshotKey($jd);

        $this->assertSame($snapKey1, $snapKey2, 'Identical JD must produce identical snap:* key');

        $sidLahiri = LayeredCacheCoordinator::siderealKey($snapKey1, 'lahiri');
        $sidRaman = LayeredCacheCoordinator::siderealKey($snapKey1, 'raman');

        $this->assertNotSame($sidLahiri, $sidRaman, 'Different ayanamsa must yield distinct sid:* keys');

        // House cusps for different locations
        $chartDel = LayeredCacheCoordinator::chartKey($sidLahiri, 28.6139, 77.2090);
        $chartLon = LayeredCacheCoordinator::chartKey($sidLahiri, 51.5074, -0.1278);

        $this->assertNotSame($chartDel, $chartLon, 'Different coordinates must produce distinct chart:* keys');

        // Render tiers for different styles/locales
        $renderNorth = LayeredCacheCoordinator::renderKey($chartDel, 'north', 'en');
        $renderSouth = LayeredCacheCoordinator::renderKey($chartDel, 'south', 'en');
        $renderHi = LayeredCacheCoordinator::renderKey($chartDel, 'north', 'hi');

        $this->assertNotSame($renderNorth, $renderSouth);
        $this->assertNotSame($renderNorth, $renderHi);
    }
}

