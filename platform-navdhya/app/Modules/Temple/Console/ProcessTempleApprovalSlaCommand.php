<?php

namespace App\Modules\Temple\Console;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Domain\ResourceAllocationManager;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingTransition;
use App\Modules\Temple\Models\TempleBookingApproval;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Console\Command;

/**
 * Sweeps temple booking approvals for SLA escalation and hard deadline timeouts (Sub-phase 3.3).
 * Auto-decline triggers a 100% refund without cancellation penalties.
 */
final class ProcessTempleApprovalSlaCommand extends Command
{
    protected $signature = 'temple:process-approval-sla {--tenant= : Scope to specific tenant}';

    protected $description = 'Process temple booking council approval SLAs, escalate warnings and trigger auto-decline with 100% refund';

    public function handle(
        TenantContext $context,
        AuditWriter $audit,
        Outbox $outbox,
        ResourceAllocationManager $allocations
    ): int {
        $tenantIdOption = $this->option('tenant');
        $tenants = Tenant::query()
            ->where('state', Tenant::STATE_ACTIVE)
            ->when($tenantIdOption !== null, fn ($q) => $q->where('id', $tenantIdOption))
            ->get();

        $totalEscalated = 0;
        $totalTimedOut = 0;

        foreach ($tenants as $tenant) {
            $tenantId = (string) $tenant->getKey();
            $context->clear();
            $context->set($tenantId, null, null, (new ConnectionResolver())->cachedFor($tenant), ['system' => 'council-sla']);

            // 1. Escalate pending approvals past soft SLA
            $toEscalate = TempleBookingApproval::query()
                ->where('tenant_id', $tenantId)
                ->where('state', TempleBookingApproval::STATE_PENDING)
                ->where('sla_expires_at', '<=', now())
                ->get();

            foreach ($toEscalate as $approval) {
                $approval->forceFill([
                    'state' => TempleBookingApproval::STATE_ESCALATED,
                ])->save();

                $audit->record(
                    'temple.council.escalated',
                    ['module' => 'temple', 'resource_type' => 'temple_booking_approval', 'resource_id' => (string) $approval->getKey()],
                    null,
                    ['temple_id' => $approval->temple_id, 'booking_id' => $approval->booking_id]
                );

                $outbox->append(
                    $tenantId,
                    'temple_booking_approval',
                    (string) $approval->getKey(),
                    'temple.council.escalated',
                    [
                        'approval_id' => (string) $approval->getKey(),
                        'temple_id' => $approval->temple_id,
                        'booking_id' => $approval->booking_id,
                    ]
                );

                $totalEscalated++;
            }

            // 2. Auto-decline approvals past hard deadline -> 100% refund
            $toDecline = TempleBookingApproval::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('state', [TempleBookingApproval::STATE_PENDING, TempleBookingApproval::STATE_ESCALATED])
                ->where('hard_expires_at', '<=', now())
                ->get();

            foreach ($toDecline as $approval) {
                $approval->forceFill([
                    'state' => TempleBookingApproval::STATE_DECLINED,
                    'decision_reason' => 'approval_timeout',
                    'decided_at' => now(),
                ])->save();

                // Release held/committed temple allocations
                $allocations->releaseAllocations($tenantId, $approval->booking_id, 'APPROVAL_TIMEOUT');

                // Transition booking to REFUNDED with 100% refund note
                /** @var Booking|null $booking */
                $booking = Booking::query()
                    ->where('tenant_id', $tenantId)
                    ->where('id', $approval->booking_id)
                    ->first();

                if ($booking !== null && in_array($booking->status, [BookingLifecycle::STATUS_PAYMENT_CONFIRMED, BookingLifecycle::STATUS_CONFIRMED], true)) {
                    $from = $booking->status;
                    $booking->status = BookingLifecycle::STATUS_REFUNDED;
                    $booking->status_detail = 'APPROVAL_TIMEOUT_REFUND';
                    $booking->refund_amount_minor = $booking->total_minor;
                    $booking->version += 1;
                    $booking->save();

                    $transition = new BookingTransition();
                    $transition->forceFill([
                        'tenant_id' => $tenantId,
                        'booking_id' => (string) $booking->getKey(),
                        'from_status' => $from,
                        'to_status' => BookingLifecycle::STATUS_REFUNDED,
                        'reason' => 'approval_timeout',
                        'actor_id' => 'system:council-sla',
                    ]);
                    $transition->save();

                    $outbox->append(
                        $tenantId,
                        'booking',
                        (string) $booking->getKey(),
                        'booking.reservation.refunded',
                        [
                            'booking_id' => (string) $booking->getKey(),
                            'refund_amount_minor' => (int) $booking->total_minor,
                            'reason' => 'approval_timeout',
                        ]
                    );
                }

                $totalTimedOut++;
            }
        }

        $this->info("Council SLA Sweep: {$totalEscalated} escalated, {$totalTimedOut} timed out and refunded.");

        return 0;
    }
}

