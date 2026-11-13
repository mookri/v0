<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Transit;

use App\Domain\Jyotish\Astronomy\SiderealLayer;
use App\Domain\Jyotish\Ephemeris\EphemerisProviderInterface;
use App\Domain\Jyotish\Ephemeris\MeeusSeriesProvider;
use App\Domain\Jyotish\Transit\Models\TransitAspect;

final readonly class GocharCalculator
{
    public function __construct(
        private EphemerisProviderInterface $provider = new MeeusSeriesProvider(),
    ) {
    }

    /**
     * Compute Gochar (transit positions) relative to a reference Rashi (e.g., Natal Moon or Natal Lagna).
     *
     * @param float $jdUt1
     * @param float $jdTt
     * @param int $referenceRashi (1..12)
     * @param string $ayanamsa
     * @return array<string, TransitAspect>
     */
    public function calculateTransits(
        float $jdUt1,
        float $jdTt,
        int $referenceRashi,
        string $ayanamsa = 'LAHIRI'
    ): array {
        $tropical = $this->provider->computeSnapshot($jdTt, $jdUt1);
        $siderealResult = SiderealLayer::convert($tropical, $ayanamsa);
        $sidereal = $siderealResult['snapshot'];

        $timestamp = (int) round(($jdUt1 - 2440587.5) * 86400.0);
        $now = (new \DateTimeImmutable('@' . $timestamp))->setTimezone(new \DateTimeZone('UTC'));

        $transits = [];
        foreach ($sidereal->getGrahaPositions() as $graha => $pos) {
            $rashi = (int) floor($pos['longitude'] / 30.0) + 1;
            // House relative to reference Rashi (1-based)
            $relativeHouse = (($rashi - $referenceRashi + 12) % 12) + 1;

            $nature = self::evaluateTransitNature($graha, $relativeHouse);

            $transits[$graha] = new TransitAspect(
                graha: $graha,
                natalHouse: $relativeHouse,
                nature: $nature,
                description: "{$graha} transiting {$relativeHouse}th house from reference sign.",
                ingressAt: $now,
            );
        }

        return $transits;
    }

    /**
     * Classical Gochara benefic house placements from Moon (BPHS / Phaladeepika):
     * Sun: 3, 6, 10, 11
     * Moon: 1, 3, 6, 7, 10, 11
     * Mars: 3, 6, 11
     * Mercury: 2, 4, 6, 8, 10, 11
     * Jupiter: 2, 5, 7, 9, 11
     * Venus: 1, 2, 3, 4, 5, 8, 9, 11, 12
     * Saturn: 3, 6, 11
     * Rahu/Ketu: 3, 6, 11
     */
    private static function evaluateTransitNature(string $graha, int $house): string
    {
        $beneficHouses = match (strtolower($graha)) {
            'sun' => [3, 6, 10, 11],
            'moon' => [1, 3, 6, 7, 10, 11],
            'mars', 'saturn', 'rahu', 'ketu' => [3, 6, 11],
            'mercury' => [2, 4, 6, 8, 10, 11],
            'jupiter' => [2, 5, 7, 9, 11],
            'venus' => [1, 2, 3, 4, 5, 8, 9, 11, 12],
            default => [3, 6, 11],
        };

        return in_array($house, $beneficHouses, true) ? 'benefic' : 'malefic';
    }
}

