<?php

namespace Tests\Unit\Calendar;

use App\Domain\Calendar\Panchang\AdhikaMasaDetector;
use App\Domain\Calendar\Panchang\KaranaCalculator;
use App\Domain\Calendar\Panchang\PanchangCalculator;
use App\Domain\Calendar\Panchang\SankrantiCalculator;
use App\Domain\Calendar\Panchang\VaraCalculator;
use App\Domain\Calendar\Panchang\YogaCalculator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class FullLimbsTest extends TestCase
{
    public function test_yoga_add_vs_subtract_discriminator_closes_p07(): void
    {
        // Example: Sun at 200°, Moon at 50°
        $sun = 200.0;
        $moon = 50.0;

        // Sum = 250°
        // 250 / (360/27 = 13.3333°) = 18.75 -> Yoga index 19 (PARIGHA)
        $yoga = YogaCalculator::calculate($sun, $moon);
        $this->assertSame(19, $yoga['number']);
        $this->assertSame('PARIGHA', $yoga['name']);

        // Discriminator: Subtraction would yield elongation (50 - 200 = -150 = 210°)
        // 210 / 12 = 17.5 -> Tithi 18, which is completely different!
        $elongation = fmod($moon - $sun + 360.0, 360.0);
        $this->assertEqualsWithDelta(210.0, $elongation, 0.001);
        $this->assertNotSame($yoga['number'], (int) floor($elongation / 13.3333) + 1);
    }

    public function test_karana_fixed_and_movable_sequence(): void
    {
        // 1st half-tithi (0° - 6° elongation): KIMSTUGHNA (fixed)
        $k1 = KaranaCalculator::calculate(3.0);
        $this->assertSame(1, $k1['half_tithi']);
        $this->assertSame('KIMSTUGHNA', $k1['name']);
        $this->assertTrue($k1['is_fixed']);
        $this->assertFalse($k1['is_vishti_bhadra']);

        // 2nd half-tithi (6° - 12°): BAVA (movable cycle begins)
        $k2 = KaranaCalculator::calculate(9.0);
        $this->assertSame(2, $k2['half_tithi']);
        $this->assertSame('BAVA', $k2['name']);
        $this->assertFalse($k2['is_fixed']);

        // 8th half-tithi (42° - 48°): VISHTI / BHADRA
        $k8 = KaranaCalculator::calculate(45.0);
        $this->assertSame(8, $k8['half_tithi']);
        $this->assertSame('VISHTI', $k8['name']);
        $this->assertTrue($k8['is_vishti_bhadra']);

        // 58th half-tithi (342° - 348°): SHAKUNI (fixed)
        $k58 = KaranaCalculator::calculate(345.0);
        $this->assertSame(58, $k58['half_tithi']);
        $this->assertSame('SHAKUNI', $k58['name']);
        $this->assertTrue($k58['is_fixed']);

        // 59th half-tithi (348° - 354°): CHATUSHPADA (fixed)
        $k59 = KaranaCalculator::calculate(351.0);
        $this->assertSame(59, $k59['half_tithi']);
        $this->assertSame('CHATUSHPADA', $k59['name']);
        $this->assertTrue($k59['is_fixed']);

        // 60th half-tithi (354° - 360°): NAGA (fixed)
        $k60 = KaranaCalculator::calculate(357.0);
        $this->assertSame(60, $k60['half_tithi']);
        $this->assertSame('NAGA', $k60['name']);
        $this->assertTrue($k60['is_fixed']);
    }

    public function test_vara_dual_anchoring_civil_vs_sunrise(): void
    {
        // Tuesday morning 04:30 AM (pre-sunrise). Sunrise is at 06:15 AM.
        $sunrise = CarbonImmutable::parse('2026-11-10T06:15:00', 'Asia/Kolkata');
        $preSunrise = CarbonImmutable::parse('2026-11-10T04:30:00', 'Asia/Kolkata');

        $varaPre = VaraCalculator::calculate($preSunrise, $sunrise);
        $this->assertTrue($varaPre['is_pre_sunrise']);
        $this->assertSame('MANGALAVARA', $varaPre['civil_name']); // Civil day is Tuesday
        $this->assertSame('SOMAVARA', $varaPre['vedic_name']);    // Vedic day is still Monday!

        // Tuesday morning 07:00 AM (post-sunrise)
        $postSunrise = CarbonImmutable::parse('2026-11-10T07:00:00', 'Asia/Kolkata');
        $varaPost = VaraCalculator::calculate($postSunrise, $sunrise);
        $this->assertFalse($varaPost['is_pre_sunrise']);
        $this->assertSame('MANGALAVARA', $varaPost['civil_name']);
        $this->assertSame('MANGALAVARA', $varaPost['vedic_name']); // Vedic day is now Tuesday
    }

    public function test_sankranti_and_rashi_calculation(): void
    {
        // 15° sidereal -> MESHA
        $r1 = SankrantiCalculator::calculateRashi(15.0);
        $this->assertSame(1, $r1['number']);
        $this->assertSame('MESHA', $r1['name']);

        // 285° sidereal -> MAKARA (Capricorn)
        $r10 = SankrantiCalculator::calculateRashi(285.0);
        $this->assertSame(10, $r10['number']);
        $this->assertSame('MAKARA', $r10['name']);
    }

    public function test_adhika_masa_detection(): void
    {
        $adhika = AdhikaMasaDetector::classifyByCount(0);
        $this->assertTrue($adhika['is_adhika']);
        $this->assertFalse($adhika['is_kshaya']);

        $nija = AdhikaMasaDetector::classifyByCount(1);
        $this->assertFalse($nija['is_adhika']);
        $this->assertFalse($nija['is_kshaya']);

        $kshaya = AdhikaMasaDetector::classifyByCount(2);
        $this->assertFalse($kshaya['is_adhika']);
        $this->assertTrue($kshaya['is_kshaya']);
    }

    public function test_full_panchang_occurrence_contains_all_five_limbs(): void
    {
        $occ = PanchangCalculator::calculate(2026, 11, 10, 23.1765, 75.7885);

        // Limb 1: Tithi
        $this->assertIsArray($occ->tithi);
        $this->assertArrayHasKey('name', $occ->tithi);

        // Limb 2: Nakshatra
        $this->assertIsArray($occ->nakshatra);
        $this->assertArrayHasKey('name', $occ->nakshatra);

        // Limb 3: Yoga
        $this->assertIsArray($occ->yoga);
        $this->assertArrayHasKey('name', $occ->yoga);
        $this->assertGreaterThan(0, $occ->yoga['number']);
        $this->assertLessThanOrEqual(27, $occ->yoga['number']);

        // Limb 4: Karana
        $this->assertIsArray($occ->karana);
        $this->assertArrayHasKey('name', $occ->karana);
        $this->assertGreaterThan(0, $occ->karana['number']);
        $this->assertLessThanOrEqual(11, $occ->karana['number']);

        // Limb 5: Vara
        $this->assertIsArray($occ->vara);
        $this->assertArrayHasKey('vedic_name', $occ->vara);
        $this->assertArrayHasKey('civil_name', $occ->vara);
    }
}

