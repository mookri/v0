<?php

namespace Tests\Unit\Calendar;

use App\Domain\Calendar\Astronomy\HorizonResult;
use App\Domain\Calendar\Astronomy\HorizonStatusEnum;
use App\Domain\Calendar\Astronomy\SunriseUtcCalculator;
use App\Domain\Calendar\Exceptions\UnsupportedHorizonException;
use App\Domain\Calendar\Panchang\PanchangCalculator;
use PHPUnit\Framework\TestCase;

final class PolarHorizonTest extends TestCase
{
    public function test_svalbard_summer_solstice_is_midnight_sun(): void
    {
        // Svalbard: 78.2232° N, 15.6267° E on June 21, 2026
        $result = SunriseUtcCalculator::calculate(2026, 6, 21, 78.2232, 15.6267);

        $this->assertInstanceOf(HorizonResult::class, $result);
        $this->assertSame(HorizonStatusEnum::MIDNIGHT_SUN, $result->status);
        $this->assertFalse($result->isSupported());
        $this->assertFalse($result->is_risen);
        $this->assertNull($result->instant_utc);
        $this->assertNull($result->hour);
    }

    public function test_svalbard_winter_solstice_is_polar_night(): void
    {
        // Svalbard: 78.2232° N, 15.6267° E on December 21, 2026
        $result = SunriseUtcCalculator::calculate(2026, 12, 21, 78.2232, 15.6267);

        $this->assertInstanceOf(HorizonResult::class, $result);
        $this->assertSame(HorizonStatusEnum::POLAR_NIGHT, $result->status);
        $this->assertFalse($result->isSupported());
        $this->assertFalse($result->is_risen);
        $this->assertNull($result->instant_utc);
        $this->assertNull($result->hour);
    }

    public function test_unsupported_latitude_beyond_poles(): void
    {
        $result = SunriseUtcCalculator::calculate(2026, 11, 10, 95.0, 75.7885);
        $this->assertSame(HorizonStatusEnum::UNSUPPORTED_LATITUDE, $result->status);
        $this->assertFalse($result->isSupported());
    }

    public function test_panchang_calculator_refuses_to_emit_day_for_polar_regions(): void
    {
        $this->expectException(UnsupportedHorizonException::class);
        $this->expectExceptionMessage('astronomical horizon is midnight_sun (P-05 polar refusal)');

        PanchangCalculator::calculate(2026, 6, 21, 78.2232, 15.6267);
    }

    public function test_panchang_calculator_refuses_to_emit_day_for_polar_night(): void
    {
        $this->expectException(UnsupportedHorizonException::class);
        $this->expectExceptionMessage('astronomical horizon is polar_night (P-05 polar refusal)');

        PanchangCalculator::calculate(2026, 12, 21, 78.2232, 15.6267);
    }

    public function test_normal_latitude_returns_supported_horizon(): void
    {
        $result = SunriseUtcCalculator::calculate(2026, 11, 10, 23.1765, 75.7885);

        $this->assertSame(HorizonStatusEnum::NORMAL, $result->status);
        $this->assertTrue($result->isSupported());
        $this->assertTrue($result->is_risen);
        $this->assertNotNull($result->instant_utc);
        $this->assertNotNull($result->hour);
        $this->assertSame($result->hour, $result['hour']);
    }
}

