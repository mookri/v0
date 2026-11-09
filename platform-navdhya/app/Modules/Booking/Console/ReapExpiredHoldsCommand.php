<?php

namespace App\Modules\Booking\Console;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Domain\ResourceAllocationManager;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingTransition;
use App\Modules\Booking\Models\ResourceAllocation;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Console\Command;

/**
 * Sweeps and releases expired resource holds (Phase 2, Task 2.3.3).
 */
final class ReapExpiredHoldsCommand extends Command
{
    protected $signature = 'booking:reap-expired-holds {--tenant= : Scope to specific tenant}';

    protected $description = 'Release expired checkout resource holds and transition abandoned bookings to EXPIRED';

    public function handle(
        ResourceAllocationManager $allocationManager,
        TenantContext $context,
        AuditWriter $audit
    ): int {
        $tenantIdOption = $this->option('tenant');
        $tenants = Tenant::query()
            ->where('state', Tenant::STATE_ACTIVE)
            ->when($tenantIdOption !== null, fn ($q) => $q->where('id', $tenantIdOption))
            ->get();

        $totalAllocationsReaped = 0;
        $totalBookingsExpired = 0;

        foreach ($tenants as $tenant) {
            $tenantId = (string) $tenant->getKey();
            $context->clear();
            $context->set($tenantId, null, null, (new ConnectionResolver())->cachedFor($tenant), ['system' => 'hold-reaper']);

            // 1. Reap allocations
            $expiredAllocations = ResourceAllocation::query()
                ->where('tenant_id', $tenantId)
                ->where('status', ResourceAllocation::STATUS_HELD)
                ->where('expires_at', '<=', now())
                ->get();

            $bookingIdsToCheck = $expiredAllocations->pluck('booking_id')->filter()->unique()->all();

            $reaped = $allocationManager->reapExpiredHolds($tenantId);
            $totalAllocationsReaped += $reaped;

            // 2. Check and expire bookings
            foreach ($bookingIdsToCheck as $bookingId) {
                /** @var Booking|null $booking */
                $booking = Booking::query()
                    ->where('tenant_id', $tenantId)
                    ->where('id', $bookingId)
                    ->lockForUpdate()
                    ->first();

                if ($booking !== null && $booking->status === BookingLifecycle::STATUS_PENDING_PAYMENT) {
                    $booking->status = BookingLifecycle::STATUS_EXPIRED;
                    $booking->checkout_expires_at = null;
                    $booking->version += 1;
                    $booking->save();

                    $transition = new BookingTransition();
                    $transition->forceFill([
                        'tenant_id' => $tenantId,
                        'booking_id' => (string) $booking->getKey(),
                        'from_status' => BookingLifecycle::STATUS_PENDING_PAYMENT,
                        'to_status' => BookingLifecycle::STATUS_EXPIRED,
                        'reason' => 'checkout_hold_timeout',
                        'actor_id' => 'system.hold-reaper',
                    ]);
                    $transition->save();

                    app(\App\Modules\Booking\Domain\StockReservationManager::class)->releaseStock($tenantId, $bookingId);

                    $audit->record(
                        'booking.expired',
                        ['module' => 'booking', 'resource_type' => 'booking', 'resource_id' => (string) $booking->getKey()],
                        null,
                        ['reason' => 'hold_timeout']
                    );

                    $totalBookingsExpired++;
                }
            }
        }

        $context->clear();
        $this->info("Hold reaper complete: reaped {$totalAllocationsReaped} allocation(s), expired {$totalBookingsExpired} booking(s).");

        return self::SUCCESS;
    }
}
