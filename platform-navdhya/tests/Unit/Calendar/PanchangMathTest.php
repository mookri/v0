<?php

namespace Tests\Unit\Calendar;

use App\Domain\Calendar\Astronomy\Elongation;
use App\Domain\Calendar\Astronomy\JulianDayCalculator;
use App\Domain\Calendar\Astronomy\LunarLongitude;
use App\Domain\Calendar\Astronomy\SolarLongitude;
use App\Domain\Calendar\Astronomy\SunriseUtcCalculator;
use App\Domain\Calendar\Panchang\LunarMonthCalculator;
use App\Domain\Calendar\Panchang\NakshatraCalculator;
use App\Domain\Calendar\Panchang\PanchangCalculator;
use App\Domain\Calendar\Panchang\PanchangConfidence;
use App\Domain\Calendar\Panchang\TithiCalculator;
use PHPUnit\Framework\TestCase;

final class PanchangMathTest extends TestCase
{
    public function test_julian_day_calculation(): void
    {
        // 2000-01-01 12:00:00 UTC = JD 2451545.0
        $jd = JulianDayCalculator::fromUtc(2000, 1, 1, 12, 0, 0);
        $this->assertEqualsWithDelta(2451545.0, $jd, 0.0001);

        $t = JulianDayCalculator::centuriesSinceJ2000($jd);
        $this->assertEqualsWithDelta(0.0, $t, 0.000001);
    }

    public function test_solar_and_lunar_coordinates_are_normalized(): void
    {
        $jd = JulianDayCalculator::fromUtc(2026, 11, 10, 6, 0, 0);
        $t = JulianDayCalculator::centuriesSinceJ2000($jd);

        $sun = SolarLongitude::calculate($t);
        $moon = LunarLongitude::calculate($t);

        $this->assertGreaterThanOrEqual(0.0, $sun);
        $this->assertLessThan(360.0, $sun);

        $this->assertGreaterThanOrEqual(0.0, $moon);
        $this->assertLessThan(360.0, $moon);

        $elongation = Elongation::calculate($moon, $sun);
        $this->assertGreaterThanOrEqual(0.0, $elongation);
        $this->assertLessThan(360.0, $elongation);
    }

    public function test_tithi_calculation_boundaries_and_paksha(): void
    {
        // 0 degrees elongation = Tithi 1 (Shukla Pratipada)
        $t1 = TithiCalculator::calculate(0.0);
        $this->assertSame(1, $t1['number']);
        $this->assertSame(TithiCalculator::PAKSHA_SHUKLA, $t1['paksha']);
        $this->assertSame('PRATIPADA', $t1['name']);

        // 175 degrees elongation = Tithi 15 (Purnima)
        $t15 = TithiCalculator::calculate(175.0);
        $this->assertSame(15, $t15['number']);
        $this->assertSame(TithiCalculator::PAKSHA_SHUKLA, $t15['paksha']);
        $this->assertSame('PURNIMA', $t15['name']);

        // 185 degrees elongation = Tithi 16 (Krishna Pratipada)
        $t16 = TithiCalculator::calculate(185.0);
        $this->assertSame(16, $t16['number']);
        $this->assertSame(TithiCalculator::PAKSHA_KRISHNA, $t16['paksha']);
        $this->assertSame('PRATIPADA', $t16['name']);
        $this->assertSame(1, $t16['index_in_paksha']);

        // 355 degrees elongation = Tithi 30 (Amavasya)
        $t30 = TithiCalculator::calculate(355.0);
        $this->assertSame(30, $t30['number']);
        $this->assertSame(TithiCalculator::PAKSHA_KRISHNA, $t30['paksha']);
        $this->assertSame('AMAVASYA', $t30['name']);
        $this->assertSame(15, $t30['index_in_paksha']);
    }

    public function test_nakshatra_calculation_and_padas(): void
    {
        // 0 degrees = Ashwini Pada 1
        $nak1 = NakshatraCalculator::calculate(0.5);
        $this->assertSame(1, $nak1['number']);
        $this->assertSame('ASHWINI', $nak1['name']);
        $this->assertSame(1, $nak1['pada']);

        // 45 degrees (Rohini, ~40° to 53°20')
        $nak4 = NakshatraCalculator::calculate(45.0);
        $this->assertSame(4, $nak4['number']);
        $this->assertSame('ROHINI', $nak4['name']);

        // 359 degrees = Revati (27)
        $nak27 = NakshatraCalculator::calculate(359.0);
        $this->assertSame(27, $nak27['number']);
        $this->assertSame('REVATI', $nak27['name']);
    }

    public function test_civil_sunrise_calculator(): void
    {
        // Sunrise at Varanasi/Ujjain (23.1765° N, 75.7885° E)
        $sunrise = SunriseUtcCalculator::calculate(2026, 11, 10, 23.1765, 75.7885);
        $this->assertGreaterThanOrEqual(0, $sunrise['hour']);
        $this->assertLessThan(24, $sunrise['hour']);
        $this->assertGreaterThanOrEqual(0, $sunrise['minute']);
        $this->assertLessThan(60, $sunrise['minute']);
        // UTC sunrise for India is typically around 00:30 to 01:30 UTC (06:00 to 07:00 IST)
        $this->assertContains($sunrise['hour'], [0, 1]);
    }

    public function test_lunar_month_amanta_and_purnimanta_dual_naming(): void
    {
        // Sun at 15 degrees (Mesha -> Chaitra)
        // Shukla Paksha: Amanta and Purnimanta are identical
        $shuklaMasa = LunarMonthCalculator::calculate(15.0, 5);
        $this->assertSame('CHAITRA', $shuklaMasa['amanta']);
        $this->assertSame('CHAITRA', $shuklaMasa['purnimanta']);

        // Krishna Paksha: Purnimanta leads by 1 (Chaitra -> Vaishakha)
        $krishnaMasa = LunarMonthCalculator::calculate(15.0, 20);
        $this->assertSame('CHAITRA', $krishnaMasa['amanta']);
        $this->assertSame('VAISHAKHA', $krishnaMasa['purnimanta']);
    }

    public function test_panchang_confidence_bounds(): void
    {
        // Very close to boundary (0.5% into tithi) -> LOW confidence
        $this->assertSame(PanchangConfidence::LOW, PanchangConfidence::forTithi(0.005));

        // 2% into tithi -> MEDIUM confidence
        $this->assertSame(PanchangConfidence::MEDIUM, PanchangConfidence::forTithi(0.02));

        // 50% into tithi (middle of tithi) -> HIGH confidence
        $this->assertSame(PanchangConfidence::HIGH, PanchangConfidence::forTithi(0.50));
    }

    public function test_full_panchang_calculator_produces_valid_occurrence(): void
    {
        $occurrence = PanchangCalculator::calculate(2026, 11, 10, 23.1765, 75.7885, 'PURNIMANTA');

        $this->assertSame('2026-11-10', $occurrence->date);
        $this->assertNotEmpty($occurrence->sunriseUtc);
        $this->assertGreaterThanOrEqual(1, $occurrence->tithi['number']);
        $this->assertLessThanOrEqual(30, $occurrence->tithi['number']);
        $this->assertGreaterThanOrEqual(1, $occurrence->nakshatra['number']);
        $this->assertLessThanOrEqual(27, $occurrence->nakshatra['number']);
        $this->assertNotEmpty($occurrence->resolvedMasa());
        $this->assertContains($occurrence->confidence, [PanchangConfidence::HIGH, PanchangConfidence::MEDIUM, PanchangConfidence::LOW]);
    }
}

