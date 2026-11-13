<?php

declare(strict_types=1);

namespace Tests\Unit\Jyotish\Muhurat;

use App\Domain\Jyotish\Muhurat\IntervalSearchEngine;
use PHPUnit\Framework\TestCase;

final class IntervalSearchPerformanceTest extends TestCase
{
    public function test_90_day_search_executes_within_performance_budget_o_days(): void
    {
        $engine = new IntervalSearchEngine();
        $startDate = new \DateTimeImmutable('2026-11-01T00:00:00Z');

        $t0 = microtime(true);
        $candidates = $engine->search(
            occasionCode: 'VIVAH',
            startDate: $startDate,
            daysCount: 90,
            latitude: 28.6139,
            longitude: 77.2090,
            timezone: 'Asia/Kolkata'
        );
        $elapsed = microtime(true) - $t0;

        $this->assertIsArray($candidates);
        $this->assertNotEmpty($candidates);
        // Acceptance criterion AC-T-JY07-01: <= 4.0 seconds for 90 days uncached
        $this->assertLessThan(4.0, $elapsed, "90-day search took {$elapsed}s exceeding 4.0s budget.");
    }
}

