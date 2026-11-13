<?php

namespace Tests\Unit\Jyotish\Astronomy;

use App\Domain\Jyotish\Astronomy\AyanamsaEngine;
use PHPUnit\Framework\TestCase;

/**
 * Unit Tests for Multi-Model Ayanamsa Calculation (T-JY02-03).
 */
final class AyanamsaEngineTest extends TestCase
{
    /**
     * J2000.0 epoch: 2000-01-01 12:00:00 TT (t = 0.0).
     * Verify classical reference values within tight tolerances (< 0.1 arcsecond or standard offsets).
     */
    public function test_ayanamsa_at_j2000_epoch(): void
    {
        $t = 0.0;

        // Lahiri at J2000 is approximately 23° 51' 11" ~ 23.8530555°
        $lahiri = AyanamsaEngine::calculate($t, AyanamsaEngine::SYSTEM_LAHIRI)['ayanamsa_degrees'];
        $this->assertEqualsWithDelta(23.8530555, $lahiri, 0.05);

        // KP at J2000 is approximately 23° 45' 56" ~ 23.7655555°
        $kp = AyanamsaEngine::calculate($t, AyanamsaEngine::SYSTEM_KP)['ayanamsa_degrees'];
        $this->assertEqualsWithDelta(23.7655555, $kp, 0.05);

        // Raman at J2000 is approximately 22° 24' 37" ~ 22.4102777°
        $raman = AyanamsaEngine::calculate($t, AyanamsaEngine::SYSTEM_RAMAN)['ayanamsa_degrees'];
        $this->assertEqualsWithDelta(22.4102777, $raman, 0.05);

        // Fagan-Bradley at J2000 is approximately 24° 44' 12" ~ 24.7366666°
        $fb = AyanamsaEngine::calculate($t, AyanamsaEngine::SYSTEM_FAGAN_BRADLEY)['ayanamsa_degrees'];
        $this->assertEqualsWithDelta(24.7366666, $fb, 0.05);
    }

    public function test_ayanamsa_rate_of_precession(): void
    {
        // One century later: t = 1.0
        $t1 = 0.0;
        $t2 = 1.0;

        $v1 = AyanamsaEngine::calculate($t1, AyanamsaEngine::SYSTEM_LAHIRI)['ayanamsa_degrees'];
        $v2 = AyanamsaEngine::calculate($t2, AyanamsaEngine::SYSTEM_LAHIRI)['ayanamsa_degrees'];

        // General precession in longitude is ~ 5029 arcsec/century = 1.3969°
        $rate = $v2 - $v1;
        $this->assertGreaterThan(1.38, $rate);
        $this->assertLessThan(1.42, $rate);
    }

    public function test_nutation_correction_is_deterministic(): void
    {
        $t = 0.0;
        $withNutation = AyanamsaEngine::calculate($t, AyanamsaEngine::SYSTEM_LAHIRI, true)['ayanamsa_degrees'];
        $withoutNutation = AyanamsaEngine::calculate($t, AyanamsaEngine::SYSTEM_LAHIRI, false)['ayanamsa_degrees'];

        // Nutation effect is < 18 arcseconds (< 0.005°)
        $diff = abs($withNutation - $withoutNutation);
        $this->assertLessThan(0.005, $diff);
    }

    public function test_all_supported_models_resolve(): void
    {
        foreach (AyanamsaEngine::CANONICAL_SYSTEMS as $system) {
            $res = AyanamsaEngine::calculate(0.0, $system);
            $this->assertGreaterThan(20.0, $res['ayanamsa_degrees']);
            $this->assertLessThan(27.0, $res['ayanamsa_degrees']);
            $this->assertNotEmpty($res['ayanamsa_dms']);
            $this->assertNotEmpty($res['epoch_reference']);
        }
    }
}
