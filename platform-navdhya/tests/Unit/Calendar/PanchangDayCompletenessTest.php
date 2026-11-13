<?php

namespace Tests\Unit\Calendar;

use App\Domain\Calendar\Astronomy\EventBoundaryResolver;
use App\Domain\Calendar\Astronomy\SunriseUtcCalculator;
use App\Domain\Calendar\Astronomy\SunsetUtcCalculator;
use App\Domain\Calendar\Panchang\KaranaCalculator;
use App\Domain\Calendar\Panchang\LunarMonthCalculator;
use App\Domain\Calendar\Panchang\MoonPhaseCalculator;
use App\Domain\Calendar\Panchang\PanchangCalculator;
use App\Domain\Calendar\Panchang\PanchangDay;
use App\Domain\Calendar\Panchang\VaraCalculator;
use App\Domain\Calendar\Panchang\YogaCalculator;
use PHPUnit\Framework\TestCase;

class PanchangDayCompletenessTest extends TestCase
{
    public function test_panchang_day_contains_all_limbs_with_continuous_boundaries_gate_g_limb_1(): void
    {
        $year = 2026;
        $month = 11;
        $day = 10;
        $lat = 23.1765;
        $lng = 75.7885;

        $occ = PanchangCalculator::calculate($year, $month, $day, $lat, $lng);
        $sunriseRes = SunriseUtcCalculator::calculate($year, $month, $day, $lat, $lng);
        $sunsetRes = SunsetUtcCalculator::calculate($year, $month, $day, $lat, $lng);

        $tithiInterval = EventBoundaryResolver::resolveTithiInterval($sunriseRes->instant_utc);
        $nakInterval = EventBoundaryResolver::resolveNakshatraInterval($sunriseRes->instant_utc);

        $sunriseUs = (int) ($sunriseRes->instant_utc->getTimestamp() * 1_000_000 + $sunriseRes->instant_utc->micro);
        $sunsetUs = (int) ($sunsetRes->instant_utc->getTimestamp() * 1_000_000 + $sunsetRes->instant_utc->micro);
        $solarNoonUs = (int) (($sunriseUs + $sunsetUs) / 2);

        $panchangDay = PanchangDay::fromCalculations(
            localDate: "{$year}-{$month}-{$day}",
            placeId: 'ujjain_central',
            profileCode: 'drik-lahiri-amanta',
            sunriseAtUs: $sunriseUs,
            sunsetAtUs: $sunsetUs,
            solarNoonUs: $solarNoonUs,
            tithi: [
                'number' => $occ->tithi['number'],
                'name' => $occ->tithi['name'],
                'paksha' => $occ->tithi['paksha'],
                'starts_at_us' => $tithiInterval['starts_at_us'],
                'ends_at_us' => $tithiInterval['ends_at_us'],
            ],
            nakshatra: [
                'number' => $occ->nakshatra['number'],
                'name' => $occ->nakshatra['name'],
                'pada' => $occ->nakshatra['pada'],
                'starts_at_us' => $nakInterval['starts_at_us'],
                'ends_at_us' => $nakInterval['ends_at_us'],
            ],
            yoga: $occ->yoga,
            karana: $occ->karana,
            vara: $occ->vara,
            masaAmanta: $occ->masa['amanta'],
            masaPurnimanta: $occ->masa['purnimanta'],
            isAdhika: $occ->masa['is_adhika'],
            moonPhase: MoonPhaseCalculator::calculate(0.0),
            confidence: $occ->confidence,
            generation: 'gen_active'
        );

        $array = $panchangDay->toArray();

        // 1. All 5 limbs present and non-empty
        $this->assertNotEmpty($array['tithi']);
        $this->assertNotEmpty($array['nakshatra']);
        $this->assertNotEmpty($array['yoga']);
        $this->assertNotEmpty($array['karana']);
        $this->assertNotEmpty($array['vara']);

        // 2. Microsecond boundaries present and ordered
        $this->assertGreaterThan(0, $array['tithi']['starts_at_us']);
        $this->assertGreaterThan($array['tithi']['starts_at_us'], $array['tithi']['ends_at_us']);
        $this->assertGreaterThan(0, $array['nakshatra']['starts_at_us']);
        $this->assertGreaterThan($array['nakshatra']['starts_at_us'], $array['nakshatra']['ends_at_us']);

        // 3. Dual Amanta and Purnimanta present
        $this->assertNotEmpty($array['masa_amanta']);
        $this->assertNotEmpty($array['masa_purnimanta']);

        // 4. Day division present
        $this->assertGreaterThan(0, $array['sunrise_at_us']);
        $this->assertGreaterThan($array['sunrise_at_us'], $array['sunset_at_us']);
        $this->assertGreaterThan($array['sunrise_at_us'], $array['solar_noon_us']);
        $this->assertLessThan($array['sunset_at_us'], $array['solar_noon_us']);
    }
}

