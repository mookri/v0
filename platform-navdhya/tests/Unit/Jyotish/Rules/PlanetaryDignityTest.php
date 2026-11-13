<?php

namespace Tests\Unit\Jyotish\Rules;

use App\Domain\Jyotish\Rules\AvasthaEvaluator;
use App\Domain\Jyotish\Rules\DignityEngine;
use PHPUnit\Framework\TestCase;

/**
 * Unit Tests for Classical Parashari Dignities and Avasthas (T-JY04-01).
 */
final class PlanetaryDignityTest extends TestCase
{
    public function test_sun_exaltation_and_debilitation(): void
    {
        // Sun deep exaltation at Mesha 10° (longitude 10.0°)
        $exaltedSun = DignityEngine::evaluate('sun', 10.0);
        $this->assertSame('exalted', $exaltedSun['dignity']);
        $this->assertSame(0, $exaltedSun['rashi_index']);
        $this->assertEqualsWithDelta(1.0, $exaltedSun['exaltation_score'], 1e-4);

        // Sun deep debilitation at Tula 10° (longitude 190.0°)
        $debilitatedSun = DignityEngine::evaluate('sun', 190.0);
        $this->assertSame('debilitated', $debilitatedSun['dignity']);
        $this->assertSame(6, $debilitatedSun['rashi_index']);
        $this->assertEqualsWithDelta(0.0, $debilitatedSun['exaltation_score'], 1e-4);
    }

    public function test_all_seven_classical_planets_exaltation_signs(): void
    {
        $cases = [
            'sun' => 10.0,      // Mesha 10°
            'moon' => 33.0,     // Vrishabha 3°
            'mars' => 298.0,    // Makara 28°
            'mercury' => 165.0, // Kanya 15°
            'jupiter' => 95.0,  // Karkataka 5°
            'venus' => 357.0,   // Meena 27°
            'saturn' => 200.0,  // Tula 20°
        ];

        foreach ($cases as $planet => $deg) {
            $eval = DignityEngine::evaluate($planet, $deg);
            $this->assertSame('exalted', $eval['dignity'], "{$planet} at {$deg}° must be exalted.");
        }
    }

    public function test_moolatrikona_vs_own_sign(): void
    {
        // Mars in Mesha (sign 0): 0°-12° is Moolatrikona; 12°-30° is Swakshetra (Own sign)
        $moolaMars = DignityEngine::evaluate('mars', 5.0);
        $this->assertSame('moolatrikona', $moolaMars['dignity']);

        $ownMars = DignityEngine::evaluate('mars', 20.0);
        $this->assertSame('own_sign', $ownMars['dignity']);
    }

    public function test_compound_relationship_panchadha_sambandha(): void
    {
        // Mars natural relationship with Mercury is enemy.
        // If Mercury is placed in the 2nd sign from Mars (temporal friend):
        // Enemy + Friend = Sama (Neutral)
        $allRashis = ['mars' => 0, 'mercury' => 1];
        $eval = DignityEngine::evaluate('mars', 45.0, $allRashis); // Mars in Vrishabha (Venus lord)
        $this->assertNotEmpty($eval['compound_relation']);
    }

    public function test_baladi_and_jagradadi_avasthas(): void
    {
        // Odd sign (Mesha / 0): 0-6° is Bala, 12-18° is Yuva
        $balaOdd = AvasthaEvaluator::evaluate('mars', 4.0, 'own_sign');
        $this->assertSame('bala', $balaOdd['baladi']['code']);
        $this->assertSame('jagrita', $balaOdd['jagradadi']['code']);
        $this->assertSame('swastha', $balaOdd['deeptadi']['code']);

        $yuvaOdd = AvasthaEvaluator::evaluate('mars', 15.0, 'own_sign');
        $this->assertSame('yuva', $yuvaOdd['baladi']['code']);
        $this->assertEqualsWithDelta(1.0, $yuvaOdd['baladi']['potency'], 1e-4);

        // Even sign (Vrishabha / 1): 0-6° is Mrita (inverted order)
        $mritaEven = AvasthaEvaluator::evaluate('moon', 32.0, 'exalted');
        $this->assertSame('mrita', $mritaEven['baladi']['code']);
        $this->assertEqualsWithDelta(0.0, $mritaEven['baladi']['potency'], 1e-4);
        $this->assertSame('deepta', $mritaEven['deeptadi']['code']);
    }
}

