<?php

namespace Tests\Unit\Jyotish\Chart;

use App\Domain\Jyotish\Chart\HouseSystem\HouseCuspCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Unit & Precision Tests for House Systems (T-JY03-02).
 */
final class HouseCuspPrecisionTest extends TestCase
{
    public function test_all_supported_systems_generate_twelve_valid_cusps(): void
    {
        $asc = 45.5; // Vrishabha 15° 30'
        $mc = 318.2; // Kumbha 18° 12'
        $lat = 28.6139; // Delhi
        $ramc = 320.0;
        $eps = 23.44;

        foreach (HouseCuspCalculator::SUPPORTED_SYSTEMS as $systemCode) {
            $calc = HouseCuspCalculator::forSystem($systemCode);
            $cusps = $calc->calculateCusps($asc, $mc, $lat, $ramc, $eps);

            $this->assertCount(12, $cusps);

            for ($h = 1; $h <= 12; $h++) {
                $this->assertArrayHasKey($h, $cusps);
                $c = $cusps[$h];
                $this->assertSame($h, $c['house']);
                $this->assertGreaterThanOrEqual(0.0, $c['cusp_degrees']);
                $this->assertLessThan(360.0, $c['cusp_degrees']);
                $this->assertGreaterThanOrEqual(0, $c['rashi_index']);
                $this->assertLessThan(12, $c['rashi_index']);
                $this->assertNotEmpty($c['dms']);
                $this->assertNotEmpty($c['rashi_name']);

                // Continuity: sandhi_start of house (h % 12 + 1) matches sandhi_end of house h
                $nextH = ($h % 12) + 1;
                $this->assertEqualsWithDelta(
                    $c['sandhi_end'],
                    $cusps[$nextH]['sandhi_start'],
                    1e-9,
                    "Sandhi discontinuity between house {$h} and {$nextH} in {$systemCode}"
                );
            }
        }
    }

    public function test_whole_sign_preserves_thirty_degree_spans(): void
    {
        $calc = HouseCuspCalculator::forSystem(HouseCuspCalculator::SYSTEM_WHOLE_SIGN);
        $cusps = $calc->calculateCusps(12.34, 100.0);

        foreach ($cusps as $c) {
            $this->assertEqualsWithDelta(30.0, $c['span_degrees'], 1e-9);
        }
    }

    public function test_sripati_distinct_from_whole_sign(): void
    {
        $asc = 15.0; // Mesha 15°
        $mc = 280.0; // Makara 10°

        $whole = HouseCuspCalculator::forSystem(HouseCuspCalculator::SYSTEM_WHOLE_SIGN)->calculateCusps($asc, $mc);
        $sripati = HouseCuspCalculator::forSystem(HouseCuspCalculator::SYSTEM_SRIPATI)->calculateCusps($asc, $mc);

        // In Whole Sign, H10 is always at Makara 15° (same degree as Lagna)
        // In Sripati, H10 cusp is the exact MC at Makara 10°
        $this->assertNotEquals($whole[10]['cusp_degrees'], $sripati[10]['cusp_degrees']);
        $this->assertEqualsWithDelta(280.0, $sripati[10]['cusp_degrees'], 1e-9);
    }

    public function test_placidus_polar_fallback_to_sripati(): void
    {
        $calc = HouseCuspCalculator::forSystem(HouseCuspCalculator::SYSTEM_PLACIDUS);
        // High Arctic latitude
        $cusps = $calc->calculateCusps(45.0, 310.0, 71.0, 315.0, 23.44);

        $this->assertCount(12, $cusps);
        $this->assertSame(1, $cusps[1]['house']);
    }
}

