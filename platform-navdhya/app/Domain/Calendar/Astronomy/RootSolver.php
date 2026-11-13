<?php

namespace App\Domain\Calendar\Astronomy;

use Closure;
use InvalidArgumentException;

/**
 * Robust numerical root solver (bisection + secant hybrid)
 * for locating astronomical angle boundaries (tithi, nakshatra, sankranti ingresses).
 */
final class RootSolver
{
    /**
     * Find root t in [a, b] such that fn(t) == targetAngle.
     *
     * @param Closure(float): float $angleFn Returns normalized angle in degrees [0, 360)
     * @param float $a Interval start (e.g. epoch seconds or JD)
     * @param float $b Interval end
     * @param float $targetAngle Target angle in degrees [0, 360)
     * @param float $tolerance Tolerance in seconds/units (default: 0.5s)
     * @param int $maxIterations Maximum allowed iterations (default: 30)
     * @return float Solution t
     */
    public static function findAngleCrossing(
        Closure $angleFn,
        float $a,
        float $b,
        float $targetAngle,
        float $tolerance = 0.5,
        int $maxIterations = 30
    ): float {
        $f = function (float $t) use ($angleFn, $targetAngle): float {
            $angle = $angleFn($t);
            $diff = $angle - $targetAngle;
            // Normalize diff to (-180, 180]
            if ($diff > 180.0) {
                $diff -= 360.0;
            } elseif ($diff <= -180.0) {
                $diff += 360.0;
            }
            return $diff;
        };

        $fa = $f($a);
        $fb = $f($b);

        // Check if root is already bracketed
        if ($fa * $fb > 0.0) {
            // Sample midpoint to attempt bracketing
            $mid = ($a + $b) / 2.0;
            $fmid = $f($mid);
            if ($fa * $fmid <= 0.0) {
                $b = $mid;
                $fb = $fmid;
            } elseif ($fmid * $fb <= 0.0) {
                $a = $mid;
                $fa = $fmid;
            }
        }

        // Bisection / Secant iteration
        for ($i = 0; $i < $maxIterations; $i++) {
            if (abs($b - $a) <= $tolerance) {
                return ($a + $b) / 2.0;
            }

            // Secant step
            $denominator = $fb - $fa;
            if (abs($denominator) > 1e-12) {
                $c = $b - ($fb * ($b - $a) / $denominator);
                // Ensure c stays inside [a, b]
                if ($c <= $a || $c >= $b) {
                    $c = ($a + $b) / 2.0;
                }
            } else {
                $c = ($a + $b) / 2.0;
            }

            $fc = $f($c);
            if (abs($fc) < 1e-7 || abs($b - $a) <= $tolerance) {
                return $c;
            }

            if ($fa * $fc <= 0.0) {
                $b = $c;
                $fb = $fc;
            } else {
                $a = $c;
                $fa = $fc;
            }
        }

        return ($a + $b) / 2.0;
    }
}

