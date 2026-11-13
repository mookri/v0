<?php

declare(strict_types=1);

namespace App\Modules\Booking\Console;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\PanditArrival;
use App\Platform\Outbox\Outbox;
use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Scans ASSIGNED bookings past scheduled_at + grace_minutes lacking arrival
 * evidence and emits ops readiness alerts (`pandit.arrival.overdue`) without
 * auto-transitioning NO_SHOW (TS-01 / TS-02).
 */
final class ScanMissingArrivalsCommand extends Command
{
    protected $signature = 'booking:scan-missing-arrivals {--grace=15 : Grace window in minutes} {--tenant= : Scope to tenant ID}';

    protected $description = 'Emit operations alerts for overdue ASSIGNED bookings without pandit arrival evidence';

    public function handle(TenantContext $context, Outbox $outbox): int
    {
        $grace = max(1, (int) $this->option('grace'));
        $tenantOption = $this->option('tenant');
        $cutoff = CarbonImmutable::now('UTC')->subMinutes($grace);

        $tenants = Tenant::query()
            ->where('state', Tenant::STATE_ACTIVE)
            ->when($tenantOption !== null, fn ($q) => $q->where('id', $tenantOption))
            ->get();

        $alerted = 0;

        foreach ($tenants as $tenant) {
            $tenantId = (string) $tenant->getKey();
            $context->clear();
            $context->set($tenantId, null, null, (new ConnectionResolver())->cachedFor($tenant), ['system' => 'missing-arrival-scan']);

            try {
                $overdue = Booking::query()
                    ->where('tenant_id', $tenantId)
                    ->where('status', BookingLifecycle::STATUS_ASSIGNED)
                    ->whereNotNull('scheduled_at')
                    ->where('scheduled_at', '<=', $cutoff->toDateTimeString())
                    ->get();

                foreach ($overdue as $booking) {
                    $bookingId = (string) $booking->getKey();
                    $hasArrival = PanditArrival::query()
                        ->where('tenant_id', $tenantId)
                        ->where('booking_id', $bookingId)
                        ->exists();

                    if ($hasArrival) {
                        continue;
                    }

                    $outbox->append(
                        $tenantId,
                        'booking',
                        $bookingId,
                        'booking.pandit_arrival.overdue',
                        [
                            'booking_id' => $bookingId,
                            'scheduled_at' => $booking->scheduled_at?->toJSON(),
                            'grace_minutes' => $grace,
                            'detected_at' => CarbonImmutable::now('UTC')->toJSON(),
                        ]
                    );
                    $alerted++;
                }
            } finally {
                $context->clear();
            }
        }

        $this->info("Emitted {$alerted} overdue arrival alerts.");

        return 0;
    }
}
