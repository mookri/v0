<?php

declare(strict_types=1);

namespace Tests\Unit\Jyotish\Dasha;

use App\Domain\Jyotish\Dasha\Systems\AshtottariCalculator;
use App\Domain\Jyotish\Dasha\Systems\YoginiCalculator;
use App\Domain\Jyotish\Dasha\Timeline\DashaTimelineIndex;
use App\Domain\Jyotish\Dasha\VimshottariCalculator;
use PHPUnit\Framework\TestCase;

final class VimshottariBalancePrecisionTest extends TestCase
{
    public function test_vimshottari_balance_and_total_span_preservation(): void
    {
        $calculator = new VimshottariCalculator();
        $birthDate = new \DateTimeImmutable('2000-01-01T12:00:00Z');

        // Moon in Ashwini at 0° 0' (exactly 0 elapsed => full Ketu 7 years)
        $periods0 = $calculator->calculate(0.0, $birthDate, maxLevels: 1);
        $this->assertCount(9, $periods0);
        $this->assertSame('ketu', $periods0[0]->lord);
        $this->assertEqualsWithDelta(7.0, $periods0[0]->durationYears, 1e-4);

        // Moon in Ashwini halfway: 6° 40' = 6.6666667° (exactly 50% elapsed => 3.5 years of Ketu)
        $periodsHalf = $calculator->calculate(6.6666667, $birthDate, maxLevels: 2);
        $this->assertCount(9, $periodsHalf);
        $this->assertSame('ketu', $periodsHalf[0]->lord);
        $this->assertEqualsWithDelta(3.5, $periodsHalf[0]->durationYears, 1e-4);

        // Verify subperiods exist and are continuous
        $antars = $periodsHalf[0]->subPeriods;
        $this->assertCount(9, $antars);
        $this->assertSame($periodsHalf[0]->startsAt->getTimestamp(), $antars[0]->startsAt->getTimestamp());
        $this->assertEqualsWithDelta($periodsHalf[0]->endsAt->getTimestamp(), $antars[8]->endsAt->getTimestamp(), 2);

        // Test timeline index binary search
        $queryDate = $birthDate->modify('+1 year');
        $active = DashaTimelineIndex::findActive($periodsHalf, $queryDate);
        $this->assertNotNull($active);
        $this->assertSame('ketu', $active->lord);
    }

    public function test_alternative_dasha_systems_are_independent_and_distinct(): void
    {
        $birthDate = new \DateTimeImmutable('2000-01-01T12:00:00Z');

        $ashtottari = new AshtottariCalculator();
        $periodsAsh = $ashtottari->calculate(0.0, $birthDate);
        $this->assertSame('ASHTOTTARI', $ashtottari->getSystemCode());
        $this->assertSame(108.0, $ashtottari->getTotalCycleYears());
        $this->assertCount(8, $periodsAsh);

        $yogini = new YoginiCalculator();
        $periodsYog = $yogini->calculate(0.0, $birthDate);
        $this->assertSame('YOGINI', $yogini->getSystemCode());
        $this->assertSame(36.0, $yogini->getTotalCycleYears());
        $this->assertCount(8, $periodsYog);
    }
}
