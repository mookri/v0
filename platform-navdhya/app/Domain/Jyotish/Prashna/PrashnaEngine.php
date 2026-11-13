<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Prashna;

use App\Domain\Jyotish\Astronomy\AscendantSolver;
use App\Domain\Jyotish\Astronomy\Time\AstronomicalTime;
use App\Domain\Jyotish\Ephemeris\EphemerisProviderInterface;
use App\Domain\Jyotish\Ephemeris\MeeusSeriesProvider;

final readonly class PrashnaEngine
{
    public function __construct(
        private EphemerisProviderInterface $provider = new MeeusSeriesProvider(),
    ) {
    }

    /**
     * Cast Horary Prashna Chart for exact query timestamp and coordinate.
     * Prashna query contexts are completely decoupled from natal birth profiles (AC-T-JY09-03).
     *
     * @param \DateTimeImmutable $queryTime
     * @param float $latitude
     * @param float $longitude
     * @param string $questionSummary
     * @return array<string, mixed>
     */
    public function castPrashna(
        \DateTimeImmutable $queryTime,
        float $latitude,
        float $longitude,
        string $questionSummary = ''
    ): array {
        $year = (int) $queryTime->format('Y');
        $month = (int) $queryTime->format('m');
        $day = (int) $queryTime->format('d');
        $hour = (int) $queryTime->format('H');
        $minute = (int) $queryTime->format('i');
        $second = (int) $queryTime->format('s');

        $jdUt1 = AstronomicalTime::calculateJulianDay($year, $month, $day, $hour, $minute, $second);
        $jdTt = $jdUt1 + (69.184 / 86400.0);

        // 1. Calculate Prashna Lagna using AscendantSolver::solve
        $ascResult = AscendantSolver::solve($jdUt1, $jdTt, $latitude, $longitude, ayanamsaDegrees: 24.18);

        $prashnaLagnaDeg = $ascResult['sidereal_ascendant'];
        $lagnaRashi = (int) floor($prashnaLagnaDeg / 30.0) + 1;

        // 2. Ephemeris planetary snapshot
        $tropical = $this->provider->computeSnapshot($jdTt, $jdUt1);

        // 3. Daivajna Prateek (Jamakkol Arudam indicator)
        $jamakkolSign = (($lagnaRashi + ((int) $hour % 12) - 1) % 12) + 1;

        return [
            'prashna_type' => 'HORARY_PRASHNA_TANTRA',
            'query_timestamp' => $queryTime->format(\DateTimeInterface::ATOM),
            'coordinates' => ['latitude' => $latitude, 'longitude' => $longitude],
            'question_summary' => $questionSummary,
            'birth_profile_id' => null, // Strictly null for query context isolation
            'prashna_lagna_degrees' => round($prashnaLagnaDeg, 4),
            'prashna_lagna_sign' => $lagnaRashi,
            'jamakkol_arudam_sign' => $jamakkolSign,
        ];
    }
}
