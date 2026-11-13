<?php

namespace Tests\Unit\Jyotish\Chart;

use App\Domain\Jyotish\Chart\BhavaChalitGenerator;
use App\Domain\Jyotish\Chart\HouseSystem\HouseCuspCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Invariant & Precision Tests for Bhava Chalit Generator (T-JY03-03).
 */
final class BhavaChalitTest extends TestCase
{
    public function test_every_planet_assigned_to_exactly_one_bhava_and_one_rashi(): void
    {
        $lagna = 28.5; // Mesha 28° 30'
        $mc = 295.0;   // Makara 25° 00'
        $sripati = HouseCuspCalculator::forSystem(HouseCuspCalculator::SYSTEM_SRIPATI);

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

        $chart = BhavaChalitGenerator::generate($sripati, $lagna, $mc, $planets, 28.6139, 300.0, 23.44);

        $this->assertCount(12, $chart->bhavas);
        $this->assertCount(9, $chart->planetaryPlacements);

        $allPlacedPlanets = [];
        foreach ($chart->bhavas as $houseNum => $bhava) {
            $this->assertSame($houseNum, $bhava['house']);
            foreach ($bhava['planets'] as $p) {
                $allPlacedPlanets[] = $p;
            }
        }

        // Assert exactly 9 placements across all 12 bhavas without duplication
        $this->assertCount(9, $allPlacedPlanets);
        $this->assertEqualsCanonicalizing(array_keys($planets), $allPlacedPlanets);

        // Assert original celestial longitudes are strictly preserved
        foreach ($planets as $pName => $origLong) {
            $placement = $chart->planetaryPlacements[$pName];
            $this->assertEqualsWithDelta($origLong, $placement['sidereal_degrees'], 1e-9);
            $this->assertGreaterThanOrEqual(1, $placement['chalit_bhava']);
            $this->assertLessThanOrEqual(12, $placement['chalit_bhava']);
            $this->assertGreaterThanOrEqual(0, $placement['rashi_index']);
            $this->assertLessThanOrEqual(11, $placement['rashi_index']);
            $this->assertContains($placement['shift'], ['none', 'forward_1', 'backward_1']);
        }
    }

    public function test_planetary_shift_into_adjacent_bhava(): void
    {
        // Construct a case where a planet is in Vrishabha (sign 1) in D1,
        // but due to high Lagna degree in Mesha (sign 0), the H1 sandhi extends well into Vrishabha.
        $lagna = 28.0; // Mesha 28°
        $mc = 298.0;
        $sripati = HouseCuspCalculator::forSystem(HouseCuspCalculator::SYSTEM_SRIPATI);

        // Planet at 31.0° (Vrishabha 1° 00').
        // In Whole Sign, it is in Bhava 2.
        // In Sripati, H1 madhya is 28°, H2 madhya is ~58°, so sandhi between H1 and H2 is ~43°.
        // Therefore at 31°, it falls inside House 1!
        $planets = ['moon' => 31.0];

        $chart = BhavaChalitGenerator::generate($sripati, $lagna, $mc, $planets);

        $moonPlacement = $chart->planetaryPlacements['moon'];
        $this->assertSame(1, $moonPlacement['rashi_index']); // Vrishabha
        $this->assertSame(2, $moonPlacement['d1_bhava']);     // 2nd house in D1
        $this->assertSame(1, $moonPlacement['chalit_bhava']); // Shifted into 1st house in Chalit
        $this->assertSame('backward_1', $moonPlacement['shift']);
    }

    public function test_is_angle_in_arc_handles_circular_wraps(): void
    {
        // Normal arc [10, 40)
        $this->assertTrue(BhavaChalitGenerator::isAngleInArc(15.0, 10.0, 40.0));
        $this->assertTrue(BhavaChalitGenerator::isAngleInArc(10.0, 10.0, 40.0));
        $this->assertFalse(BhavaChalitGenerator::isAngleInArc(40.0, 10.0, 40.0));
        $this->assertFalse(BhavaChalitGenerator::isAngleInArc(5.0, 10.0, 40.0));

        // Wrapping arc [350, 20)
        $this->assertTrue(BhavaChalitGenerator::isAngleInArc(355.0, 350.0, 20.0));
        $this->assertTrue(BhavaChalitGenerator::isAngleInArc(5.0, 350.0, 20.0));
        $this->assertTrue(BhavaChalitGenerator::isAngleInArc(350.0, 350.0, 20.0));
        $this->assertFalse(BhavaChalitGenerator::isAngleInArc(20.0, 350.0, 20.0));
        $this->assertFalse(BhavaChalitGenerator::isAngleInArc(180.0, 350.0, 20.0));
    }
}

