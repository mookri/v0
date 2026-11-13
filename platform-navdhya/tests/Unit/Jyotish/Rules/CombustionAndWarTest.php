<?php

namespace Tests\Unit\Jyotish\Rules;

use App\Domain\Jyotish\Rules\CombustionCalculator;
use App\Domain\Jyotish\Rules\PlanetaryWarResolver;
use PHPUnit\Framework\TestCase;

/**
 * Unit Tests for Combustion (Asta) & Planetary War (Graha Yuddha) (T-JY04-03).
 */
final class CombustionAndWarTest extends TestCase
{
    public function test_combustion_orbs_and_deep_combustion(): void
    {
        $sunLon = 45.0; // Vrishabha 15°

        // Mars orb is 17°: at 10° distance, it is combust
        $combustMars = CombustionCalculator::evaluate('mars', 55.0, $sunLon, false);
        $this->assertTrue($combustMars['is_combust']);
        $this->assertFalse($combustMars['is_deeply_combust']);
        $this->assertEqualsWithDelta(10.0, $combustMars['angular_separation'], 1e-4);

        // Mars at 0.5° distance: deep combustion (Param Asta)
        $deepMars = CombustionCalculator::evaluate('mars', 45.5, $sunLon, false);
        $this->assertTrue($deepMars['is_combust']);
        $this->assertTrue($deepMars['is_deeply_combust']);

        // Mars at 20° distance: free from combustion
        $freeMars = CombustionCalculator::evaluate('mars', 65.0, $sunLon, false);
        $this->assertFalse($freeMars['is_combust']);
    }

    public function test_rahu_and_ketu_are_immune_to_combustion(): void
    {
        $resRahu = CombustionCalculator::evaluate('rahu', 45.1, 45.0);
        $this->assertFalse($resRahu['is_combust']);
        $this->assertSame('immune', $resRahu['status']);
    }

    public function test_planetary_war_venus_always_prevails(): void
    {
        // Venus at 100.0°, Mars at 100.5° (separation 0.5° < 1.0° war orb)
        // Even if Mars has higher latitude, Venus always wins (Bhrigu Jayam)
        $war = PlanetaryWarResolver::resolvePair(
            'venus',
            ['longitude' => 100.0, 'latitude' => -1.5],
            'mars',
            ['longitude' => 100.5, 'latitude' => 2.0]
        );

        $this->assertSame('venus', $war['winner']);
        $this->assertSame('mars', $war['loser']);
        $this->assertStringContainsString('Venus radiant superiority', $war['victory_reason']);
    }

    public function test_planetary_war_northern_latitude_prevails(): void
    {
        // Jupiter vs Saturn war (< 1.0° separation)
        // Jupiter at latitude +1.2°, Saturn at latitude -0.8°
        $war = PlanetaryWarResolver::resolvePair(
            'jupiter',
            ['longitude' => 210.0, 'latitude' => 1.2],
            'saturn',
            ['longitude' => 210.6, 'latitude' => -0.8]
        );

        $this->assertSame('jupiter', $war['winner']);
        $this->assertSame('saturn', $war['loser']);
        $this->assertStringContainsString('Northern celestial latitude', $war['victory_reason']);
    }
}

