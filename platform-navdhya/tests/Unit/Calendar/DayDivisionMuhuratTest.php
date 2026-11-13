<?php

namespace Tests\Unit\Calendar;

use App\Domain\Calendar\Astronomy\SunriseUtcCalculator;
use App\Domain\Calendar\Astronomy\SunsetUtcCalculator;
use App\Domain\Calendar\Muhurat\AbhijitMuhurat;
use App\Domain\Calendar\Muhurat\ChoghadiyaCalculator;
use App\Domain\Calendar\Muhurat\DayDivision;
use App\Domain\Calendar\Muhurat\HoraCalculator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class DayDivisionMuhuratTest extends TestCase
{
    public function test_daylight_octants_and_muhurtas_span_daylight_completely(): void
    {
        $sunrise = CarbonImmutable::parse('2026-11-10T01:07:00Z');
        $sunset = CarbonImmutable::parse('2026-11-10T12:14:00Z');

        $octants = DayDivision::daylightOctants($sunrise, $sunset);
        $this->assertCount(8, $octants);
        $this->assertSame($sunrise->getTimestamp(), $octants[0]['starts_at']->getTimestamp());
        $this->assertSame($sunset->getTimestamp(), $octants[7]['ends_at']->getTimestamp());

        // Check continuity of octants
        for ($i = 0; $i < 7; $i++) {
            $this->assertSame($octants[$i]['ends_at']->getTimestamp(), $octants[$i + 1]['starts_at']->getTimestamp());
        }

        $muhurtas = DayDivision::daylightMuhurtas($sunrise, $sunset);
        $this->assertCount(15, $muhurtas);
        $this->assertSame($sunrise->getTimestamp(), $muhurtas[0]['starts_at']->getTimestamp());
        $this->assertSame($sunset->getTimestamp(), $muhurtas[14]['ends_at']->getTimestamp());
    }

    public function test_rahu_kaal_varies_by_weekday_and_location(): void
    {
        // 2026-11-10 (Tuesday, weekday 2): Rahu Kaal is 7th part
        $sunriseUjjain = SunriseUtcCalculator::calculate(2026, 11, 10, 23.1765, 75.7885)->instant_utc;
        $sunsetUjjain = SunsetUtcCalculator::calculate(2026, 11, 10, 23.1765, 75.7885)->instant_utc;

        $rahuUjjain = DayDivision::rahuKaal($sunriseUjjain, $sunsetUjjain, 2);
        $this->assertSame('RAHU_KAAL', $rahuUjjain['name']);

        // Chennai: 13.0827° N, 80.2707° E
        $sunriseChennai = SunriseUtcCalculator::calculate(2026, 11, 10, 13.0827, 80.2707)->instant_utc;
        $sunsetChennai = SunsetUtcCalculator::calculate(2026, 11, 10, 13.0827, 80.2707)->instant_utc;
        $rahuChennai = DayDivision::rahuKaal($sunriseChennai, $sunsetChennai, 2);

        // Chennai and Ujjain have different longitudes, so their Rahu Kaal starts at different UTC timestamps
        $this->assertNotSame($rahuUjjain['starts_at_us'], $rahuChennai['starts_at_us']);
    }

    public function test_abhijit_muhurat_properties_and_wednesday_dosha(): void
    {
        $sunrise = CarbonImmutable::parse('2026-11-10T06:00:00Z');
        $sunset = CarbonImmutable::parse('2026-11-10T18:00:00Z');

        // Tuesday (weekday 2) -> not dosha afflicted
        $tuesdayAbhijit = AbhijitMuhurat::calculate($sunrise, $sunset, 2);
        $this->assertFalse($tuesdayAbhijit['is_dosha_afflicted']);
        $this->assertNull($tuesdayAbhijit['dosha_reason']);

        // 8th muhurta of a 12-hour day (each is 48 mins):
        // 7 * 48 min = 336 min = 5h 36m after sunrise (11:36)
        // 8 * 48 min = 384 min = 6h 24m after sunrise (12:24)
        // Solar noon is at 12:00 -> Abhijit is centered exactly at 12:00 (11:36 to 12:24)!
        $this->assertSame('2026-11-10 11:36:00', $tuesdayAbhijit['starts_at']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-10 12:24:00', $tuesdayAbhijit['ends_at']->format('Y-m-d H:i:s'));

        // Wednesday (weekday 3) -> dosha afflicted
        $wednesdayAbhijit = AbhijitMuhurat::calculate($sunrise, $sunset, 3);
        $this->assertTrue($wednesdayAbhijit['is_dosha_afflicted']);
        $this->assertNotNull($wednesdayAbhijit['dosha_reason']);
    }

    public function test_choghadiya_daylight_sequence(): void
    {
        $sunrise = CarbonImmutable::parse('2026-11-10T06:00:00Z');
        $sunset = CarbonImmutable::parse('2026-11-10T18:00:00Z');

        // Tuesday (weekday 2): starts with ROGA
        $choghadiyas = ChoghadiyaCalculator::daylight($sunrise, $sunset, 2);
        $this->assertCount(8, $choghadiyas);
        $this->assertSame('ROGA', $choghadiyas[0]['name']);
        $this->assertSame('INAUSPICIOUS', $choghadiyas[0]['nature']);
    }

    public function test_hora_daylight_sequence(): void
    {
        $sunrise = CarbonImmutable::parse('2026-11-10T06:00:00Z');
        $sunset = CarbonImmutable::parse('2026-11-10T18:00:00Z');

        // Sunday (weekday 0): 1st hora is SUN
        $horasSun = HoraCalculator::daylight($sunrise, $sunset, 0);
        $this->assertCount(12, $horasSun);
        $this->assertSame('SUN', $horasSun[0]['lord']);
        $this->assertSame('VENUS', $horasSun[1]['lord']); // Chaldean next
        $this->assertSame('MERCURY', $horasSun[2]['lord']);
    }
}

