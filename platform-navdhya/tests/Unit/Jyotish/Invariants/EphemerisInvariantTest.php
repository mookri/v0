<?php

namespace Tests\Unit\Jyotish\Invariants;

use App\Domain\Jyotish\Astronomy\AyanamsaEngine;
use App\Domain\Jyotish\Astronomy\SiderealLayer;
use App\Domain\Jyotish\Ephemeris\EphemerisSnapshot;
use App\Domain\Jyotish\Ephemeris\MeeusSeriesProvider;
use App\Domain\Jyotish\Ephemeris\NodesResolver;
use App\Domain\Jyotish\Support\Angle;
use PHPUnit\Framework\TestCase;

/**
 * Ephemeris and Sidereal Invariant Property Tests (T-JY02-01, T-JY02-04).
 * Enforces AC-T-JY02-01, AC-T-JY02-01b, AC-T-JY02-04.
 */
final class EphemerisInvariantTest extends TestCase
{
    private MeeusSeriesProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new MeeusSeriesProvider();
    }

    public function test_all_bodies_bounded_in_degrees_and_share_snapshot_jd(): void
    {
        // 50 dates spanning 1950 to 2050 CE
        for ($i = 0; $i < 50; $i++) {
            $jd = 2433282.5 + ($i * 730.0); // every ~2 years
            $snapshot = $this->provider->computeSnapshot($jd);

            $this->assertSame($jd, $snapshot->getJdTt());

            foreach (EphemerisSnapshot::GRAHAS as $graha) {
                $pos = $snapshot->getPlanet($graha);
                $this->assertGreaterThanOrEqual(0.0, $pos['longitude']);
                $this->assertLessThan(360.0, $pos['longitude']);
                $this->assertFinite($pos['speed_longitude']);
                $this->assertFinite($pos['distance']);
            }
        }
    }

    public function test_sidereal_layer_preserves_velocity_and_distance_invariants(): void
    {
        $jd = 2461120.0; // 2026-03-20
        $tropical = $this->provider->computeSnapshot($jd);

        $result = SiderealLayer::convert($tropical, AyanamsaEngine::SYSTEM_LAHIRI);
        $sidereal = $result['snapshot'];
        $ayanamsa = $result['ayanamsa'];

        $this->assertGreaterThan(23.0, $ayanamsa['ayanamsa_degrees']);
        $this->assertLessThan(25.0, $ayanamsa['ayanamsa_degrees']);

        foreach (EphemerisSnapshot::GRAHAS as $graha) {
            $tropPos = $tropical->getPlanet($graha);
            $sidPos = $sidereal->getPlanet($graha);

            // Sidereal = Tropical - Ayanamsa mod 360
            $expectedSidLon = Angle::normalize($tropPos['longitude'] - $ayanamsa['ayanamsa_degrees']);
            $this->assertEqualsWithDelta($expectedSidLon, $sidPos['longitude'], 1e-9);

            // Speed and distance must be identically preserved
            $this->assertSame($tropPos['speed_longitude'], $sidPos['speed_longitude']);
            $this->assertSame($tropPos['speed_latitude'], $sidPos['speed_latitude']);
            $this->assertSame($tropPos['distance'], $sidPos['distance']);
        }
    }

    public function test_ayanamsa_switching_produces_distinct_sidereal_coordinates(): void
    {
        $jd = 2461120.0;
        $tropical = $this->provider->computeSnapshot($jd);

        $lahiri = SiderealLayer::convert($tropical, AyanamsaEngine::SYSTEM_LAHIRI);
        $raman = SiderealLayer::convert($tropical, AyanamsaEngine::SYSTEM_RAMAN);
        $kp = SiderealLayer::convert($tropical, AyanamsaEngine::SYSTEM_KRISHNAMURTI);

        $sunLahiri = $lahiri['snapshot']->getPlanet('sun')['longitude'];
        $sunRaman = $raman['snapshot']->getPlanet('sun')['longitude'];
        $sunKp = $kp['snapshot']->getPlanet('sun')['longitude'];

        // Lahiri and Raman differ by ~1.4 degrees; Lahiri and KP differ by ~0.1 degrees
        $this->assertNotEqualsWithDelta($sunLahiri, $sunRaman, 0.5);
        $this->assertNotEqualsWithDelta($sunLahiri, $sunKp, 0.05);
    }
}

