<?php

namespace Tests\Unit\Jyotish\Ephemeris;

use App\Domain\Jyotish\Ephemeris\NodesResolver;
use PHPUnit\Framework\TestCase;

/**
 * Lunar Nodes Engine Invariant & Precision Tests (T-JY02-02).
 * Enforces AC-T-JY02-02:
 *  - Exact 180-degree opposition invariant across all dates and modes.
 *  - Retrograde motion validation.
 *  - True node vs Mean node oscillation.
 */
final class LunarNodesTest extends TestCase
{
    public function test_exact_180_degree_opposition_invariant(): void
    {
        $modes = [NodesResolver::MODE_MEAN, NodesResolver::MODE_TRUE];

        // Sample across 200 century points (-1.0 to 1.0, 1900 to 2100 CE)
        for ($step = 0; $step <= 200; $step++) {
            $t = -1.0 + ($step * 0.01);

            foreach ($modes as $mode) {
                $nodes = NodesResolver::resolve($t, $mode);
                $rahu = $nodes['rahu'];
                $ketu = $nodes['ketu'];

                $diff = abs(NodesResolver::angularDifference($rahu['longitude'] + 180.0, $ketu['longitude']));
                $this->assertLessThan(
                    1e-9,
                    $diff,
                    "Opposition invariant violated at T={$t} in mode {$mode}: delta={$diff}",
                );

                $this->assertGreaterThanOrEqual(0.0, $rahu['longitude']);
                $this->assertLessThan(360.0, $rahu['longitude']);
                $this->assertGreaterThanOrEqual(0.0, $ketu['longitude']);
                $this->assertLessThan(360.0, $ketu['longitude']);

                // In Mean mode, velocities are strictly retrograde (negative)
                if ($mode === NodesResolver::MODE_MEAN) {
                    $this->assertLessThan(0.0, $rahu['speed_longitude']);
                    $this->assertEqualsWithDelta(-0.05295, $rahu['speed_longitude'], 0.005);
                }
            }
        }
    }

    public function test_true_node_oscillates_around_mean_node(): void
    {
        $t = 0.26; // ~2026 CE

        $mean = NodesResolver::resolve($t, NodesResolver::MODE_MEAN);
        $true = NodesResolver::resolve($t, NodesResolver::MODE_TRUE);

        $delta = abs(NodesResolver::angularDifference($true['rahu']['longitude'], $mean['rahu']['longitude']));

        // True node departs from mean node by up to ~1.75 degrees
        $this->assertGreaterThan(0.01, $delta);
        $this->assertLessThan(2.0, $delta);
    }
}

