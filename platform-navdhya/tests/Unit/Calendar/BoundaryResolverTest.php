<?php

namespace Tests\Unit\Calendar;

use App\Domain\Calendar\Astronomy\EventBoundaryResolver;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class BoundaryResolverTest extends TestCase
{
    public function test_continuous_tithi_microsecond_intervals_span_smoothly(): void
    {
        $base = CarbonImmutable::parse('2026-11-10T12:00:00Z');
        $interval = EventBoundaryResolver::resolveTithiInterval($base);

        $this->assertGreaterThan(0, $interval['tithi']);
        $this->assertLessThanOrEqual(30, $interval['tithi']);
        $this->assertNotEmpty($interval['name']);
        $this->assertGreaterThan($interval['starts_at_us'], $interval['ends_at_us']);

        // Check continuity with next instant past end boundary
        $nextInstant = CarbonImmutable::createFromTimestamp((int) ($interval['ends_at_us'] / 1_000_000))->addSeconds(60);
        $nextInterval = EventBoundaryResolver::resolveTithiInterval($nextInstant);

        $expectedNextTithi = ($interval['tithi'] % 30) + 1;
        $this->assertSame($expectedNextTithi, $nextInterval['tithi']);

        // The boundary difference between interval end and next interval start should be within 1 second (< 1,000,000 us)
        $diffUs = abs($interval['ends_at_us'] - $nextInterval['starts_at_us']);
        $this->assertLessThan(1_000_000, $diffUs, 'Tithi boundary should be continuous within root-finding tolerance');
    }

    public function test_continuous_nakshatra_microsecond_intervals(): void
    {
        $base = CarbonImmutable::parse('2026-11-10T12:00:00Z');
        $interval = EventBoundaryResolver::resolveNakshatraInterval($base);

        $this->assertGreaterThan(0, $interval['nakshatra']);
        $this->assertLessThanOrEqual(27, $interval['nakshatra']);
        $this->assertNotEmpty($interval['name']);
        $this->assertGreaterThanOrEqual(1, $interval['pada']);
        $this->assertLessThanOrEqual(4, $interval['pada']);
        $this->assertGreaterThan($interval['starts_at_us'], $interval['ends_at_us']);

        // Check continuity with next instant past end boundary
        $nextInstant = CarbonImmutable::createFromTimestamp((int) ($interval['ends_at_us'] / 1_000_000))->addSeconds(60);
        $nextInterval = EventBoundaryResolver::resolveNakshatraInterval($nextInstant);

        $expectedNextNak = ($interval['nakshatra'] % 27) + 1;
        $this->assertSame($expectedNextNak, $nextInterval['nakshatra']);

        $diffUs = abs($interval['ends_at_us'] - $nextInterval['starts_at_us']);
        $this->assertLessThan(1_000_000, $diffUs, 'Nakshatra boundary should be continuous within root-finding tolerance');
    }
}

