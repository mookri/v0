<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Ephemeris;

use App\Domain\Jyotish\Support\Angle;

final readonly class UpagrahaCalculator
{
    /**
     * Compute the 5 mathematical non-luminous planets (Aprakash Grahas / Upagrahas)
     * as defined in BPHS Ch. 3 and Phaladeepika:
     * 1. Dhuma = Sun Longitude + 133° 20' (4 Signs 13° 20')
     * 2. Vyatipata = 360° - Dhuma
     * 3. Parivesha = Vyatipata + 180°
     * 4. Indrachapa (Karmuka) = 360° - Parivesha
     * 5. Upaketu (Sikhi) = Indrachapa + 16° 40' (= Sun - 30°)
     *
     * Mathematical symmetry invariant verified by tests (AC-T-JY10-02).
     *
     * @param float $sunLongitude [0, 360)
     * @return array<string, array{name: string, longitude: float, rashi: int}>
     */
    public static function calculateMathematicalUpagrahas(float $sunLongitude): array
    {
        $dhuma = Angle::normalize($sunLongitude + (133.0 + 20.0 / 60.0));
        $vyatipata = Angle::normalize(360.0 - $dhuma);
        $parivesha = Angle::normalize($vyatipata + 180.0);
        $indrachapa = Angle::normalize(360.0 - $parivesha);
        $upaketu = Angle::normalize($indrachapa + (16.0 + 40.0 / 60.0));

        return [
            'dhuma' => [
                'name' => 'Dhuma',
                'longitude' => round($dhuma, 4),
                'rashi' => (int) floor($dhuma / 30.0) + 1,
            ],
            'vyatipata' => [
                'name' => 'Vyatipata',
                'longitude' => round($vyatipata, 4),
                'rashi' => (int) floor($vyatipata / 30.0) + 1,
            ],
            'parivesha' => [
                'name' => 'Parivesha',
                'longitude' => round($parivesha, 4),
                'rashi' => (int) floor($parivesha / 30.0) + 1,
            ],
            'indrachapa' => [
                'name' => 'Indrachapa',
                'longitude' => round($indrachapa, 4),
                'rashi' => (int) floor($indrachapa / 30.0) + 1,
            ],
            'upaketu' => [
                'name' => 'Upaketu',
                'longitude' => round($upaketu, 4),
                'rashi' => (int) floor($upaketu / 30.0) + 1,
            ],
        ];
    }
}

