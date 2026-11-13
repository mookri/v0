<?php

namespace App\Domain\Calendar\Console;

use App\Domain\Calendar\Astronomy\SunsetUtcCalculator;
use App\Domain\Calendar\Panchang\PanchangCalculator;
use App\Domain\Calendar\Profiles\GenerationManager;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class CalendarBuildCommand extends Command
{
    protected $signature = 'calendar:build
        {--year=2026 : Gregorian year to compute}
        {--profile=drik-lahiri-purnimanta : Calculation profile code}
        {--places=all : Places scope (all or place ID)}';

    protected $description = 'Batch-compute astronomical events and Panchang days into a staged calendar generation.';

    public function handle(GenerationManager $generations): int
    {
        $year = (int) $this->option('year');
        $profile = (string) $this->option('profile');
        $placesOpt = (string) $this->option('places');

        $this->info("Initiating calendar build for year {$year} under profile {$profile}...");

        // Ensure default pilot places exist on central
        $this->ensurePilotPlacesExist();

        $placesQuery = DB::connection('central')->table('calendar_places');
        if ($placesOpt !== 'all') {
            $placesQuery->where('region_id', $placesOpt);
        }
        $places = $placesQuery->get();

        if ($places->isEmpty()) {
            $this->error('No verified calendar places available to compute.');
            return 1;
        }

        // 1. Create generation in building state
        $genId = $generations->createGeneration($profile, $year, [
            'total_places' => $places->count(),
            'scope_year' => $year,
        ]);

        $this->line("Created generation #{$genId} in [building] state.");

        $startDate = CarbonImmutable::create($year, 1, 1, 0, 0, 0, 'UTC');
        $endDate = CarbonImmutable::create($year, 12, 31, 0, 0, 0, 'UTC');
        $totalDaysInYear = (int) $startDate->diffInDays($endDate) + 1;

        $totalInserted = 0;

        foreach ($places as $place) {
            $this->line("Computing {$totalDaysInYear} days for place [{$place->region_id}] ({$place->latitude}, {$place->longitude})...");

            for ($dayOffset = 0; $dayOffset < $totalDaysInYear; $dayOffset++) {
                $current = $startDate->addDays($dayOffset);
                $occ = PanchangCalculator::calculate(
                    $current->year,
                    $current->month,
                    $current->day,
                    (float) $place->latitude,
                    (float) $place->longitude,
                    'PURNIMANTA'
                );

                $sunriseInstant = CarbonImmutable::parse($occ->sunriseUtc);
                $sunriseAtUs = (int) ($sunriseInstant->getTimestamp() * 1_000_000 + $sunriseInstant->micro);

                $sunsetResult = SunsetUtcCalculator::calculate(
                    $current->year,
                    $current->month,
                    $current->day,
                    (float) $place->latitude,
                    (float) $place->longitude
                );

                if ($sunsetResult->isSupported() && $sunsetResult->instant_utc !== null) {
                    $sunsetInstant = $sunsetResult->instant_utc;
                    $sunsetAtUs = (int) ($sunsetInstant->getTimestamp() * 1_000_000 + $sunsetInstant->micro);
                    $solarNoonUs = (int) (($sunriseAtUs + $sunsetAtUs) / 2);
                } else {
                    $sunsetAtUs = $sunriseAtUs + (12 * 3600 * 1_000_000);
                    $solarNoonUs = $sunriseAtUs + (6 * 3600 * 1_000_000);
                }

                // Insert into calendar_panchang_days
                DB::connection('central')->table('calendar_panchang_days')->insert([
                    'place_id' => $place->region_id,
                    'profile_code' => $profile,
                    'local_date' => $current->toDateString(),
                    'sunrise_at_us' => $sunriseAtUs,
                    'sunset_at_us' => $sunsetAtUs,
                    'solar_noon_us' => $solarNoonUs,
                    'masa_amanta' => $occ->masa['amanta'],
                    'masa_purnimanta' => $occ->masa['purnimanta'],
                    'is_adhika' => (bool) $occ->masa['is_adhika'],
                    'generation' => $genId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Insert limb events (Tithi & Nakshatra)
                DB::connection('central')->table('calendar_astronomical_events')->insert([
                    [
                        'place_id' => $place->region_id,
                        'profile_code' => $profile,
                        'event_type' => 'TITHI',
                        'index_in_series' => (int) $occ->tithi['number'],
                        'starts_at_us' => $sunriseAtUs,
                        'ends_at_us' => $sunriseAtUs + (24 * 3600 * 1_000_000),
                        'generation' => $genId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'place_id' => $place->region_id,
                        'profile_code' => $profile,
                        'event_type' => 'NAKSHATRA',
                        'index_in_series' => (int) $occ->nakshatra['number'],
                        'starts_at_us' => $sunriseAtUs,
                        'ends_at_us' => $sunriseAtUs + (24 * 3600 * 1_000_000),
                        'generation' => $genId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]);

                $totalInserted++;
            }
        }

        $this->info("Calendar build complete: staged {$totalInserted} days in generation #{$genId}.");
        $this->comment("Next step: run 'php artisan calendar:validate --generation={$genId}'");

        return 0;
    }

    private function ensurePilotPlacesExist(): void
    {
        $pilotPlaces = [
            [
                'region_id' => '01JH0000000000000000000001',
                'latitude' => 23.1765000,
                'longitude' => 75.7885000,
                'elevation_m' => 494,
                'iana_timezone' => 'Asia/Kolkata',
                'is_verified' => true,
            ],
            [
                'region_id' => '01JH0000000000000000000002',
                'latitude' => 28.6139000,
                'longitude' => 77.2090000,
                'elevation_m' => 216,
                'iana_timezone' => 'Asia/Kolkata',
                'is_verified' => true,
            ],
            [
                'region_id' => '01JH0000000000000000000003',
                'latitude' => 25.3176000,
                'longitude' => 82.9739000,
                'elevation_m' => 81,
                'iana_timezone' => 'Asia/Kolkata',
                'is_verified' => true,
            ],
        ];

        foreach ($pilotPlaces as $place) {
            DB::connection('central')->table('calendar_places')->updateOrInsert(
                ['region_id' => $place['region_id']],
                array_merge($place, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}

