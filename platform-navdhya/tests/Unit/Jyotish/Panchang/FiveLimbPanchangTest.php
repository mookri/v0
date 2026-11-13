<?php

namespace Tests\Unit\Jyotish\Panchang;

use App\Domain\Jyotish\Panchang\ChandraTaraBalaEvaluator;
use App\Domain\Jyotish\Panchang\DayDivisionCalculator;
use App\Domain\Jyotish\Panchang\PanchangOrchestrator;
use PHPUnit\Framework\TestCase;

/**
 * Unit Tests for Five-Limb Panchang & Auspicious Strength Engines (T-JY05-01, T-JY05-04, T-JY05-05).
 */
final class FiveLimbPanchangTest extends TestCase
{
    public function test_five_limbs_compute_consistently(): void
    {
        $jd = 2451545.0; // 2000-01-01 12:00 TT
        $lat = 28.6139;  // Delhi
        $lon = 77.2090;

        $p = PanchangOrchestrator::calculate($jd, $jd, $lat, $lon);

        $this->assertArrayHasKey('limbs', $p);
        $limbs = $p['limbs'];

        // 1. Tithi
        $this->assertGreaterThanOrEqual(1, $limbs['tithi']['number']);
        $this->assertLessThanOrEqual(30, $limbs['tithi']['number']);
        $this->assertContains($limbs['tithi']['paksha'], ['Shukla', 'Krishna']);
        $this->assertNotEmpty($limbs['tithi']['name']);
        $this->assertGreaterThan(2450000.0, $limbs['tithi']['end_jd']);

        // 2. Vara
        $this->assertGreaterThanOrEqual(0, $limbs['vara']['index']);
        $this->assertLessThanOrEqual(6, $limbs['vara']['index']);
        $this->assertNotEmpty($limbs['vara']['name']);

        // 3. Nakshatra
        $this->assertGreaterThanOrEqual(0, $limbs['nakshatra']['index']);
        $this->assertLessThanOrEqual(26, $limbs['nakshatra']['index']);
        $this->assertGreaterThanOrEqual(1, $limbs['nakshatra']['pada']);
        $this->assertLessThanOrEqual(4, $limbs['nakshatra']['pada']);
        $this->assertNotEmpty($limbs['nakshatra']['syllable']);
        $this->assertGreaterThan(2450000.0, $limbs['nakshatra']['end_jd']);

        // 4. Yoga
        $this->assertGreaterThanOrEqual(1, $limbs['yoga']['number']);
        $this->assertLessThanOrEqual(27, $limbs['yoga']['number']);
        $this->assertNotEmpty($limbs['yoga']['name']);

        // 5. Karana
        $this->assertGreaterThanOrEqual(1, $limbs['karana']['half_tithi']);
        $this->assertLessThanOrEqual(60, $limbs['karana']['half_tithi']);
        $this->assertNotEmpty($limbs['karana']['name']);
    }

    public function test_daylight_octiles_dynamic_duration(): void
    {
        // 14 hours daylight (summer solstice)
        $sunrise = 2451545.25; // 06:00
        $sunset = 2451545.833333; // 20:00 (14 hours)

        $div = DayDivisionCalculator::calculateDaytimeDivisions($sunrise, $sunset, 0); // Sunday

        $rahu = $div['rahu_kaal'];
        $rahuDurationHours = ($rahu['end_jd'] - $rahu['start_jd']) * 24.0;

        // In 14h day, each octile is 14/8 = 1.75 hours (105 mins), NOT fixed 90 mins!
        $this->assertEqualsWithDelta(1.75, $rahuDurationHours, 1e-4);
        $this->assertCount(8, $div['choghadiya']);
    }

    public function test_chandra_bala_and_tara_bala(): void
    {
        // Chandra Bala: 8th Moon from natal is Ashtama Chandra (inauspicious)
        $chandra = ChandraTaraBalaEvaluator::evaluateChandraBala(0, 7);
        $this->assertSame(8, $chandra['house_distance']);
        $this->assertFalse($chandra['is_auspicious']);
        $this->assertSame('ashtama_chandra', $chandra['classification']);

        // Chandra Bala: 7th Moon from natal is auspicious
        $chandra7 = ChandraTaraBalaEvaluator::evaluateChandraBala(0, 6);
        $this->assertSame(7, $chandra7['house_distance']);
        $this->assertTrue($chandra7['is_auspicious']);

        // Tara Bala: 2nd Nakshatra is Sampat (Wealth - auspicious)
        $tara2 = ChandraTaraBalaEvaluator::evaluateTaraBala(0, 1);
        $this->assertSame(2, $tara2['tara_number']);
        $this->assertSame('Sampat', $tara2['tara_name']);
        $this->assertTrue($tara2['is_benefic']);

        // Tara Bala: 7th Nakshatra is Naidhana (Vadha - inauspicious)
        $tara7 = ChandraTaraBalaEvaluator::evaluateTaraBala(0, 6);
        $this->assertSame(7, $tara7['tara_number']);
        $this->assertSame('Naidhana', $tara7['tara_name']);
        $this->assertFalse($tara7['is_benefic']);
    }
}

