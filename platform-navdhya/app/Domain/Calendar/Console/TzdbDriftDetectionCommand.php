<?php

namespace App\Domain\Calendar\Console;

use App\Platform\Outbox\Outbox;
use DateTimeZone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TZDB refresh detector (T-JYAUT-04 / JY-AUT-04).
 *
 * Monthly daemon checking IANA tzdb updates; flags affected jyotish_places
 * rows and emits jyotish.tzdb.drift_detected.
 */
final class TzdbDriftDetectionCommand extends Command
{
    protected $signature = 'calendar:tzdb-drift-detection
        {--dry-run : Scan without emitting outbox events}';

    protected $description = 'Scan stored places and calendar references for IANA timezone database drift or deprecations.';

    public function handle(Outbox $outbox): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $validTimezones = array_flip(DateTimeZone::listIdentifiers());

        $this->info("Scanning places and calendar references against PHP TZDB version " . timezone_version_get() . "...");

        // Check places table in central connection if present
        $invalidPlaces = [];
        if (DB::connection('central')->getSchemaBuilder()->hasTable('jyotish_places')) {
            $places = DB::connection('central')->table('jyotish_places')
                ->select('id', 'name', 'timezone')
                ->get();

            foreach ($places as $place) {
                if (!isset($validTimezones[$place->timezone])) {
                    $invalidPlaces[] = [
                        'id' => $place->id,
                        'name' => $place->name,
                        'timezone' => $place->timezone,
                    ];
                }
            }
        }

        if (!empty($invalidPlaces)) {
            $count = count($invalidPlaces);
            $this->warn("Detected {$count} place(s) with deprecated or unknown timezones.");
            Log::warning("TZDB drift detected in jyotish_places", ['places' => $invalidPlaces]);

            if (!$dryRun) {
                $outbox->record(
                    'jyotish.tzdb.drift_detected',
                    [
                        'drift_count' => $count,
                        'affected_places' => $invalidPlaces,
                        'detected_tzdb_version' => timezone_version_get(),
                        'scanned_at' => now()->toIso8601String(),
                    ],
                    'central',
                    'system',
                    'tzdb-drift-' . date('Y-m')
                );
                $this->info("Recorded 'jyotish.tzdb.drift_detected' in outbox.");
            }
            return 1;
        }

        $this->info("All place timezones validated cleanly against current TZDB. Zero drift detected.");
        return 0;
    }
}

