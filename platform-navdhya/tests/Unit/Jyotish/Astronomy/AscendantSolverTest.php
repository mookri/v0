<?php

namespace Tests\Unit\Jyotish\Astronomy;

use App\Domain\Jyotish\Astronomy\AscendantSolver;
use App\Domain\Jyotish\Astronomy\HorizonStatusEnum;
use PHPUnit\Framework\TestCase;

/**
 * Unit Tests for Ascendant (Lagna) & MC Solver (T-JY03-01).
 */
final class AscendantSolverTest extends TestCase
{
    public function test_equatorial_ascendant_geometry(): void
    {
        // J2000.0 epoch
        $jd = 2451545.0;
        // Observer at Equator (lat = 0)
        $res = AscendantSolver::solve($jd, $jd, 0.0, 0.0, 0.0);

        $this->assertSame(HorizonStatusEnum::NORMAL, $res['status']);
        $this->assertGreaterThanOrEqual(0.0, $res['tropical_ascendant']);
        $this->assertLessThan(360.0, $res['tropical_ascendant']);
        $this->assertNotEmpty($res['dms_ascendant']);
    }

    public function test_polar_latitude_triggers_unsupported_polar_status(): void
    {
        $jd = 2451545.0;

        // Lat 70° N (Tromsø / Arctic Circle)
        $res70 = AscendantSolver::solve($jd, $jd, 70.0, 18.95, 0.0);
        $this->assertSame(HorizonStatusEnum::UNSUPPORTED_POLAR, $res70['status']);

        // Lat -68° S (Antarctica)
        $resSouth = AscendantSolver::solve($jd, $jd, -68.0, 0.0, 0.0);
        $this->assertSame(HorizonStatusEnum::UNSUPPORTED_POLAR, $resSouth['status']);

        // Lat 28.6° N (New Delhi)
        $resDelhi = AscendantSolver::solve($jd, $jd, 28.6139, 77.2090, 0.0);
        $this->assertSame(HorizonStatusEnum::NORMAL, $resDelhi['status']);
    }

    public function test_ayanamsa_subtraction_is_exact(): void
    {
        $jd = 2451545.0;
        $ayanamsa = 23.853;

        $res = AscendantSolver::solve($jd, $jd, 23.1765, 75.7885, $ayanamsa); // Ujjain

        $expectedSiderealAsc = fmod($res['tropical_ascendant'] - $ayanamsa + 360.0, 360.0);
        $this->assertEqualsWithDelta($expectedSiderealAsc, $res['sidereal_ascendant'], 1e-9);

        $expectedSiderealMc = fmod($res['tropical_mc'] - $ayanamsa + 360.0, 360.0);
        $this->assertEqualsWithDelta($expectedSiderealMc, $res['sidereal_mc'], 1e-9);
    }

    public function test_continuous_lagna_movement_over_24_hours(): void
    {
        $baseJd = 2451545.0;
        $lat = 13.0827; // Chennai
        $lon = 80.2707;

        $previousLagna = null;
        $totalRotation = 0.0;

        // Sample every 30 minutes over 24 hours (48 steps)
        for ($step = 0; $step <= 48; $step++) {
            $jd = $baseJd + ($step * (0.5 / 24.0));
            $res = AscendantSolver::solve($jd, $jd, $lat, $lon, 23.85);
            $currentLagna = $res['sidereal_ascendant'];

            if ($previousLagna !== null) {
                $diff = $currentLagna - $previousLagna;
                if ($diff < -180.0) {
                    $diff += 360.0; // Crossed 360° -> 0° border
                }
                $this->assertGreaterThan(0.0, $diff, "Lagna must advance monotonically eastward.");
                $totalRotation += $diff;
            }

            $previousLagna = $currentLagna;
        }

        // Earth rotates 360° sidereal in ~23h 56m, in 24 solar hours it rotates ~360.985°
        $this->assertEqualsWithDelta(360.985, $totalRotation, 2.0);
    }
}

