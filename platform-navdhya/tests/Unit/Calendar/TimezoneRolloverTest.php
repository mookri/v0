<?php

namespace Tests\Unit\Calendar;

use App\Domain\Calendar\Astronomy\SunriseUtcCalculator;
use App\Domain\Calendar\Panchang\PanchangCalculator;
use Carbon\CarbonImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class TimezoneRolloverTest extends TestCase
{
    public function test_tokyo_sunrise_utc_lands_on_previous_civil_day_correctly(): void
    {
        // Tokyo: 35.6762° N, 139.6503° E
        // Local sunrise on Nov 10, 2026 is ~06:15 JST (UTC+9).
        // In UTC, this is Nov 9, 2026 ~21:15 UTC.
        $result = SunriseUtcCalculator::calculate(2026, 11, 10, 35.6762, 139.6503);

        $this->assertTrue($result->isSupported());
        $this->assertNotNull($result->instant_utc);

        // Instant must have date 2026-11-09 in UTC, NOT 2026-11-10 (closes P-06 date stamping defect)
        $this->assertSame('2026-11-09', $result->instant_utc->format('Y-m-d'));
        $this->assertSame(21, (int) $result->instant_utc->format('H'));

        // PanchangCalculator ISO string must match the true UTC instant
        $panchang = PanchangCalculator::calculate(2026, 11, 10, 35.6762, 139.6503);
        $this->assertStringStartsWith('2026-11-09T21:', $panchang->sunriseUtc);
    }

    public function test_microsecond_timeline_arithmetic_advances_date_without_hour_modulo_bug(): void
    {
        // Construct near-midnight UTC calculation to test rollover handling
        $year = 2026;
        $month = 12;
        $day = 31;

        // Longitude 170° E (UTC+11.3) with sunrise around 05:00 local time
        // Local 05:00 - 11.33 hours = 17:40 previous UTC day.
        $resultEast = SunriseUtcCalculator::calculate($year, $month, $day, -45.0, 170.0);
        $this->assertSame('2026-12-30', $resultEast->instant_utc->format('Y-m-d'));

        // Longitude 150° W (UTC-10) with sunrise around 06:45 local time
        // Local 06:45 + 10.0 hours = 16:45 same UTC day.
        $resultWest = SunriseUtcCalculator::calculate($year, $month, $day, 21.3, -157.8);
        $this->assertSame('2026-12-31', $resultWest->instant_utc->format('Y-m-d'));
    }

    public function test_server_side_iana_timezone_identifier_validation(): void
    {
        $validTimezones = ['Asia/Kolkata', 'America/New_York', 'UTC', 'Europe/London'];
        $allIana = DateTimeZone::listIdentifiers();

        foreach ($validTimezones as $tz) {
            $this->assertContains($tz, $allIana);
        }

        // Malformed or non-IANA timezones must not be in list
        $this->assertNotContains('Asia/Calcutta_Old_Fake', $allIana);
        $this->assertNotContains('GMT+5:30', $allIana);
    }
}

