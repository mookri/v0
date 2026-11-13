<?php

namespace Tests\Unit\Jyotish\Boundaries;

use App\Domain\Jyotish\Chart\BoundaryIndexer;
use PHPUnit\Framework\TestCase;

/**
 * Zodiac, Nakshatra & Pada Boundary Tests (T-JY02-05).
 * Enforces AC-T-JY02-05:
 *  - 0° -> Mesha, Ashwini Pada 1 ('Chu')
 *  - 30° -> Vrishabha, Krittika Pada 2 ('Ee')
 *  - 360° -> Mesha, Ashwini Pada 1 ('Chu')
 *  - 108 Pada contiguous monotonicity
 */
final class ZodiacBoundaryTest extends TestCase
{
    public function test_zero_degree_boundary(): void
    {
        $indexed = BoundaryIndexer::index(0.0);

        $this->assertSame(0, $indexed['rashi']['index']);
        $this->assertSame('Mesha', $indexed['rashi']['name']);
        $this->assertSame('mars', $indexed['rashi']['lord']);
        $this->assertEqualsWithDelta(0.0, $indexed['rashi']['degrees_in_rashi'], 1e-9);

        $this->assertSame(0, $indexed['nakshatra']['index']);
        $this->assertSame('Ashwini', $indexed['nakshatra']['name']);
        $this->assertSame(1, $indexed['nakshatra']['pada']);
        $this->assertSame(0, $indexed['nakshatra']['absolute_pada']);
        $this->assertSame('Chu', $indexed['nakshatra']['syllable']);
    }

    public function test_thirty_degree_rashi_boundary(): void
    {
        $justBefore = BoundaryIndexer::index(29.999999);
        $this->assertSame(0, $justBefore['rashi']['index']);
        $this->assertSame('Mesha', $justBefore['rashi']['name']);

        $exactThirty = BoundaryIndexer::index(30.0);
        $this->assertSame(1, $exactThirty['rashi']['index']);
        $this->assertSame('Vrishabha', $exactThirty['rashi']['name']);
        $this->assertSame('venus', $exactThirty['rashi']['lord']);
        $this->assertEqualsWithDelta(0.0, $exactThirty['rashi']['degrees_in_rashi'], 1e-9);

        // At 30°, Ashwini (0..13.33) and Bharani (13.33..26.66) are complete; Krittika spans 26.66..40.0
        // Krittika Pada 1 is in Mesha (26° 40' - 30° 00'); Krittika Pada 2 begins at 30° 00' in Vrishabha!
        $this->assertSame(2, $exactThirty['nakshatra']['index']);
        $this->assertSame('Krittika', $exactThirty['nakshatra']['name']);
        $this->assertSame(2, $exactThirty['nakshatra']['pada']);
        $this->assertSame('Ee', $exactThirty['nakshatra']['syllable']);
    }

    public function test_360_degree_wrap_around(): void
    {
        $wrapped = BoundaryIndexer::index(360.0);
        $this->assertSame(0, $wrapped['rashi']['index']);
        $this->assertSame('Mesha', $wrapped['rashi']['name']);
        $this->assertSame(0, $wrapped['nakshatra']['index']);
        $this->assertSame(1, $wrapped['nakshatra']['pada']);

        $excess = BoundaryIndexer::index(390.0); // 390 mod 360 = 30
        $this->assertSame(1, $excess['rashi']['index']);
        $this->assertSame('Vrishabha', $excess['rashi']['name']);
    }

    public function test_all_108_padas_are_contiguous_and_ordered(): void
    {
        for ($pada = 0; $pada < 108; $pada++) {
            $midDegrees = ($pada * (10.0 / 3.0)) + 0.5;
            $indexed = BoundaryIndexer::index($midDegrees);

            $this->assertSame(
                $pada,
                $indexed['nakshatra']['absolute_pada'],
                "Pada mismatch at index {$pada} (degrees: {$midDegrees})"
            );

            $this->assertNotEmpty($indexed['nakshatra']['syllable']);
        }
    }
}

