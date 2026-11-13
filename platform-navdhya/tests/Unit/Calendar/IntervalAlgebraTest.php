<?php

namespace Tests\Unit\Calendar;

use App\Domain\Calendar\Muhurat\ActivityRuleInterpreter;
use App\Domain\Calendar\Muhurat\IntervalAlgebra;
use App\Domain\Calendar\Panchang\MoonPhaseCalculator;
use App\Domain\Calendar\Panchang\PanchangDay;
use PHPUnit\Framework\TestCase;

class IntervalAlgebraTest extends TestCase
{
    public function test_interval_intersection(): void
    {
        // A: [100, 200), B: [150, 250) -> [150, 200)
        $a = [['starts_at_us' => 100, 'ends_at_us' => 200]];
        $b = [['starts_at_us' => 150, 'ends_at_us' => 250]];

        $res = IntervalAlgebra::intersect($a, $b);
        $this->assertCount(1, $res);
        $this->assertSame(150, $res[0]['starts_at_us']);
        $this->assertSame(200, $res[0]['ends_at_us']);
    }

    public function test_interval_subtraction_splits_overlapping_exclusion(): void
    {
        // Base: [100, 300)
        // Excluded: [180, 220)
        // Result: [100, 180) and [220, 300)
        $base = [['starts_at_us' => 100, 'ends_at_us' => 300]];
        $excluded = [['starts_at_us' => 180, 'ends_at_us' => 220]];

        $res = IntervalAlgebra::subtract($base, $excluded);
        $this->assertCount(2, $res);
        $this->assertSame(100, $res[0]['starts_at_us']);
        $this->assertSame(180, $res[0]['ends_at_us']);
        $this->assertSame(220, $res[1]['starts_at_us']);
        $this->assertSame(300, $res[1]['ends_at_us']);
    }

    public function test_activity_rule_interpreter_finds_auspicious_window(): void
    {
        // Construct a candidate day with Dwitiya (tithi 2) and Rohini (nakshatra 4)
        $sunriseUs = 1794272820 * 1_000_000;
        $sunsetUs = 1794312840 * 1_000_000;

        $day = new PanchangDay(
            localDate: '2026-11-10',
            placeId: 'delhi',
            profileCode: 'drik-lahiri-amanta',
            sunriseAtUs: $sunriseUs,
            sunsetAtUs: $sunsetUs,
            solarNoonUs: (int) (($sunriseUs + $sunsetUs) / 2),
            tithi: [
                'number' => 2,
                'name' => 'DWITIYA',
                'paksha' => 'SHUKLA',
                'starts_at_us' => $sunriseUs - 3_600_000_000,
                'ends_at_us' => $sunsetUs + 3_600_000_000,
            ],
            nakshatra: [
                'number' => 4,
                'name' => 'ROHINI',
                'pada' => 1,
                'starts_at_us' => $sunriseUs - 3_600_000_000,
                'ends_at_us' => $sunsetUs + 3_600_000_000,
            ],
            yoga: ['number' => 2, 'name' => 'PRITI', 'progress' => 0.5],
            karana: ['number' => 2, 'half_tithi' => 3, 'name' => 'BALAVA', 'is_vishti_bhadra' => false, 'is_fixed' => false],
            vara: ['civil_name' => 'MANGALAVARA', 'vedic_name' => 'MANGALAVARA', 'is_pre_sunrise' => false],
            masaAmanta: 'KARTIKA',
            masaPurnimanta: 'KARTIKA',
            isAdhika: false,
            moonPhase: MoonPhaseCalculator::calculate(15.0),
            confidence: 'HIGH'
        );

        $rahuKaalUs = [
            'starts_at_us' => $sunriseUs + 10_000_000_000,
            'ends_at_us' => $sunriseUs + 15_000_000_000,
        ];

        $evaluation = ActivityRuleInterpreter::evaluate('VIVAH', $day, $rahuKaalUs);
        $this->assertTrue($evaluation['is_eligible']);
        $this->assertNotEmpty($evaluation['windows']);
    }
}

