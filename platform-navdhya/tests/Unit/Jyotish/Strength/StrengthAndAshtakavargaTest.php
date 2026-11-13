<?php

declare(strict_types=1);

namespace Tests\Unit\Jyotish\Strength;

use App\Domain\Jyotish\Ashtakavarga\BavCalculator;
use App\Domain\Jyotish\Ashtakavarga\SavCalculator;
use App\Domain\Jyotish\Matching\Kootas\AshtakootaMatcher;
use App\Domain\Jyotish\Matching\ManglikAnalyzer;
use App\Domain\Jyotish\Rules\DoshaAnalyzer;
use App\Domain\Jyotish\Rules\YogaDetector;
use App\Domain\Jyotish\Strength\ShadbalaCalculator;
use PHPUnit\Framework\TestCase;

final class StrengthAndAshtakavargaTest extends TestCase
{
    public function test_shadbala_component_summation_and_rupas(): void
    {
        $calculator = new ShadbalaCalculator();

        $grahaData = [
            'sun' => ['longitude' => 10.0, 'rashi' => 1, 'house_number' => 10, 'exaltation_longitude' => 10.0, 'gender' => 'MALE'],
            'moon' => ['longitude' => 45.0, 'rashi' => 2, 'house_number' => 11, 'exaltation_longitude' => 33.0, 'gender' => 'FEMALE'],
            'jupiter' => ['longitude' => 95.0, 'rashi' => 4, 'house_number' => 1, 'exaltation_longitude' => 95.0, 'gender' => 'MALE'],
        ];

        $results = $calculator->calculateAll($grahaData, ascendantLongitude: 95.0);

        $this->assertCount(3, $results);

        foreach ($results as $graha => $res) {
            // Assert exact mathematical sum: Total Virupas = sum of components
            $expectedSum = array_sum($res['components_virupas']);
            $this->assertEqualsWithDelta($expectedSum, $res['total_virupas'], 0.05, "Shadbala total must match exact sum of components for {$graha}.");
            $this->assertEqualsWithDelta($res['total_virupas'] / 60.0, $res['total_rupas'], 0.05);
            $this->assertGreaterThan(0.0, $res['minimum_required_rupas']);
        }
    }

    public function test_ashtakavarga_bav_and_sav_337_bindus_invariant(): void
    {
        $bavCalc = new BavCalculator();
        $savCalc = new SavCalculator();

        $positions = [
            'sun' => 1, 'moon' => 2, 'mars' => 3, 'mercury' => 1,
            'jupiter' => 4, 'venus' => 12, 'saturn' => 10, 'lagna' => 4,
        ];

        $classical7 = ['sun', 'moon', 'mars', 'mercury', 'jupiter', 'venus', 'saturn'];
        $bavMatrices = [];

        foreach ($classical7 as $g) {
            $matrix = $bavCalc->calculateBav($g, $positions);
            $bavMatrices[$g] = $matrix;

            // BAV checks: 12 signs, each bindu in [0, 8]
            $this->assertCount(12, $matrix->signBindus);
            foreach ($matrix->signBindus as $sign => $bindus) {
                $this->assertGreaterThanOrEqual(0, $bindus);
                $this->assertLessThanOrEqual(8, $bindus);
            }
        }

        $sav = $savCalc->calculateSav($bavMatrices);

        // Acceptance Criteria AC-T-JY08-04: SAV sign total strictly equals sum of BAV; total equals exactly 337 bindus
        $this->assertSame(337, $sav['total_bindus'], "Classical Parashari SAV total across 12 signs must equal exactly 337 bindus.");
        $this->assertEqualsWithDelta(28.08, $sav['average_per_sign'], 0.1);
    }

    public function test_ashtakoota_compatibility_and_manglik_cancellation(): void
    {
        $matcher = new AshtakootaMatcher();
        // Groom Moon in Ashwini (0°), Bride Moon in Rohini (45°)
        $res = $matcher->match(groomMoonLong: 0.0, brideMoonLong: 45.0);

        $this->assertSame(36, $res['max_score']);
        $this->assertGreaterThanOrEqual(0.0, $res['total_score']);
        $this->assertLessThanOrEqual(36.0, $res['total_score']);
        $this->assertCount(8, $res['kootas']);

        $manglik = new ManglikAnalyzer();
        // Mars in 10 (exalted in Capricorn)
        $mRes = $manglik->analyze(marsRashi: 10, lagnaRashi: 1, moonRashi: 2, venusRashi: 3);
        $this->assertTrue($mRes['is_cancelled'], "Mars exalted in Capricorn should trigger cancellation exception.");
        $this->assertFalse($mRes['is_manglik']);
    }

    public function test_yoga_detection_and_dosha_analysis(): void
    {
        $detector = new YogaDetector();
        $positions = [
            'jupiter' => ['house' => 1, 'rashi' => 4],
            'moon' => ['house' => 4, 'rashi' => 7],
            'sun' => ['house' => 10, 'rashi' => 1],
            'mercury' => ['house' => 10, 'rashi' => 1],
            'mars' => ['house' => 7, 'rashi' => 10], // Exalted in Capricorn in Kendra 7th house => Ruchaka
        ];

        $yogas = $detector->detect($positions, lagnaRashi: 4);
        $yogaCodes = array_column($yogas, 'code');

        $this->assertContains('GAJAKESARI_YOGA', $yogaCodes);
        $this->assertContains('BUDHADITYA_YOGA', $yogaCodes);
        $this->assertContains('RUCHAKA_YOGA', $yogaCodes);
        $this->assertContains('HAMSA_YOGA', $yogaCodes);

        $doshas = (new DoshaAnalyzer())->analyze($positions);
        $this->assertIsArray($doshas);
    }
}

