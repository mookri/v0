<?php

namespace Tests\Unit\Jyotish\Astronomy;

use App\Domain\Jyotish\Astronomy\Time\AstronomicalTime;
use App\Domain\Jyotish\Astronomy\Time\JdTt;
use App\Domain\Jyotish\Astronomy\Time\JdUtc;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * Astronomical Time Scale Conversion Unit Test (T-JY01-03).
 * Verifies AC-T-JY01-03:
 *  - Type safety between JdUtc, JdUt1, JdTt (TypeError on mismatch).
 *  - Delta T offset precision (< 1ms temporal delta).
 *  - Invariant property over multiple epochs.
 */
final class TimeScaleConversionTest extends TestCase
{
    public function test_type_safety_prevents_implicit_scale_mixing(): void
    {
        $time = AstronomicalTime::fromUtc(2026, 3, 20, 12, 0, 0);

        $consumer = function (JdTt $tt): float {
            return $tt->value;
        };

        // Correct invocation
        $this->assertIsFloat($consumer($time->tt));

        // Incorrect invocation with JdUtc must raise TypeError
        $this->expectException(TypeError::class);
        /** @phpstan-ignore-next-line */
        $consumer($time->utc);
    }

    public function test_delta_t_accuracy_and_continuity_across_epochs(): void
    {
        // 2026 epoch: Delta T ~ 75 seconds
        $time2026 = AstronomicalTime::fromUtc(2026, 1, 1, 0, 0, 0);
        $this->assertEqualsWithDelta(75.0, $time2026->deltaTSeconds, 2.0);

        $jdDeltaDays = $time2026->tt->value - $time2026->utc->value;
        $deltaTFromJd = $jdDeltaDays * 86400.0;
        $this->assertEqualsWithDelta($time2026->deltaTSeconds, $deltaTFromJd, 0.001); // < 1ms accuracy

        // J2000 epoch: Delta T ~ 63.8s
        $time2000 = AstronomicalTime::fromUtc(2000, 1, 1, 12, 0, 0);
        $this->assertEqualsWithDelta(63.8, $time2000->deltaTSeconds, 1.5);
    }

    public function test_datetime_immutable_utc_conversion(): void
    {
        $dt = new DateTimeImmutable('2026-11-24T12:00:00.000Z', new DateTimeZone('UTC'));
        $time = AstronomicalTime::fromUtcDateTime($dt);

        $this->assertEqualsWithDelta(2461369.0, $time->utc->value, 0.0001);
        $this->assertGreaterThan($time->utc->value, $time->tt->value);
    }
}

