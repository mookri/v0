<?php

namespace App\Domain\Jyotish\Chart\Varga;

use App\Domain\Jyotish\Contracts\ZodiacConstants;
use App\Domain\Jyotish\Support\Angle;
use InvalidArgumentException;

/**
 * Shodasha Varga Harmonic Engine (T-JY04-07).
 *
 * Implements rigorous Parashari mathematical algorithms for all 16 divisional charts:
 * D1, D2, D3, D4, D7, D9, D10, D12, D16, D20, D24, D27, D30, D40, D45, D60.
 *
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class VargaHarmonicEngine implements ZodiacConstants
{
    public const SUPPORTED_VARGAS = [
        'D1' => ['name' => 'Rashi', 'division' => 1, 'domain' => 'Physical body & general life'],
        'D2' => ['name' => 'Hora', 'division' => 2, 'domain' => 'Wealth & family assets'],
        'D3' => ['name' => 'Drekkana', 'division' => 3, 'domain' => 'Siblings, courage & initiative'],
        'D4' => ['name' => 'Chaturthamsha', 'division' => 4, 'domain' => 'Fixed assets, home & fortune'],
        'D7' => ['name' => 'Saptamsha', 'division' => 7, 'domain' => 'Children, progeny & lineage'],
        'D9' => ['name' => 'Navamsha', 'division' => 9, 'domain' => 'Dharma, spouse & inner potential'],
        'D10' => ['name' => 'Dashamsha', 'division' => 10, 'domain' => 'Career, profession & prestige'],
        'D12' => ['name' => 'Dwadashamsha', 'division' => 12, 'domain' => 'Parents & ancestral lineage'],
        'D16' => ['name' => 'Shodashamsha', 'division' => 16, 'domain' => 'Vehicles, luxuries & contentment'],
        'D20' => ['name' => 'Vimshamsha', 'division' => 20, 'domain' => 'Spiritual progress & upasana'],
        'D24' => ['name' => 'Chaturvimshamsha', 'division' => 24, 'domain' => 'Higher learning & intellect'],
        'D27' => ['name' => 'Saptavimshamsha', 'division' => 27, 'domain' => 'Inherent strengths & stamina'],
        'D30' => ['name' => 'Trimshamsha', 'division' => 30, 'domain' => 'Arishta, misfortunes & evils'],
        'D40' => ['name' => 'Khavedamsha', 'division' => 40, 'domain' => 'Auspicious & inauspicious events'],
        'D45' => ['name' => 'Akshavedamsha', 'division' => 45, 'domain' => 'General character & conduct'],
        'D60' => ['name' => 'Shashtiamsha', 'division' => 60, 'domain' => 'Past-life karma & fine nuances'],
    ];

    /**
     * Calculate Varga Rashi placement for a given sidereal longitude.
     *
     * @param string $vargaCode e.g. 'D1', 'D9', 'D10'
     * @param float $siderealDegrees Ecliptic longitude in degrees [0, 360)
     * @return array{
     *     varga: string,
     *     division: int,
     *     rashi_index: int,
     *     rashi_name: string,
     *     rashi_lord: string,
     *     degrees_in_varga: float
     * }
     */
    public static function calculate(string $vargaCode, float $siderealDegrees): array
    {
        $code = strtoupper(trim($vargaCode));
        if (! isset(self::SUPPORTED_VARGAS[$code])) {
            throw new InvalidArgumentException("Unsupported Varga code '{$vargaCode}'.");
        }

        $degNorm = Angle::normalize($siderealDegrees);
        $rashiIndex = (int) floor($degNorm / self::RASHI_SPAN_DEGREES);
        if ($rashiIndex >= 12) {
            $rashiIndex = 11;
        }
        $degInRashi = $degNorm - ($rashiIndex * self::RASHI_SPAN_DEGREES);

        $vargaRashi = match ($code) {
            'D1' => $rashiIndex,
            'D2' => self::calculateD2($rashiIndex, $degInRashi),
            'D3' => self::calculateD3($rashiIndex, $degInRashi),
            'D4' => self::calculateD4($rashiIndex, $degInRashi),
            'D7' => self::calculateD7($rashiIndex, $degInRashi),
            'D9' => self::calculateD9($degNorm),
            'D10' => self::calculateD10($rashiIndex, $degInRashi),
            'D12' => self::calculateD12($rashiIndex, $degInRashi),
            'D16' => self::calculateD16($rashiIndex, $degInRashi),
            'D20' => self::calculateD20($rashiIndex, $degInRashi),
            'D24' => self::calculateD24($rashiIndex, $degInRashi),
            'D27' => self::calculateD27($rashiIndex, $degInRashi),
            'D30' => self::calculateD30($rashiIndex, $degInRashi),
            'D40' => self::calculateD40($rashiIndex, $degInRashi),
            'D45' => self::calculateD45($rashiIndex, $degInRashi),
            'D60' => self::calculateD60($rashiIndex, $degInRashi),
            default => $rashiIndex,
        };

        $vargaRashi = ($vargaRashi % 12 + 12) % 12;
        $rashiMeta = self::RASHIS[$vargaRashi];

        $division = self::SUPPORTED_VARGAS[$code]['division'];
        $sliceSpan = 30.0 / $division;
        $fraction = ($sliceSpan > 0.0) ? fmod($degInRashi, $sliceSpan) / $sliceSpan : 0.0;
        $degInVarga = $fraction * 30.0;

        return [
            'varga' => $code,
            'division' => $division,
            'rashi_index' => $vargaRashi,
            'rashi_name' => $rashiMeta['name'],
            'rashi_lord' => $rashiMeta['lord'],
            'degrees_in_varga' => round($degInVarga, 4),
        ];
    }

    private static function calculateD2(int $rashi, float $deg): int
    {
        $isOdd = ($rashi % 2 === 0);
        $firstHalf = ($deg < 15.0);

        if ($isOdd) {
            return $firstHalf ? 4 : 3; // Simha (Sun), Karkataka (Moon)
        }
        return $firstHalf ? 3 : 4; // Karkataka (Moon), Simha (Sun)
    }

    private static function calculateD3(int $rashi, float $deg): int
    {
        $k = (int) floor($deg / 10.0);
        if ($k > 2) {
            $k = 2;
        }
        return ($rashi + ($k * 4)) % 12;
    }

    private static function calculateD4(int $rashi, float $deg): int
    {
        $k = (int) floor($deg / 7.5);
        if ($k > 3) {
            $k = 3;
        }
        return ($rashi + ($k * 3)) % 12;
    }

    private static function calculateD7(int $rashi, float $deg): int
    {
        $span = 30.0 / 7.0;
        $k = (int) floor($deg / $span);
        if ($k > 6) {
            $k = 6;
        }
        $isOdd = ($rashi % 2 === 0);
        $start = $isOdd ? $rashi : (($rashi + 6) % 12);
        return ($start + $k) % 12;
    }

    private static function calculateD9(float $totalDegrees): int
    {
        // Direct 108 Pada harmonic: 360° / 108 = 10/3°
        $pada = (int) floor(($totalDegrees + 1e-11) / (10.0 / 3.0));
        return $pada % 12;
    }

    private static function calculateD10(int $rashi, float $deg): int
    {
        $k = (int) floor($deg / 3.0);
        if ($k > 9) {
            $k = 9;
        }
        $isOdd = ($rashi % 2 === 0);
        $start = $isOdd ? $rashi : (($rashi + 8) % 12);
        return ($start + $k) % 12;
    }

    private static function calculateD12(int $rashi, float $deg): int
    {
        $k = (int) floor($deg / 2.5);
        if ($k > 11) {
            $k = 11;
        }
        return ($rashi + $k) % 12;
    }

    private static function calculateD16(int $rashi, float $deg): int
    {
        $span = 30.0 / 16.0;
        $k = (int) floor($deg / $span);
        if ($k > 15) {
            $k = 15;
        }
        $modality = $rashi % 3; // 0 = Chara (Movable), 1 = Sthira (Fixed), 2 = Dwiswabhava (Dual)
        $start = match ($modality) {
            0 => 0, // Mesha
            1 => 4, // Simha
            2 => 8, // Dhanu
            default => 0,
        };
        return ($start + $k) % 12;
    }

    private static function calculateD20(int $rashi, float $deg): int
    {
        $k = (int) floor($deg / 1.5);
        if ($k > 19) {
            $k = 19;
        }
        $modality = $rashi % 3;
        $start = match ($modality) {
            0 => 0, // Mesha
            1 => 8, // Dhanu
            2 => 4, // Simha
            default => 0,
        };
        return ($start + $k) % 12;
    }

    private static function calculateD24(int $rashi, float $deg): int
    {
        $k = (int) floor($deg / 1.25);
        if ($k > 23) {
            $k = 23;
        }
        $isOdd = ($rashi % 2 === 0);
        $start = $isOdd ? 4 : 3; // Simha (Odd) or Karkataka (Even)
        return ($start + $k) % 12;
    }

    private static function calculateD27(int $rashi, float $deg): int
    {
        $span = 30.0 / 27.0;
        $k = (int) floor($deg / $span);
        if ($k > 26) {
            $k = 26;
        }
        $element = $rashi % 4; // 0 = Fire, 1 = Earth, 2 = Air, 3 = Water
        $start = match ($element) {
            0 => 0, // Mesha
            1 => 3, // Karkataka
            2 => 6, // Tula
            3 => 9, // Makara
            default => 0,
        };
        return ($start + $k) % 12;
    }

    private static function calculateD30(int $rashi, float $deg): int
    {
        $isOdd = ($rashi % 2 === 0);

        if ($isOdd) {
            return match (true) {
                $deg < 5.0 => 0,   // 0-5°: Mars (Mesha)
                $deg < 10.0 => 10, // 5-10°: Saturn (Kumbha)
                $deg < 18.0 => 8,  // 10-18°: Jupiter (Dhanu)
                $deg < 25.0 => 2,  // 18-25°: Mercury (Mithuna)
                default => 6,      // 25-30°: Venus (Tula)
            };
        }

        return match (true) {
            $deg < 5.0 => 1,   // 0-5°: Venus (Vrishabha)
            $deg < 12.0 => 5,  // 5-12°: Mercury (Kanya)
            $deg < 20.0 => 11, // 12-20°: Jupiter (Meena)
            $deg < 25.0 => 9,  // 20-25°: Saturn (Makara)
            default => 7,      // 25-30°: Mars (Vrishchika)
        };
    }

    private static function calculateD40(int $rashi, float $deg): int
    {
        $k = (int) floor($deg / 0.75);
        if ($k > 39) {
            $k = 39;
        }
        $isOdd = ($rashi % 2 === 0);
        $start = $isOdd ? 0 : 6; // Mesha or Tula
        return ($start + $k) % 12;
    }

    private static function calculateD45(int $rashi, float $deg): int
    {
        $span = 30.0 / 45.0;
        $k = (int) floor($deg / $span);
        if ($k > 44) {
            $k = 44;
        }
        $modality = $rashi % 3;
        $start = match ($modality) {
            0 => 0, // Mesha
            1 => 4, // Simha
            2 => 8, // Dhanu
            default => 0,
        };
        return ($start + $k) % 12;
    }

    private static function calculateD60(int $rashi, float $deg): int
    {
        $k = (int) floor($deg / 0.5);
        if ($k > 59) {
            $k = 59;
        }
        return ($rashi + $k) % 12;
    }
}

