<?php

namespace Tests\Unit\Jyotish\Rules;

use App\Domain\Jyotish\Rules\AspectMatrixCalculator;
use App\Domain\Jyotish\Rules\GrahaDrishtiEvaluator;
use PHPUnit\Framework\TestCase;

/**
 * Unit Tests for Graha Drishti & Aspect Matrix Calculator (T-JY04-04).
 */
final class AspectMatrixCalculatorTest extends TestCase
{
    public function test_all_planets_cast_full_seventh_house_aspect(): void
    {
        foreach (AspectMatrixCalculator::GRAHAS as $graha) {
            $strength = GrahaDrishtiEvaluator::getAspectStrength($graha, 7);
            $this->assertEqualsWithDelta(1.0, $strength, 1e-4, "{$graha} must cast 100% aspect on 7th house.");
        }
    }

    public function test_special_aspects(): void
    {
        // Mars special aspects on 4th and 8th
        $this->assertEqualsWithDelta(1.0, GrahaDrishtiEvaluator::getAspectStrength('mars', 4), 1e-4);
        $this->assertEqualsWithDelta(1.0, GrahaDrishtiEvaluator::getAspectStrength('mars', 8), 1e-4);
        $this->assertEqualsWithDelta(0.25, GrahaDrishtiEvaluator::getAspectStrength('mars', 3), 1e-4);

        // Jupiter special aspects on 5th and 9th
        $this->assertEqualsWithDelta(1.0, GrahaDrishtiEvaluator::getAspectStrength('jupiter', 5), 1e-4);
        $this->assertEqualsWithDelta(1.0, GrahaDrishtiEvaluator::getAspectStrength('jupiter', 9), 1e-4);

        // Saturn special aspects on 3rd and 10th
        $this->assertEqualsWithDelta(1.0, GrahaDrishtiEvaluator::getAspectStrength('saturn', 3), 1e-4);
        $this->assertEqualsWithDelta(1.0, GrahaDrishtiEvaluator::getAspectStrength('saturn', 10), 1e-4);
    }

    public function test_aspect_matrix_structure(): void
    {
        // Sun in Mesha (0), Jupiter in Simha (4), Saturn in Tula (6)
        $rashis = [
            'sun' => 0,
            'jupiter' => 4,
            'saturn' => 6,
        ];

        $matrix = AspectMatrixCalculator::build($rashis, 0);

        $this->assertCount(9, $matrix['graha_to_bhavas']);
        $this->assertCount(12, $matrix['graha_to_bhavas']['sun']);

        // Sun in sign 0 casts 1.0 on house 7
        $this->assertEqualsWithDelta(1.0, $matrix['graha_to_bhavas']['sun'][7], 1e-4);

        // Jupiter in sign 4 (5th house from Lagna 0) casts 1.0 aspect on 9th house from it (Sign 0 / 1st house!)
        $this->assertEqualsWithDelta(1.0, $matrix['graha_to_bhavas']['jupiter'][1], 1e-4);
        // And Jupiter casts 1.0 aspect on Sun
        $this->assertEqualsWithDelta(1.0, $matrix['graha_to_grahas']['jupiter']['sun'], 1e-4);
    }
}

