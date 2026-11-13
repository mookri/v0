<?php

namespace Tests\Unit\Calendar;

use App\Domain\Calendar\Astronomy\Ayanamsa;
use App\Domain\Calendar\Astronomy\Elongation;
use App\Domain\Calendar\Astronomy\JulianDayCalculator;
use App\Domain\Calendar\Astronomy\LunarLongitude;
use App\Domain\Calendar\Astronomy\SolarLongitude;
use App\Domain\Calendar\Panchang\LunarMonthCalculator;
use App\Domain\Calendar\Panchang\NakshatraCalculator;
use App\Domain\Calendar\Panchang\PanchangCalculator;
use Tests\TestCase;

class AyanamsaParityTest extends TestCase
{
    public function test_ayanamsa_shifts_nakshatra_from_tropical_to_sidereal(): void
    {
        // 2026-11-10 (≈ Delhi sunrise UTC)
        $jd = JulianDayCalculator::fromUtc(2026, 11, 10, 0, 30, 0);
        $t = JulianDayCalculator::centuriesSinceJ2000($jd);
        $moonTrop = LunarLongitude::calculate($t);

        // Without ayanamsa (defective P-01) -> JYESHTHA
        $defective = NakshatraCalculator::calculate($moonTrop);
        $this->assertSame('JYESHTHA', $defective['name']);

        // With Lahiri ayanamsa -> VISHAKHA
        $moonSidereal = Ayanamsa::toSidereal($moonTrop, $t);
        $corrected = NakshatraCalculator::calculate($moonSidereal);
        $this->assertSame('VISHAKHA', $corrected['name']);

        // 2026-01-01
        $jd2 = JulianDayCalculator::fromUtc(2026, 1, 1, 0, 30, 0);
        $t2 = JulianDayCalculator::centuriesSinceJ2000($jd2);
        $moonTrop2 = LunarLongitude::calculate($t2);
        $this->assertSame('ARDRA', NakshatraCalculator::calculate($moonTrop2)['name']);
        $this->assertSame('ROHINI', NakshatraCalculator::calculate(Ayanamsa::toSidereal($moonTrop2, $t2))['name']);

        // 2027-05-15
        $jd3 = JulianDayCalculator::fromUtc(2027, 5, 15, 0, 30, 0);
        $t3 = JulianDayCalculator::centuriesSinceJ2000($jd3);
        $moonTrop3 = LunarLongitude::calculate($t3);
        $this->assertSame('HASTA', NakshatraCalculator::calculate($moonTrop3)['name']);
        $this->assertSame('PURVA_PHALGUNI', NakshatraCalculator::calculate(Ayanamsa::toSidereal($moonTrop3, $t3))['name']);
    }

    public function test_tithi_elongation_is_strictly_ayanamsa_invariant(): void
    {
        $jd = JulianDayCalculator::fromUtc(2026, 11, 10, 0, 30, 0);
        $t = JulianDayCalculator::centuriesSinceJ2000($jd);
        $moonTrop = LunarLongitude::calculate($t);
        $sunTrop = SolarLongitude::calculate($t);

        $moonSidereal = Ayanamsa::toSidereal($moonTrop, $t);
        $sunSidereal = Ayanamsa::toSidereal($sunTrop, $t);

        $elongTrop = Elongation::calculate($moonTrop, $sunTrop);
        $elongSidereal = Elongation::calculate($moonSidereal, $sunSidereal);

        $diff = abs($elongTrop - $elongSidereal);
        $this->assertLessThan(1e-10, $diff, 'Elongation must be mathematically invariant under Ayanamsa rotation.');
    }

    public function test_ayanamsa_rectifies_lunar_month_masa(): void
    {
        // 2026-11-10
        $jd = JulianDayCalculator::fromUtc(2026, 11, 10, 0, 30, 0);
        $t = JulianDayCalculator::centuriesSinceJ2000($jd);
        $sunTrop = SolarLongitude::calculate($t);

        // Defective tropical month was KARTIKA (rashi 8)
        $defective = LunarMonthCalculator::calculate($sunTrop, 1);
        $this->assertSame('KARTIKA', $defective['amanta']);

        // Correct sidereal month is ASHVINA (rashi 7)
        $sunSidereal = Ayanamsa::toSidereal($sunTrop, $t);
        $corrected = LunarMonthCalculator::calculate($sunSidereal, 1);
        $this->assertSame('ASHVINA', $corrected['amanta']);
    }

    public function test_panchang_calculator_emits_sidereal_nakshatra_and_masa(): void
    {
        $occurrence = PanchangCalculator::calculate(2026, 11, 10, 28.6139, 77.2090, 'SMARTHA');

        // Nakshatra must be sidereal (VISHAKHA, not JYESHTHA)
        $this->assertSame('VISHAKHA', $occurrence->nakshatra['name']);

        // Resolved masa must be sidereal (ASHVINA, not KARTIKA)
        $this->assertSame('ASHVINA', $occurrence->resolvedMasa());

        // Tithi remains Pratipada (Shukla 1)
        $this->assertSame('PRATIPADA', $occurrence->tithi['name']);
    }
}

