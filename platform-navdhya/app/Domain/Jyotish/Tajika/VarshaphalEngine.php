<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Tajika;

use App\Domain\Jyotish\Astronomy\Time\AstronomicalTime;
use App\Domain\Jyotish\Ephemeris\EphemerisProviderInterface;
use App\Domain\Jyotish\Ephemeris\MeeusSeriesProvider;

final readonly class VarshaphalEngine
{
    public function __construct(
        private EphemerisProviderInterface $provider = new MeeusSeriesProvider(),
    ) {
    }

    /**
     * Solve for the exact annual Solar Return instant where tropical Sun longitude matches natal Sun longitude.
     * Convergence tolerance: <= 1e-5 degrees (approx 1 second of solar arc).
     *
     * @param float $natalSunLongitude Tropical solar longitude at birth
     * @param int $targetYear Gregorian target return year
     * @param float $approxBirthJd Approximate birth JD
     * @return array{return_jd: float, muntha_sign: int, solar_longitude_error: float}
     */
    public function calculateSolarReturn(
        float $natalSunLongitude,
        int $targetYear,
        float $approxBirthJd,
        int $natalLagnaSign = 1,
        int $ageYears = 1
    ): array {
        // Approximate return JD: natal birth JD + (ageYears * 365.2422 days)
        $jdEstimate = $approxBirthJd + ($ageYears * 365.242189);

        // Secant root-solving for Sun longitude match
        $currentJd = $jdEstimate;
        $maxIterations = 15;
        $error = 1.0;

        for ($iter = 0; $iter < $maxIterations; $iter++) {
            $snap = $this->provider->computeSnapshot($currentJd, $currentJd);
            $sunLong = $snap->getGrahaPositions()['sun']['longitude'];

            $diff = $sunLong - $natalSunLongitude;
            if ($diff > 180.0) $diff -= 360.0;
            if ($diff < -180.0) $diff += 360.0;

            $error = abs($diff);
            if ($error <= 0.00001) {
                break;
            }

            // Sun moves ~0.9856° per day (approx 1 day / 0.9856 = 1.0146 days per degree)
            $stepDays = $diff / 0.9856;
            $currentJd -= $stepDays;
        }

        // Muntha calculation: progresses 1 sign per completed year from natal Lagna
        // Muntha = (Natal Lagna + Completed Years - 1) mod 12 + 1
        $munthaSign = (($natalLagnaSign + $ageYears - 1) % 12) + 1;

        return [
            'return_jd' => round($currentJd, 6),
            'muntha_sign' => $munthaSign,
            'solar_longitude_error' => round($error, 7),
        ];
    }
}

