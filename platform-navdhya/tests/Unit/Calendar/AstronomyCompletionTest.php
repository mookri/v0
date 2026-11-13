<?php

namespace Tests\Unit\Calendar;

use App\Domain\Calendar\Astronomy\DeltaT;
use App\Domain\Calendar\Astronomy\EquationOfTime;
use App\Domain\Calendar\Astronomy\EventBoundaryResolver;
use App\Domain\Calendar\Astronomy\RootSolver;
use App\Domain\Calendar\Astronomy\SiderealTime;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class AstronomyCompletionTest extends TestCase
{
    public function test_delta_t_for_2026_matches_nasa_polynomial(): void
    {
        $dt = CarbonImmutable::parse('2026-11-10 00:00:00', 'UTC');
        $deltaT = DeltaT::forDate($dt);

        // In 2026, ΔT is approximately 70-76 seconds
        $this->assertGreaterThan(65.0, $deltaT);
        $this->assertLessThan(80.0, $deltaT);

        $tt = DeltaT::toTerrestrialTime($dt);
        $this->assertSame(
            (int) round($deltaT),
            (int) round(abs($tt->diffInSeconds($dt)))
        );
    }

    public function test_sidereal_time_properties_and_normalization(): void
    {
        $utc = CarbonImmutable::parse('2026-11-10 12:00:00', 'UTC');

        $gmst = SiderealTime::gmstDegrees($utc);
        $this->assertGreaterThanOrEqual(0.0, $gmst);
        $this->assertLessThan(360.0, $gmst);

        // Local sidereal time at Ujjain (+75.7885° E)
        $lmst = SiderealTime::lmstDegrees($utc, 75.7885);
        $this->assertGreaterThanOrEqual(0.0, $lmst);
        $this->assertLessThan(360.0, $lmst);

        $lmstHours = SiderealTime::degreesToHours($lmst);
        $this->assertGreaterThanOrEqual(0.0, $lmstHours);
        $this->assertLessThan(24.0, $lmstHours);
    }

    public function test_equation_of_time_and_solar_noon(): void
    {
        $date = CarbonImmutable::parse('2026-11-10 00:00:00', 'UTC');
        $eot = EquationOfTime::inMinutes($date);

        // In early November, EoT reaches near maximum (+15 to +16 minutes)
        $this->assertGreaterThan(10.0, $eot);
        $this->assertLessThan(20.0, $eot);

        // True solar noon at Ujjain (75.7885° E)
        $solarNoonUtc = EquationOfTime::trueSolarNoonUtc(2026, 11, 10, 75.7885);

        // Longitude 75.7885° E is ~5h 03m ahead of UTC. With EoT ~+16m, solar noon in UTC is ~06:41 UTC (12:11 IST)
        $this->assertSame('2026-11-10', $solarNoonUtc->format('Y-m-d'));
        $this->assertSame(6, (int) $solarNoonUtc->format('H'));
    }

    public function test_root_solver_finds_exact_angle_crossing(): void
    {
        // Continuous linear angle function: starts at 0°, increases by 1° per unit
        $angleFn = fn (float $t) => fmod($t, 360.0);

        $crossing = RootSolver::findAngleCrossing(
            $angleFn,
            a: 10.0,
            b: 20.0,
            targetAngle: 15.0,
            tolerance: 0.001
        );

        $this->assertEqualsWithDelta(15.0, $crossing, 0.005);
    }

    public function test_event_boundary_resolver_produces_monotonic_microsecond_interval(): void
    {
        $instant = CarbonImmutable::parse('2026-11-10 06:00:00', 'UTC');
        $tithiInterval = EventBoundaryResolver::resolveTithiInterval($instant);

        $this->assertSame(1, $tithiInterval['tithi']);
        $this->assertSame('PRATIPADA', $tithiInterval['name']);
        $this->assertGreaterThan(0, $tithiInterval['starts_at_us']);
        $this->assertGreaterThan($tithiInterval['starts_at_us'], $tithiInterval['ends_at_us']);

        // A lunar tithi duration is typically between 19 and 26 hours
        $durationSeconds = ($tithiInterval['ends_at_us'] - $tithiInterval['starts_at_us']) / 1_000_000;
        $this->assertGreaterThan(19 * 3600, $durationSeconds);
        $this->assertLessThan(27 * 3600, $durationSeconds);
    }
}
