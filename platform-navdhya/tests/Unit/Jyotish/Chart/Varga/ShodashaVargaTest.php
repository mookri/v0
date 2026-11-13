<?php

namespace Tests\Unit\Jyotish\Chart\Varga;

use App\Domain\Jyotish\Chart\Varga\DashamshaAnalyzer;
use App\Domain\Jyotish\Chart\Varga\NavamshaAnalyzer;
use App\Domain\Jyotish\Chart\Varga\VargaCalculator;
use App\Domain\Jyotish\Chart\Varga\VargaHarmonicEngine;
use PHPUnit\Framework\TestCase;

/**
 * Unit & Precision Tests for Shodasha Varga Engine (T-JY04-07, T-JY04-08).
 */
final class ShodashaVargaTest extends TestCase
{
    public function test_all_sixteen_vargas_compute_for_lagna_and_planets(): void
    {
        $lagna = 28.5; // Mesha 28° 30'
        $planets = [
            'sun' => 15.2,
            'moon' => 45.8,
            'mars' => 92.1,
            'mercury' => 12.0,
            'jupiter' => 210.5,
            'venus' => 355.0,
            'saturn' => 310.2,
            'rahu' => 140.0,
            'ketu' => 320.0,
        ];

        $vargas = VargaCalculator::calculateAll($lagna, $planets);

        $this->assertCount(16, $vargas);

        foreach (array_keys(VargaHarmonicEngine::SUPPORTED_VARGAS) as $code) {
            $this->assertArrayHasKey($code, $vargas);
            $v = $vargas[$code];
            $this->assertSame($code, $v['varga']);
            $this->assertArrayHasKey('lagna', $v);
            $this->assertArrayHasKey('planets', $v);
            $this->assertCount(9, $v['planets']);

            // Assert valid rashi index (0..11) for all bodies
            $this->assertGreaterThanOrEqual(0, $v['lagna']['rashi_index']);
            $this->assertLessThanOrEqual(11, $v['lagna']['rashi_index']);

            foreach ($v['planets'] as $pName => $pData) {
                $this->assertGreaterThanOrEqual(0, $pData['rashi_index']);
                $this->assertLessThanOrEqual(11, $pData['rashi_index']);
                $this->assertNotEmpty($pData['rashi_name']);
                $this->assertNotEmpty($pData['rashi_lord']);
            }
        }
    }

    public function test_vargottama_placement_detection(): void
    {
        // For a Movable sign (Mesha / 0), Pada 1 (0° - 3°20') maps to Mesha in D9.
        // Therefore, a planet at 2.0° is Vargottama!
        $lagna = 15.0;
        $planets = [
            'sun' => 2.0,    // Mesha in D1 and Mesha in D9 -> Vargottama!
            'moon' => 25.0,  // Mesha in D1, but Dhanu in D9 -> Not Vargottama
        ];

        $analysis = NavamshaAnalyzer::analyze($lagna, $planets);

        $this->assertContains('sun', $analysis['vargottama_planets']);
        $this->assertNotContains('moon', $analysis['vargottama_planets']);
        $this->assertTrue($analysis['placements']['sun']['is_vargottama']);
        $this->assertFalse($analysis['placements']['moon']['is_vargottama']);
    }

    public function test_d10_dashamsha_career_analyzer(): void
    {
        $lagna = 10.0;
        $planets = [
            'sun' => 15.0,
            'jupiter' => 90.0,
        ];

        $d10Analysis = DashamshaAnalyzer::analyze($lagna, $planets);

        $this->assertArrayHasKey('dashamsha_lagna', $d10Analysis);
        $this->assertArrayHasKey('tenth_house', $d10Analysis);
        $this->assertArrayHasKey('artha_trikona_planets', $d10Analysis);
        $this->assertArrayHasKey('placements', $d10Analysis);
        $this->assertNotEmpty($d10Analysis['tenth_house']['lord']);
    }
}

