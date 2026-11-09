<?php

namespace App\Modules\Customers\Console;

use App\Modules\Customers\Models\UserEvent;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Telemetry retention purge daemon (E5):
 * Irreversibly purges telemetry rows that have exceeded the statutory retention horizon.
 */
final class PurgeTelemetryCommand extends Command
{
    protected $signature = 'customers:purge-telemetry';

    protected $description = 'Purge customer telemetry rows exceeding the configured retention threshold.';

    public function handle(): int
    {
        $days = (int) config('privacy.retention.telemetry_days', 90);
        $threshold = CarbonImmutable::now('UTC')->subDays($days);

        $purgedCount = UserEvent::query()
            ->withoutGlobalScopes()
            ->where('occurred_at', '<', $threshold)
            ->delete();

        $this->info("Purged {$purgedCount} telemetry events older than {$days} days (threshold: {$threshold->toIso8601String()}).");

        return self::SUCCESS;
    }
}
