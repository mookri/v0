<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Domain\Jyotish\Research\MundaneResearchWorkbench;
use PHPUnit\Framework\TestCase;

/**
 * Validates public safety prediction bans (T-JY10-10).
 * Asserts that Mundane/Samhita systems reject disaster or mass casualty forecasts.
 */
final class PublicSafetyPredictionBanTest extends TestCase
{
    public function test_disaster_prediction_queries_are_prohibited(): void
    {
        $cycle = ['saturn_transit' => 'kumbha', 'jupiter_transit' => 'mesha'];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Public Safety Policy: Predictions regarding disasters');

        MundaneResearchWorkbench::analyzeHistoricalCycle($cycle, 'Predict next major earthquake in 2027');
    }

    public function test_benign_historical_queries_are_permitted(): void
    {
        $cycle = ['saturn_transit' => 'kumbha', 'jupiter_transit' => 'mesha'];

        $result = MundaneResearchWorkbench::analyzeHistoricalCycle($cycle, 'Historical 60-year Samvatsara Jupiter-Saturn conjunctions');

        $this->assertTrue($result['safety_verified']);
        $this->assertSame('HISTORICAL_CORRELATION_ONLY', $result['mode']);
    }
}

