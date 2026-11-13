<?php

namespace App\Modules\Booking\Application;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Domain\BookingRequestFingerprint;
use App\Modules\Booking\Domain\Exceptions\BookingConflictException;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingLine;
use App\Modules\Booking\Models\BookingRevision;
use App\Modules\Booking\Models\BookingTransition;
use App\Modules\Catalog\Contracts\ListsCatalogItems;
use App\Modules\Catalog\Contracts\ResolvesServiceDurations;
use App\Modules\Catalog\Contracts\ResolvesServiceKitBindings;
use App\Modules\Pandit\Contracts\ListsPanditProfiles;
use App\Modules\Pandit\Contracts\PanditSchedulePolicy;
use App\Modules\Temple\Contracts\TempleSchedulePolicy;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Creates a DRAFT reservation with immutable Revision 1 (Phase 2, Task 2.1.3; Program F2/F3).
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
        private readonly ?ResolvesServiceDurations $durationResolver = null,
        private readonly ?ResolvesServiceKitBindings $kitBindingsResolver = null,
    ) {
    }

    /**
     * @param array{
     *     service_sku: string,
     *     scheduled_at: string,
     *     duration_minutes?: int|null,
     *     participants?: int|null,
     *     party_size?: int|null,
     *     package_tier?: string|null,
     *     tradition?: string|null,
     *     address_id?: string|null,
     *     address_snapshot?: array<string, mixed>|null,
     *     selected_pandit_id?: string|null,
     *     selected_temple_id?: string|null,
     *     kit_sku?: string|null,
     *     kit_quantity?: int|null,
     *     kit_optins?: list<string>|null,
     *     selected_vendor_id?: string|null,
     * } $input
     */
    public function execute(string $tenantId, string $userId, string $idempotencyKey, array $input): Booking
    {
        $sku = (string) $input['service_sku'];
        $catalogItems = $this->catalog->itemsBySkus([$sku]);

        if ($catalogItems === [] || ($catalogItems[0]['status'] ?? '') !== 'ACTIVE') {
            throw new InvalidArgumentException("Invalid or inactive service SKU '{$sku}'.");
        }

        $durationResolver = $this->durationResolver ?? (app()->bound(ResolvesServiceDurations::class) ? app(ResolvesServiceDurations::class) : null);
        $kitBindingsResolver = $this->kitBindingsResolver ?? (app()->bound(ResolvesServiceKitBindings::class) ? app(ResolvesServiceKitBindings::class) : null);

        $participants = (int) ($input['participants'] ?? $input['party_size'] ?? 1);
        $packageTier = isset($input['package_tier']) ? (string) $input['package_tier'] : null;
        $tradition = isset($input['tradition']) ? (string) $input['tradition'] : null;
        $scheduledAt = CarbonImmutable::parse($input['scheduled_at'])->utc();

        $durationRuleSnapshot = null;
        if ($durationResolver !== null) {
            $durationResult = $durationResolver->resolveDuration(
                sku: $sku,
                participants: $participants,
                packageTier: $packageTier,
                tradition: $tradition,
                at: $scheduledAt
            );
            $serverDuration = $durationResult->minutes;
            $durationRuleSnapshot = $durationResult->toArray();

            // Drift rule: server computes and echoes; 409 duration_drift ONLY when a client explicitly asserted a conflicting duration on a SKU with authoritative rules configured
            if ($durationResult->isAuthoritative && isset($input['duration_minutes']) && $input['duration_minutes'] !== null) {
                $requestedDuration = (int) $input['duration_minutes'];
                if ($requestedDuration !== $serverDuration) {
                    throw new BookingConflictException(
                        "Client requested duration {$requestedDuration}m conflicts with authoritative server duration {$serverDuration}m for SKU '{$sku}'.",
                        'duration_drift'
                    );
                }
                $duration = $serverDuration;
            } elseif (! $durationResult->isAuthoritative && isset($input['duration_minutes']) && $input['duration_minutes'] !== null) {
                $duration = (int) $input['duration_minutes'];
            } else {
                $duration = $serverDuration;
            }
        } else {
            $duration = (int) ($input['duration_minutes'] ?? 60);
        }

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

        $booking->getConnection()->transaction(function () use (
            $booking,
            $tenantId,
            $userId,
            $input,
            $sku,
            $duration,
            $durationRuleSnapshot,
            $scheduledAt,
            $kitBindingsResolver,
            $participants
        ): void {
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

            // Create Line 1 (Service)
            $line = new BookingLine();
            $line->forceFill([
                'tenant_id' => $tenantId,
                'revision_id' => (string) $revision->getKey(),
                'line_number' => 1,
                'line_kind' => 'SERVICE',
                'sku' => $sku,
                'quantity' => 1,
                'server_duration_minutes' => $duration,
                'duration_rule_snapshot' => $durationRuleSnapshot,
                'selected_pandit_id' => $input['selected_pandit_id'] ?? null,
                'selected_temple_id' => $input['selected_temple_id'] ?? null,
            ]);
            $line->save();
            $primaryLineId = (string) $line->getKey();

            $lineNumber = 2;
            $emittedKitSkus = [];

            // Expand kit bindings if resolver is available (F3)
            if ($kitBindingsResolver !== null) {
                $bindings = $kitBindingsResolver->bindingsForService($sku);
                $optIns = (array) ($input['kit_optins'] ?? []);
                $explicitKitSku = $input['kit_sku'] ?? null;

                foreach ($bindings as $binding) {
                    $obligation = strtoupper($binding['obligation']);
                    $shouldAdd = false;
                    $priceCents = 0;
                    $qty = (int) ($binding['quantity_per_party'] ?? 1);

                    if ($obligation === 'REQUIRED') {
                        $shouldAdd = true;
                        $qty = $qty * max(1, $participants);
                    } elseif ($obligation === 'INCLUDED') {
                        $shouldAdd = true;
                        $priceCents = 0;
                    } elseif ($obligation === 'OPTIONAL') {
                        if ($explicitKitSku === $binding['kit_sku'] || in_array($binding['kit_sku'], $optIns, true) || in_array($binding['binding_id'], $optIns, true)) {
                            $shouldAdd = true;
                            $qty = (int) ($input['kit_quantity'] ?? $qty);
                        }
                    }

                    if ($shouldAdd) {
                        $kitLine = new BookingLine();
                        $kitLine->forceFill([
                            'tenant_id' => $tenantId,
                            'revision_id' => (string) $revision->getKey(),
                            'parent_line_id' => $primaryLineId,
                            'line_number' => $lineNumber++,
                            'line_kind' => 'KIT',
                            'sku' => (string) $binding['kit_sku'],
                            'quantity' => $qty,
                            'price_cents' => $priceCents,
                            'server_duration_minutes' => 0,
                            'selected_vendor_id' => $input['selected_vendor_id'] ?? null,
                        ]);
                        $kitLine->save();
                        $emittedKitSkus[] = (string) $binding['kit_sku'];
                    }
                }
            }

            // Legacy fallback if caller supplied explicit kit_sku not already emitted by bindings
            if (! empty($input['kit_sku']) && ! in_array((string) $input['kit_sku'], $emittedKitSkus, true)) {
                $kitLine = new BookingLine();
                $kitLine->forceFill([
                    'tenant_id' => $tenantId,
                    'revision_id' => (string) $revision->getKey(),
                    'parent_line_id' => $primaryLineId,
                    'line_number' => $lineNumber++,
                    'line_kind' => 'KIT',
                    'sku' => (string) $input['kit_sku'],
                    'quantity' => (int) ($input['kit_quantity'] ?? 1),
                    'price_cents' => 0,
                    'server_duration_minutes' => 0,
                    'selected_vendor_id' => $input['selected_vendor_id'] ?? null,
                ]);
                $kitLine->save();
            }

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

            $traceId = $input['trace_id'] ?? $input['query_trace_id'] ?? null;

            // Outbox
            $outboxPayload = [
                'booking_id' => (string) $booking->getKey(),
                'user_id' => $userId,
                'service_sku' => $sku,
                'scheduled_at' => $scheduledAt->toDateTimeString(),
                'revision_id' => (string) $revision->getKey(),
            ];
            if ($traceId !== null) {
                $outboxPayload['trace_id'] = (string) $traceId;
            }

            $this->outbox->append(
                $tenantId,
                'booking',
                (string) $booking->getKey(),
                'booking.reservation.created',
                $outboxPayload
            );
        });

        return $booking;
    }
}
