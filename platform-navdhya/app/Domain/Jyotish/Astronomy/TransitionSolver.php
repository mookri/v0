<?php

namespace App\Domain\Jyotish\Astronomy;

use App\Domain\Jyotish\Ephemeris\EphemerisProviderInterface;
use App\Domain\Jyotish\Ephemeris\MeeusSeriesProvider;
use App\Domain\Jyotish\Support\Angle;
use Closure;

/**
 * High-Precision Astronomical Event Boundary & Root Solver (T-JY05-02).
 *
 * Uses coarse interval bracketing followed by Brent/secant root solving
 * to converge on exact boundary transitions down to <= 1 second (< 10^-6 rad) tolerance.
 *
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class TransitionSolver
{
    public const TOLERANCE_SECONDS = 1.0;
    public const TOLERANCE_DAYS = self::TOLERANCE_SECONDS / 86400.0; // ~1.157e-5 days

    /**
     * Find the exact Julian Day TT when a continuous monotonic angular function f(jd) reaches targetAngle.
     *
     * @param Closure(float $jd): float $angleFunction Evaluates angle in degrees [0, 360) at JD
     * @param float $targetAngle Target angle in degrees [0, 360)
     * @param float $approximateJd Initial anchor JD
     * @param float $searchWindowDays Search window in days (default +/- 1.5 days)
     * @return array{
     *     transition_jd: float,
     *     residual_degrees: float,
     *     iterations: int,
     *     converged: bool
     * }
     */
    public static function solveTransition(
        Closure $angleFunction,
        float $targetAngle,
        float $approximateJd,
        float $searchWindowDays = 1.5
    ): array {
        $target = Angle::normalize($targetAngle);

        // Helper to compute directional angle difference: f(jd) - target in (-180, 180]
        $diffFunc = static function (float $jd) use ($angleFunction, $target): float {
            $current = Angle::normalize($angleFunction($jd));
            $diff = $current - $target;
            if ($diff > 180.0) {
                $diff -= 360.0;
            } elseif ($diff <= -180.0) {
                $diff += 360.0;
            }
            return $diff;
        };

        // 1. Bracket search: sample points across window
        $steps = 24;
        $stepSize = ($searchWindowDays * 2.0) / $steps;
        $tLeft = $approximateJd - $searchWindowDays;
        $tRight = $approximateJd + $searchWindowDays;

        $bracketA = null;
        $bracketB = null;
        $valA = null;
        $valB = null;

        $prevT = $tLeft;
        $prevVal = $diffFunc($prevT);

        for ($i = 1; $i <= $steps; $i++) {
            $currT = $tLeft + ($i * $stepSize);
            $currVal = $diffFunc($currT);

            // Check if root crossed: sign change without a 360° jump
            if (($prevVal <= 0.0 && $currVal >= 0.0) || ($prevVal >= 0.0 && $currVal <= 0.0)) {
                if (abs($currVal - $prevVal) < 180.0) {
                    $bracketA = $prevT;
                    $bracketB = $currT;
                    $valA = $prevVal;
                    $valB = $currVal;
                    break;
                }
            }

            $prevT = $currT;
            $prevVal = $currVal;
        }

        if ($bracketA === null || $bracketB === null) {
            // Fall back to approximateJd +/- 0.5 days
            $bracketA = $approximateJd - 0.5;
            $bracketB = $approximateJd + 0.5;
            $valA = $diffFunc($bracketA);
            $valB = $diffFunc($bracketB);
        }

        // 2. Secant / Bisection hybrid solver
        $iter = 0;
        $maxIter = 30;
        $root = ($bracketA + $bracketB) / 2.0;
        $residual = 0.0;

        while ($iter < $maxIter) {
            $iter++;

            // Secant step
            $denominator = $valB - $valA;
            if (abs($denominator) > 1e-12) {
                $tNext = $bracketB - ($valB * ($bracketB - $bracketA) / $denominator);
            } else {
                $tNext = ($bracketA + $bracketB) / 2.0;
            }

            // If secant stepped outside bracket, bisect
            if ($tNext <= $bracketA || $tNext >= $bracketB) {
                $tNext = ($bracketA + $bracketB) / 2.0;
            }

            $valNext = $diffFunc($tNext);
            $residual = $valNext;

            if (abs($bracketB - $bracketA) <= self::TOLERANCE_DAYS || abs($valNext) <= 1e-6) {
                $root = $tNext;
                break;
            }

            // Maintain bracket containing root
            if (($valA * $valNext) <= 0.0) {
                $bracketB = $tNext;
                $valB = $valNext;
            } else {
                $bracketA = $tNext;
                $valA = $valNext;
            }

            $root = $tNext;
        }

        return [
            'transition_jd' => $root,
            'residual_degrees' => $residual,
            'iterations' => $iter,
            'converged' => abs($residual) <= 1e-4,
        ];
    }

    /**
     * Helper: Resolve Tithi end instant.
     */
    public static function resolveTithiEndJd(
        float $anchorJd,
        int $tithiNumber,
        ?EphemerisProviderInterface $provider = null
    ): float {
        $ephem = $provider ?? new MeeusSeriesProvider();
        $targetElongation = Angle::normalize($tithiNumber * 12.0);

        $res = self::solveTransition(
            angleFunction: static function (float $jd) use ($ephem): float {
                $snap = $ephem->computeSnapshot($jd);
                $grahas = $snap->getGrahaPositions();
                return Angle::normalize($grahas['moon']['longitude'] - $grahas['sun']['longitude']);
            },
            targetAngle: $targetElongation,
            approximateJd: $anchorJd,
            searchWindowDays: 1.5
        );

        return $res['transition_jd'];
    }

    /**
     * Helper: Resolve Nakshatra end instant.
     */
    public static function resolveNakshatraEndJd(
        float $anchorJd,
        int $nakshatraIndex,
        float $ayanamsaDegrees = 0.0,
        ?EphemerisProviderInterface $provider = null
    ): float {
        $ephem = $provider ?? new MeeusSeriesProvider();
        $targetDeg = Angle::normalize(($nakshatraIndex + 1) * (360.0 / 27.0));

        $res = self::solveTransition(
            angleFunction: static function (float $jd) use ($ephem, $ayanamsaDegrees): float {
                $snap = $ephem->computeSnapshot($jd);
                $moonTropical = $snap->getGrahaPositions()['moon']['longitude'];
                return Angle::normalize($moonTropical - $ayanamsaDegrees);
            },
            targetAngle: $targetDeg,
            approximateJd: $anchorJd,
            searchWindowDays: 1.5
        );

        return $res['transition_jd'];
    }
}

