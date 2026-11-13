<?php

namespace Tests\Unit\Jyotish\Rules;

use App\Domain\Jyotish\Rules\ConjunctionAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * Unit Tests for Planetary Conjunction (Graha Yuti) Analyzer (T-JY04-05).
 */
final class ConjunctionsTest extends TestCase
{
    public function test_same_sign_and_tight_conjunction(): void
    {
        $planets = [
            'sun' => 15.2,
            'mercury' => 15.8, // 0.6° separation in Mesha -> tight conjunction
            'jupiter' => 120.0,
        ];

        $conjunctions = ConjunctionAnalyzer::analyze($planets);

        $this->assertNotEmpty($conjunctions);
        $sunMerc = $conjunctions[0];
        $this->assertSame('sun', $sunMerc['planet_a']);
        $this->assertSame('mercury', $sunMerc['planet_b']);
        $this->assertTrue($sunMerc['same_rashi']);
        $this->assertSame(0, $sunMerc['rashi_index']);
        $this->assertSame('tight', $sunMerc['tightness']);
        $this->assertEqualsWithDelta(0.6, $sunMerc['separation_degrees'], 1e-4);
    }

    public function test_cross_sign_border_wraparound_conjunction(): void
    {
        // Planet A at 359.5° (Meena 29° 30')
        // Planet B at 0.5° (Mesha 0° 30')
        // Separation across 360°/0° border is 1.0° (tight conjunction across sign borders!)
        $planets = [
            'mars' => 359.5,
            'venus' => 0.5,
        ];

        $conjunctions = ConjunctionAnalyzer::analyze($planets);

        $this->assertCount(1, $conjunctions);
        $conj = $conjunctions[0];
        $this->assertFalse($conj['same_rashi']); // Different signs
        $this->assertNull($conj['rashi_index']);
        $this->assertSame('tight', $conj['tightness']);
        $this->assertEqualsWithDelta(1.0, $conj['separation_degrees'], 1e-4);
    }
}

