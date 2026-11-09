<?php

namespace App\Modules\Booking\Application;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Domain\BookingRequestFingerprint;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingLine;
use App\Modules\Booking\Models\BookingRevision;
use App\Modules\Booking\Models\BookingTransition;
use App\Modules\Catalog\Contracts\ListsCatalogItems;
use App\Modules\Pandit\Contracts\ListsPanditProfiles;
use App\Modules\Pandit\Contracts\PanditSchedulePolicy;
use App\Modules\Temple\Contracts\TempleSchedulePolicy;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Creates a DRAFT reservation with immutable Revision 1 (Phase 2, Task 2.1.3).
 */
final class CreateReservationDraftUseCase
{
    public function __construct(
        private readonly ListsCatalogItems $catalog,
        private readonly AuditWriter $audit,
        private readonly Outbox $outbox,
        private readonly ?ListsPanditProfiles $pandits = null,
        private readonly ?PanditSchedulePolicy $schedulePolicy = null,
        private readonly ?TempleSchedulePolicy $templeSchedulePolicy = null,
    ) {
    }

    /**
     * @param array{
     *     service_sku: string,
     *     scheduled_at: string,
     *     duration_minutes?: int|null,
     *     address_id?: string|null,
     *     address_snapshot?: array<string, mixed>|null,
     *     selected_pandit_id?: string|null,
     *     selected_temple_id?: string|null,
     * } $input
     */
    public function execute(string $tenantId, string $userId, string $idempotencyKey, array $input): Booking
    {
        $sku = (string) $input['service_sku'];
        $catalogItems = $this->catalog->itemsBySkus([$sku]);

        if ($catalogItems === [] || ($catalogItems[0]['status'] ?? '') !== 'ACTIVE') {
            throw new InvalidArgumentException("Invalid or inactive service SKU '{$sku}'.");
        }

        $duration = (int) ($input['duration_minutes'] ?? 60);
        $scheduledAt = CarbonImmutable::parse($input['scheduled_at'])->utc();
        $endsAt = $scheduledAt->addMinutes($duration);

        if (! empty($input['selected_pandit_id'])) {
            $panditId = (string) $input['selected_pandit_id'];
            $panditsPort = $this->pandits ?? app(ListsPanditProfiles::class);
            $policy = $this->schedulePolicy ?? app(PanditSchedulePolicy::class);

            if (! $panditsPort->isQualifiedForService($tenantId, $panditId, $sku)) {
                throw new InvalidArgumentException("Selected pandit '{$panditId}' is not qualified or inactive for service SKU '{$sku}'.");
            }

            $verdict = $policy->isAvailableAt($tenantId, $panditId, $scheduledAt, $endsAt);
            if (! $verdict->isAvailable()) {
                throw new InvalidArgumentException("Selected pandit '{$panditId}' is not available for requested window: {$verdict->reason()}.");
            }
        }

        if (! empty($input['selected_temple_id'])) {
            $templeId = (string) $input['selected_temple_id'];
            $templePolicy = $this->templeSchedulePolicy ?? (app()->bound(TempleSchedulePolicy::class) ? app(TempleSchedulePolicy::class) : null);
            if ($templePolicy !== null) {
                $units = 1;
                $slotType = (string) ($input['slot_type'] ?? 'PUJA');
                $tVerdict = $templePolicy->evaluateSlot($tenantId, $templeId, $slotType, $scheduledAt, $endsAt, $units);
                if (! $tVerdict->isAllowed()) {
                    throw new InvalidArgumentException("Selected temple slot is unavailable: {$tVerdict->reason()}.");
                }
            }
        }

        $fingerprint = BookingRequestFingerprint::hash($input);

        $booking = new Booking();
        $booking->forceFill([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'service_sku' => $sku,
            'idempotency_key' => $idempotencyKey,
            'idempotency_hash' => $fingerprint,
            'idempotency_hash_version' => BookingRequestFingerprint::VERSION,
            'status' => BookingLifecycle::STATUS_DRAFT,
            'scheduled_at' => $scheduledAt->toDateTimeString(),
            'duration_minutes' => $duration,
            'service_start_at' => $scheduledAt->toDateTimeString(),
            'service_end_at' => $endsAt->toDateTimeString(),
            'address_snapshot' => $input['address_snapshot'] ?? null,
            'version' => 1,
        ]);

        $booking->getConnection()->transaction(function () use ($booking, $tenantId, $userId, $input, $sku, $duration, $scheduledAt): void {
            $booking->save();

            // Create Revision 1
            $revision = new BookingRevision();
            $revision->forceFill([
                'tenant_id' => $tenantId,
                'booking_id' => (string) $booking->getKey(),
                'version' => 1,
                'service_address_json' => $input['address_snapshot'] ?? null,
                'service_mode' => 'AT_LOCATION',
            ]);
            $revision->save();

            // Create Line 1
            $line = new BookingLine();
            $line->forceFill([
                'tenant_id' => $tenantId,
                'revision_id' => (string) $revision->getKey(),
                'line_number' => 1,
                'line_kind' => 'SERVICE',
                'sku' => $sku,
                'quantity' => 1,
                'server_duration_minutes' => $duration,
                'selected_pandit_id' => $input['selected_pandit_id'] ?? null,
                'selected_temple_id' => $input['selected_temple_id'] ?? null,
            ]);
            $line->save();

            $booking->current_revision_id = (string) $revision->getKey();
            $booking->save();

            // Record initial transition
            $transition = new BookingTransition();
            $transition->forceFill([
                'tenant_id' => $tenantId,
                'booking_id' => (string) $booking->getKey(),
                'from_status' => '',
                'to_status' => BookingLifecycle::STATUS_DRAFT,
                'reason' => 'created',
                'actor_id' => $userId,
            ]);
            $transition->save();

            // Audit
            $this->audit->record(
                'booking.reservation.created',
                ['module' => 'booking', 'resource_type' => 'booking', 'resource_id' => (string) $booking->getKey()],
                null,
                ['user_id' => $userId, 'service_sku' => $sku]
            );

            // Outbox
            $this->outbox->append(
                $tenantId,
                'booking',
                (string) $booking->getKey(),
                'booking.reservation.created',
                [
                    'booking_id' => (string) $booking->getKey(),
                    'user_id' => $userId,
                    'service_sku' => $sku,
                    'scheduled_at' => $scheduledAt->toDateTimeString(),
                    'revision_id' => (string) $revision->getKey(),
                ]
            );
        });

        return $booking;
    }
}
