<?php

namespace App\Domain\Calendar\Console;

use App\Domain\Calendar\Astronomy\Ayanamsa;
use App\Domain\Calendar\Astronomy\DeltaT;
use App\Domain\Calendar\Astronomy\EventBoundaryResolver;
use App\Domain\Calendar\Astronomy\JulianDayCalculator;
use App\Domain\Calendar\Astronomy\LunarLongitude;
use App\Domain\Calendar\Astronomy\MoonriseUtcCalculator;
use App\Domain\Calendar\Astronomy\SolarLongitude;
use App\Domain\Calendar\Astronomy\SunriseUtcCalculator;
use App\Domain\Calendar\Astronomy\SunsetUtcCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class CalendarVerifyOracleCommand extends Command
{
    protected $signature = 'calendar:verify-oracle {--report : Write report to docs/23-evidence/G-ASTRO-1-validation-report.md}';

    protected $description = 'Verify astronomical math against external gold oracle vectors (IMD / JPL Horizons / Swiss Ephemeris)';

    public function handle(): int
    {
        $this->info('Starting astronomical oracle verification...');

        $rows = [];
        $failures = 0;

        // Test vectors (Solar longitude, Lunar longitude, Sunrise, Sunset, Tithi)
        $vectors = [
            [
                'name' => 'Solar Longitude (Equinox 2026-03-20 12:00 UTC)',
                'actual' => function () {
                    $jd = JulianDayCalculator::fromUtc(2026, 3, 20, 12, 0, 0);
                    $t = JulianDayCalculator::centuriesSinceJ2000($jd);
                    return SolarLongitude::calculate($t);
                },
                'expected' => 359.88,
                'tolerance' => 0.05,
                'unit' => '°',
            ],
            [
                'name' => 'Lunar Longitude (Purnima 2026-11-24 12:00 UTC)',
                'actual' => function () {
                    $jd = JulianDayCalculator::fromUtc(2026, 11, 24, 12, 0, 0);
                    $t = JulianDayCalculator::centuriesSinceJ2000($jd);
                    return LunarLongitude::calculate($t);
                },
                'expected' => 60.48,
                'tolerance' => 0.25,
                'unit' => '°',
            ],
            [
                'name' => 'Chitrapaksha Ayanamsa (2026.0)',
                'actual' => function () {
                    $jd = JulianDayCalculator::fromUtc(2026, 1, 1, 0, 0, 0);
                    $t = JulianDayCalculator::centuriesSinceJ2000($jd);
                    return Ayanamsa::forCenturies($t);
                },
                'expected' => 24.22,
                'tolerance' => 0.02,
                'unit' => '°',
            ],
            [
                'name' => 'Delta T (2026.0)',
                'actual' => function () {
                    return DeltaT::inSeconds(2026.0);
                },
                'expected' => 75.60,
                'tolerance' => 1.5,
                'unit' => 's',
            ],
            [
                'name' => 'Sunrise Ujjain (2026-11-10)',
                'actual' => function () {
                    $res = SunriseUtcCalculator::calculate(2026, 11, 10, 23.1765, 75.7885);
                    return $res->instant_utc->getTimestamp();
                },
                'expected' => CarbonImmutable::parse('2026-11-10T01:07:00Z')->getTimestamp(),
                'tolerance' => 120,
                'unit' => 's',
            ],
            [
                'name' => 'Sunset Ujjain (2026-11-10)',
                'actual' => function () {
                    $res = SunsetUtcCalculator::calculate(2026, 11, 10, 23.1765, 75.7885);
                    return $res->instant_utc->getTimestamp();
                },
                'expected' => CarbonImmutable::parse('2026-11-10T12:14:00Z')->getTimestamp(),
                'tolerance' => 120,
                'unit' => 's',
            ],
        ];

        $reportLines = [];
        $reportLines[] = '# G-ASTRO-1: Astronomical Validation Report';
        $reportLines[] = '';
        $reportLines[] = '**Generated at:** ' . CarbonImmutable::now()->toIso8601String();
        $reportLines[] = '**Reference Oracles:** IMD Rashtriya Panchang, JPL Horizons, Swiss Ephemeris offline vectors';
        $reportLines[] = '';
        $reportLines[] = '| Test Vector | Measured | Expected | Max Error Allowed | Actual Delta | Status |';
        $reportLines[] = '| --- | --- | --- | --- | --- | --- |';

        foreach ($vectors as $v) {
            $measured = call_user_func($v['actual']);
            $expected = $v['expected'];
            $delta = abs($measured - $expected);
            $pass = $delta <= $v['tolerance'];

            if (! $pass) {
                $failures++;
            }

            $statusStr = $pass ? 'PASS' : 'FAIL';
            $rows[] = [
                $v['name'],
                round($measured, 3) . ' ' . $v['unit'],
                round($expected, 3) . ' ' . $v['unit'],
                '± ' . $v['tolerance'] . ' ' . $v['unit'],
                round($delta, 3) . ' ' . $v['unit'],
                $statusStr,
            ];

            $reportLines[] = sprintf(
                '| %s | %s | %s | %s | %s | %s |',
                $v['name'],
                round($measured, 3) . ' ' . $v['unit'],
                round($expected, 3) . ' ' . $v['unit'],
                '± ' . $v['tolerance'] . ' ' . $v['unit'],
                round($delta, 3) . ' ' . $v['unit'],
                $statusStr
            );
        }

        $this->table(
            ['Vector', 'Measured', 'Expected', 'Tolerance', 'Delta', 'Status'],
            $rows
        );

        if ($this->option('report')) {
            $reportPath = base_path('docs/23-evidence/G-ASTRO-1-validation-report.md');
            File::ensureDirectoryExists(dirname($reportPath));
            File::put($reportPath, implode("\n", $reportLines) . "\n");
            $this->info("Validation report written to: {$reportPath}");
        }

        if ($failures > 0) {
            $this->error("Oracle verification failed with {$failures} errors.");
            return 1;
        }

        $this->info('All astronomical oracle vectors verified successfully within tolerance limits.');
        return 0;
    }
}
