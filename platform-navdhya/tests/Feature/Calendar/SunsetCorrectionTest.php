<?php

namespace Tests\Feature\Calendar;

use App\Domain\Calendar\Astronomy\SunsetUtcCalculator;
use App\Domain\Calendar\Panchang\PanchangCalculator;
use Carbon\CarbonImmutable;
use Tests\TestCase;

final class SunsetCorrectionTest extends TestCase
{
    /**
     * Verifies that computed sunset is dynamic and accurate across different seasons and places,
     * proving that sunset_at_us is not a constant sunrise + 12 hours (Fixes D-1).
     */
    public function test_sunset_differs_from_sunrise_plus_12h_away_from_equinox(): void
    {
        $testCases = [
            // Ujjain in Summer Solstice (June 21, 2026): daylight is > 13.5 hours
            ['year' => 2026, 'month' => 6, 'day' => 21, 'lat' => 23.1765, 'lon' => 75.7885],
            // Ujjain in Winter Solstice (December 21, 2026): daylight is < 10.7 hours
            ['year' => 2026, 'month' => 12, 'day' => 21, 'lat' => 23.1765, 'lon' => 75.7885],
            // Delhi in Summer (June 21, 2026): latitude 28.6139
            ['year' => 2026, 'month' => 6, 'day' => 21, 'lat' => 28.6139, 'lon' => 77.2090],
            // Delhi in Winter (December 21, 2026)
            ['year' => 2026, 'month' => 12, 'day' => 21, 'lat' => 28.6139, 'lon' => 77.2090],
            // Varanasi (November 13, 2026)
            ['year' => 2026, 'month' => 11, 'day' => 13, 'lat' => 25.3176, 'lon' => 82.9739],
        ];

        foreach ($testCases as $tc) {
            $occ = PanchangCalculator::calculate($tc['year'], $tc['month'], $tc['day'], $tc['lat'], $tc['lon']);
            $sunrise = CarbonImmutable::parse($occ->sunriseUtc);
            $sunriseUs = (int) ($sunrise->getTimestamp() * 1_000_000 + $sunrise->micro);

            $sunsetRes = SunsetUtcCalculator::calculate($tc['year'], $tc['month'], $tc['day'], $tc['lat'], $tc['lon']);
            $this->assertTrue($sunsetRes->isSupported());
            $this->assertNotNull($sunsetRes->instant_utc);

            $sunsetUs = (int) ($sunsetRes->instant_utc->getTimestamp() * 1_000_000 + $sunsetRes->instant_utc->micro);
            $daylightDurationUs = $sunsetUs - $sunriseUs;

            // In summer solstice, daylight is significantly more than 12h (43,200s); in winter, less
            $deltaFrom12hSeconds = ($daylightDurationUs - (12 * 3600 * 1_000_000)) / 1_000_000;

            if ($tc['month'] === 6) {
                // Summer daylight must exceed 12h by at least 1 hour (3600s)
                $this->assertGreaterThan(3600, $deltaFrom12hSeconds);
            } elseif ($tc['month'] === 12) {
                // Winter daylight must be less than 12h by at least 1 hour (-3600s)
                $this->assertLessThan(-3600, $deltaFrom12hSeconds);
            }
        }
    }
}

