<?php

declare(strict_types=1);

namespace Tests\Unit\Jyotish\Specialist;

use App\Domain\Jyotish\Jaimini\ArudhaLagnaCalculator;
use App\Domain\Jyotish\Jaimini\CharaKarakaCalculator;
use App\Domain\Jyotish\Jaimini\JaiminiEngine;
use App\Domain\Jyotish\Kp\KpEngine;
use App\Domain\Jyotish\Kp\SubLordDivider;
use App\Domain\Jyotish\Prashna\PrashnaEngine;
use App\Domain\Jyotish\Tajika\VarshaphalEngine;
use PHPUnit\Framework\TestCase;

final class SpecialistSystemsValidationTest extends TestCase
{
    public function test_jaimini_chara_karaka_7_and_8_schemes(): void
    {
        $calculator = new CharaKarakaCalculator();
        $longitudes = [
            'sun' => 28.5,       // 28° 30'
            'moon' => 24.2,      // 24° 12'
            'mars' => 18.0,
            'mercury' => 15.4,
            'jupiter' => 12.1,
            'venus' => 9.0,
            'saturn' => 4.5,
            'rahu' => 5.0,       // in 8-karaka, reversed: 30 - 5 = 25.0°
        ];

        $k7 = $calculator->calculate($longitudes, 7);
        $this->assertCount(7, $k7);
        $this->assertSame('sun', $k7['AK']['graha'], "Highest degree Sun must be Atmakaraka.");
        $this->assertSame('moon', $k7['AmK']['graha'], "Second highest Moon must be Amatyakaraka.");

        $k8 = $calculator->calculate($longitudes, 8);
        $this->assertCount(8, $k8);
        $this->assertSame('sun', $k8['AK']['graha']);
        // Rahu reversed degree is 25.0 > Moon 24.2 => Rahu becomes AmK in 8-karaka mode
        $this->assertSame('rahu', $k8['AmK']['graha']);
    }

    public function test_jaimini_arudha_exception_rules(): void
    {
        // Exception 1: Lord in 1st house => raw pada is 1st => moved to 10th
        $al1 = ArudhaLagnaCalculator::calculatePada(1, houseRashi: 1, lordRashi: 1);
        $this->assertSame(10, $al1, "Lagna lord in Lagna must produce Arudha Lagna in 10th house.");

        // Exception 2: Lord in 4th house => distance is 4 => raw pada is 4 + 4 - 1 = 7th => moved to 4th (10th from 7th)
        $al2 = ArudhaLagnaCalculator::calculatePada(1, houseRashi: 1, lordRashi: 4);
        $this->assertSame(4, $al2, "Pada falling in 7th house must move to 4th house.");

        // Normal case: Lagna in Aries (1), Lord Mars in Taurus (2) => distance 2 => Pada in Gemini (3)
        $alNormal = ArudhaLagnaCalculator::calculatePada(1, houseRashi: 1, lordRashi: 2);
        $this->assertSame(3, $alNormal);
    }

    public function test_kp_sub_lord_divisions_sum_to_exactly_800_arcminutes(): void
    {
        for ($nak = 0; $nak < 27; $nak++) {
            $subDivs = SubLordDivider::getSubDivisions($nak);
            $this->assertCount(9, $subDivs);

            $totalWidthMinutes = array_sum(array_column($subDivs, 'width_minutes'));
            // Acceptance Criteria AC-T-JY09-02: All 9 sub-lord divisions within each nakshatra sum to exactly 800 arcminutes
            $this->assertEqualsWithDelta(800.0, $totalWidthMinutes, 1e-4, "Nakshatra {$nak} sub-divisions must sum to 800'.");

            $this->assertSame(0.0, $subDivs[0]['start_minutes']);
            $this->assertEqualsWithDelta(800.0, $subDivs[8]['end_minutes'], 1e-4);
        }

        // Test Horary number 1..249 resolution
        $h1 = KpEngine::resolveHoraryNumber(1);
        $this->assertSame(1, $h1['number']);
        $this->assertSame('ketu', $h1['star_lord']);

        $h249 = KpEngine::resolveHoraryNumber(249);
        $this->assertSame(249, $h249['number']);
    }

    public function test_prashna_query_context_isolation_and_tajika_solar_return(): void
    {
        $prashna = new PrashnaEngine();
        $queryTime = new \DateTimeImmutable('2026-11-13T10:30:00Z');
        $pRes = $prashna->castPrashna($queryTime, 28.6139, 77.2090, "Career transition query");

        $this->assertNull($pRes['birth_profile_id'], "Prashna charts must have strictly null birth_profile_id.");
        $this->assertGreaterThan(0.0, $pRes['prashna_lagna_degrees']);

        // Tajika Varshaphal Solar Return root-solving
        $tajika = new VarshaphalEngine();
        $natalSunLong = 120.5; // Leo 0° 30'
        $approxBirthJd = 2451545.0;
        $returnRes = $tajika->calculateSolarReturn($natalSunLong, 2026, $approxBirthJd, natalLagnaSign: 1, ageYears: 26);

        $this->assertLessThan(1e-4, $returnRes['solar_longitude_error'], "Solar return root must converge within tolerance.");
        $this->assertGreaterThanOrEqual(1, $returnRes['muntha_sign']);
        $this->assertLessThanOrEqual(12, $returnRes['muntha_sign']);
    }
}

