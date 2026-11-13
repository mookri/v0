<?php

declare(strict_types=1);

namespace Tests\Unit\Jyotish\Muhurat;

use App\Domain\Jyotish\Muhurat\IntervalSearchEngine;
use PHPUnit\Framework\TestCase;

final class OccasionRulesValidationTest extends TestCase
{
    private IntervalSearchEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new IntervalSearchEngine();
    }

    public function test_interval_search_completes_and_returns_ranked_auspicious_candidates(): void
    {
        $startDate = new \DateTimeImmutable('2026-11-01T00:00:00Z');
        $candidates = $this->engine->search(
            occasionCode: 'VIVAH',
            startDate: $startDate,
            daysCount: 14,
            latitude: 28.6139,
            longitude: 77.2090,
            timezone: 'Asia/Kolkata'
        );

        $this->assertNotEmpty($candidates, 'Should find at least some auspicious Vivah windows in 14 days.');

        foreach ($candidates as $cand) {
            $this->assertSame('VIVAH', $cand->occasionCode);
            $this->assertFalse($cand->isDisqualified);
            $this->assertGreaterThanOrEqual(50.0, $cand->score);
            $this->assertNotEmpty($cand->reasons);
            $this->assertEmpty($cand->exclusions, 'Auspicious candidate must have zero disqualifying exclusions.');
        }

        // Verify descending sort by score
        for ($i = 0; $i < count($candidates) - 1; $i++) {
            $this->assertGreaterThanOrEqual($candidates[$i + 1]->score, $candidates[$i]->score);
        }
    }

    public function test_griha_pravesh_and_business_opening_search(): void
    {
        $startDate = new \DateTimeImmutable('2026-11-01T00:00:00Z');
        $gpCandidates = $this->engine->search('GRIHA_PRAVESH', $startDate, 7, 28.6139, 77.2090);
        $bizCandidates = $this->engine->search('BUSINESS_OPENING', $startDate, 7, 28.6139, 77.2090);

        $this->assertIsArray($gpCandidates);
        $this->assertIsArray($bizCandidates);
    }
}
