<?php

namespace Tests\Unit\Jyotish\Astronomy;

use App\Domain\Jyotish\Astronomy\TransitionSolver;
use PHPUnit\Framework\TestCase;

/**
 * Precision & Convergence Tests for TransitionSolver (T-JY05-02).
 */
final class TransitionSolverPrecisionTest extends TestCase
{
    public function test_root_solver_converges_within_one_second_tolerance(): void
    {
        // Simple synthetic linear angular function rotating 360° per day: f(jd) = (jd - 2451545.0) * 360
        $anchor = 2451545.0;
        $targetDeg = 90.0; // exact root at 2451545.25

        $res = TransitionSolver::solveTransition(
            angleFunction: static function (float $jd): float {
                return ($jd - 2451545.0) * 360.0;
            },
            targetAngle: $targetDeg,
            approximateJd: 2451545.1,
            searchWindowDays: 0.5
        );

        $this->assertTrue($res['converged']);
        // 1 second tolerance = 1/86400 days ~ 0.00001157 days
        $this->assertEqualsWithDelta(2451545.25, $res['transition_jd'], TransitionSolver::TOLERANCE_DAYS);
        $this->assertLessThanOrEqual(1e-4, abs($res['residual_degrees']));
    }
}

