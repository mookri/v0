<?php

declare(strict_types=1);

namespace Tests\Unit\Jyotish\Catalogue;

use App\Domain\Jyotish\Calendar\SamvatConverter;
use App\Domain\Jyotish\Chart\SpecialLagnaCalculator;
use App\Domain\Jyotish\Ephemeris\UpagrahaCalculator;
use PHPUnit\Framework\TestCase;

final class SpecialLagnasAndUpagrahasTest extends TestCase
{
    public function test_special_lagnas_deterministic_progression(): void
    {
        $calculator = new SpecialLagnaCalculator();
        $sunLong = 210.0;
        $elapsedHours = 4.0;
        $lagnaDeg = 245.0;
        $moonDeg = 120.0;

        $results = $calculator->calculateAll($sunLong, $elapsedHours, $lagnaDeg, $moonDeg);

        $this->assertArrayHasKey('bhava_lagna', $results);
        $this->assertArrayHasKey('hora_lagna', $results);
        $this->assertArrayHasKey('ghati_lagna', $results);
        $this->assertArrayHasKey('sri_lagna', $results);
        $this->assertArrayHasKey('indu_lagna', $results);

        // Bhava Lagna: 210 + (4 * 15) = 270° (Capricorn 0°)
        $this->assertEqualsWithDelta(270.0, $results['bhava_lagna']['degrees'], 1e-4);
        $this->assertSame(10, $results['bhava_lagna']['rashi']);

        // Hora Lagna: 210 + (4 * 30) = 330° (Pisces 0°)
        $this->assertEqualsWithDelta(330.0, $results['hora_lagna']['degrees'], 1e-4);
        $this->assertSame(12, $results['hora_lagna']['rashi']);

        // Ghati Lagna: 210 + (4 * 75) = 510 mod 360 = 150° (Virgo 0°)
        $this->assertEqualsWithDelta(150.0, $results['ghati_lagna']['degrees'], 1e-4);
        $this->assertSame(6, $results['ghati_lagna']['rashi']);
    }

    public function test_upagraha_mathematical_symmetry_invariants(): void
    {
        $sunLong = 100.0;
        $upagrahas = UpagrahaCalculator::calculateMathematicalUpagrahas($sunLong);

        $this->assertCount(5, $upagrahas);

        $dhuma = $upagrahas['dhuma']['longitude'];
        $vyatipata = $upagrahas['vyatipata']['longitude'];
        $parivesha = $upagrahas['parivesha']['longitude'];
        $indrachapa = $upagrahas['indrachapa']['longitude'];
        $upaketu = $upagrahas['upaketu']['longitude'];

        // Invariant 1: Vyatipata + Dhuma = 360° mod 360 = 0°
        $this->assertEqualsWithDelta(0.0, fmod($vyatipata + $dhuma, 360.0), 1e-3);

        // Invariant 2: Parivesha - Vyatipata = 180°
        $diffP = abs($parivesha - $vyatipata);
        $this->assertEqualsWithDelta(180.0, $diffP, 1e-3);

        // Invariant 3: Indrachapa + Parivesha = 360° mod 360 = 0°
        $this->assertEqualsWithDelta(0.0, fmod($indrachapa + $parivesha, 360.0), 1e-3);

        // Invariant 4: Upaketu = Sun - 30° mod 360 = 100 - 30 = 70°
        $this->assertEqualsWithDelta(70.0, $upaketu, 1e-3);
    }

    public function test_pan_indian_samvat_era_conversions(): void
    {
        // 2026-11-13 (Post-Chaitra, Post-Boishakh, Post-Chingam)
        $eras = SamvatConverter::convert(2026, 11, 13);

        $this->assertSame(2083, $eras['vikram_samvat']['year'], "2026 November is Vikram Samvat 2083.");
        $this->assertSame(1948, $eras['shaka_samvat']['year'], "2026 November is Shaka Samvat 1948.");
        $this->assertSame(1433, $eras['bengali_san']['year'], "2026 November is Bengali San 1433.");
        $this->assertSame(1202, $eras['kollam_era']['year'], "2026 November is Kollam 1202.");
    }
}

