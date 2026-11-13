<?php

namespace Tests\Unit\Calendar;

use App\Domain\Calendar\Panchang\MoonPhaseCalculator;
use App\Domain\Calendar\Panchang\SankrantiCalculator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class RashiAndPhaseTest extends TestCase
{
    public function test_moon_phase_illumination_and_age(): void
    {
        // New Moon (elongation ~ 0°)
        $newMoon = MoonPhaseCalculator::calculate(0.0);
        $this->assertEqualsWithDelta(0.0, $newMoon['illumination_fraction'], 0.01);
        $this->assertEqualsWithDelta(0.0, $newMoon['age_days'], 0.1);
        $this->assertSame('NEW_MOON', $newMoon['phase_name']);

        // First Quarter (elongation ~ 90°)
        $firstQuarter = MoonPhaseCalculator::calculate(90.0);
        $this->assertEqualsWithDelta(0.5, $firstQuarter['illumination_fraction'], 0.01);
        $this->assertEqualsWithDelta(7.38, $firstQuarter['age_days'], 0.1);
        $this->assertSame('FIRST_QUARTER', $firstQuarter['phase_name']);

        // Full Moon (elongation ~ 180°)
        $fullMoon = MoonPhaseCalculator::calculate(180.0);
        $this->assertEqualsWithDelta(1.0, $fullMoon['illumination_fraction'], 0.01);
        $this->assertEqualsWithDelta(14.77, $fullMoon['age_days'], 0.1);
        $this->assertSame('FULL_MOON', $fullMoon['phase_name']);

        // Third Quarter (elongation ~ 270°)
        $thirdQuarter = MoonPhaseCalculator::calculate(270.0);
        $this->assertEqualsWithDelta(0.5, $thirdQuarter['illumination_fraction'], 0.01);
        $this->assertSame('THIRD_QUARTER', $thirdQuarter['phase_name']);
    }

    public function test_rashi_mapping_and_makar_sankranti(): void
    {
        // 0° = Mesha (Aries)
        $rashi0 = SankrantiCalculator::calculateRashi(0.5);
        $this->assertSame(1, $rashi0['number']);
        $this->assertSame('MESHA', $rashi0['name']);

        // 270° = Makara (Capricorn)
        $rashiMakara = SankrantiCalculator::calculateRashi(270.5);
        $this->assertSame(10, $rashiMakara['number']);
        $this->assertSame('MAKARA', $rashiMakara['name']);

        // Starting from early January 2027 (~Jan 10, Sun is in Dhanu ~266°), find next Sankranti (Makar Sankranti)
        $jan10 = CarbonImmutable::parse('2027-01-10T00:00:00Z');
        $sankranti = SankrantiCalculator::findNextSankranti($jan10);

        $this->assertSame(10, $sankranti['rashi_entered']);
        $this->assertSame('MAKARA', $sankranti['rashi_name']);
        // Makar Sankranti occurs around Jan 14-15
        $this->assertSame(2027, $sankranti['instant_utc']->year);
        $this->assertSame(1, $sankranti['instant_utc']->month);
        $this->assertContains($sankranti['instant_utc']->day, [14, 15]);
    }
}

