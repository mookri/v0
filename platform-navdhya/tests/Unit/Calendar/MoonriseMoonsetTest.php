<?php

namespace Tests\Unit\Calendar;

use App\Domain\Calendar\Astronomy\MoonriseUtcCalculator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class MoonriseMoonsetTest extends TestCase
{
    public function test_moonrise_and_moonset_in_delhi(): void
    {
        // Delhi: 28.6139° N, 77.2090° E
        $lat = 28.6139;
        $lng = 77.2090;

        $result = MoonriseUtcCalculator::calculate(2026, 11, 10, $lat, $lng);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('moonrise', $result);
        $this->assertArrayHasKey('moonset', $result);
        $this->assertArrayHasKey('has_moonrise', $result);
        $this->assertArrayHasKey('has_moonset', $result);

        if ($result['has_moonrise']) {
            $this->assertInstanceOf(CarbonImmutable::class, $result['moonrise']);
            // Convert to Delhi local time (UTC+5:30)
            $localRise = $result['moonrise']->setTimezone('Asia/Kolkata');
            $this->assertSame(2026, $localRise->year);
        }

        if ($result['has_moonset']) {
            $this->assertInstanceOf(CarbonImmutable::class, $result['moonset']);
            $localSet = $result['moonset']->setTimezone('Asia/Kolkata');
            $this->assertSame(2026, $localSet->year);
        }
    }

    public function test_moonrise_and_moonset_in_varanasi(): void
    {
        // Varanasi: 25.3176° N, 82.9739° E
        $lat = 25.3176;
        $lng = 82.9739;

        $result = MoonriseUtcCalculator::calculate(2026, 11, 10, $lat, $lng);

        $this->assertIsArray($result);
        $this->assertTrue($result['has_moonrise'] || $result['has_moonset']);
    }
}

