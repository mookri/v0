<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Transit;

use App\Domain\Jyotish\Astronomy\SiderealLayer;
use App\Domain\Jyotish\Ephemeris\EphemerisProviderInterface;
use App\Domain\Jyotish\Ephemeris\MeeusSeriesProvider;
use App\Domain\Jyotish\Transit\Models\SadeSatiPhase;

final readonly class SadeSatiDetector
{
    public function __construct(
        private EphemerisProviderInterface $provider = new MeeusSeriesProvider(),
    ) {
    }

    /**
     * Compute Sade Sati and Dhaiya phases from continuous Saturn ephemeris longitudes.
     * Never uses static lookup tables (doc 01 §3, AC-T-JY07-08).
     *
     * @param float $jdUt1
     * @param float $jdTt
     * @param int $natalMoonRashi (1..12)
     * @param string $ayanamsa
     * @return SadeSatiPhase
     */
    public function detectPhase(
        float $jdUt1,
        float $jdTt,
        int $natalMoonRashi,
        string $ayanamsa = 'LAHIRI'
    ): SadeSatiPhase {
        $tropical = $this->provider->computeSnapshot($jdTt, $jdUt1);
        $sidereal = SiderealLayer::convert($tropical, $ayanamsa)['snapshot'];
        $saturnLong = $sidereal->getGrahaPositions()['saturn']['longitude'];

        $saturnRashi = (int) floor($saturnLong / 30.0) + 1;
        $relativeHouse = (($saturnRashi - $natalMoonRashi + 12) % 12) + 1;

        return match ($relativeHouse) {
            12 => new SadeSatiPhase(
                phase: 'RISING',
                saturnRashi: $saturnRashi,
                natalMoonRashi: $natalMoonRashi,
                relativeHouse: 12,
                isActive: true,
                description: 'First Phase (Rising): Saturn transits the 12th house from natal Moon.'
            ),
            1 => new SadeSatiPhase(
                phase: 'PEAK',
                saturnRashi: $saturnRashi,
                natalMoonRashi: $natalMoonRashi,
                relativeHouse: 1,
                isActive: true,
                description: 'Second Phase (Peak / Janma Shani): Saturn transits natal Moon Rashi.'
            ),
            2 => new SadeSatiPhase(
                phase: 'SETTING',
                saturnRashi: $saturnRashi,
                natalMoonRashi: $natalMoonRashi,
                relativeHouse: 2,
                isActive: true,
                description: 'Third Phase (Setting): Saturn transits the 2nd house from natal Moon.'
            ),
            4 => new SadeSatiPhase(
                phase: 'DHAIYA_4TH',
                saturnRashi: $saturnRashi,
                natalMoonRashi: $natalMoonRashi,
                relativeHouse: 4,
                isActive: true,
                description: 'Kantaka Shani (Small Panoti): Saturn transits the 4th house from Moon.'
            ),
            8 => new SadeSatiPhase(
                phase: 'DHAIYA_8TH',
                saturnRashi: $saturnRashi,
                natalMoonRashi: $natalMoonRashi,
                relativeHouse: 8,
                isActive: true,
                description: 'Ashtama Shani: Saturn transits the 8th house from Moon.'
            ),
            default => new SadeSatiPhase(
                phase: 'NONE',
                saturnRashi: $saturnRashi,
                natalMoonRashi: $natalMoonRashi,
                relativeHouse: $relativeHouse,
                isActive: false,
                description: 'Saturn is currently outside Sade Sati and Dhaiya houses.'
            ),
        };
    }
}

