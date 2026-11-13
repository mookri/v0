<?php

declare(strict_types=1);

namespace App\Modules\Incident\Application;

use App\Modules\Incident\Models\Incident;
use App\Modules\Incident\Models\IncidentTimeline;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;

/**
 * Temple / Venue Access Denial Workflow (T-TS05-06).
 *
 * Ensures temple or venue gate denials are classified as `VENUE` (never provider
 * fault), logged for ops intervention, and excluded from provider reliability scores.
 */
final class TempleAccessIncidentService
{
    public function __construct(
        private readonly IncidentLifecycleService $lifecycle,
    ) {
    }

    public function recordAccessDenial(
        string $bookingId,
        string $templeOrVenueRef,
        string $actorId,
        string $reason,
        ?string $panditId = null,
    ): Incident {
        $incident = $this->lifecycle->declareIncident([
            'type' => 'TEMPLE_ACCESS_DENIAL',
            'severity' => 'SEV_1',
            'title' => "Venue access denial at {$templeOrVenueRef}",
            'summary' => $reason,
            'attributed_cause' => 'VENUE',
            'actor_id' => $actorId,
            'impacts' => [
                [
                    'booking_id' => $bookingId,
                    'subject_type' => $panditId !== null ? 'pandit' : null,
                    'subject_id' => $panditId,
                    'attributed_cause' => 'VENUE',
                    'devotee_outcome' => 'REALLOCATE_OR_REFUND_PENDING',
                ],
            ],
        ]);

        $tenantId = (string) app(TenantContext::class)->tenantId();
        IncidentTimeline::query()->create([
            'tenant_id' => $tenantId,
            'incident_id' => (string) $incident->getKey(),
            'entry_type' => 'VENUE_ACCESS_DENIED',
            'actor_id' => $actorId,
            'summary' => "Venue {$templeOrVenueRef} denied ceremony access; attributed strictly to VENUE.",
            'metadata_json' => ['temple_ref' => $templeOrVenueRef],
            'occurred_at' => CarbonImmutable::now('UTC')->toDateTimeString(),
            'created_at' => CarbonImmutable::now('UTC')->toDateTimeString(),
        ]);

        return $incident;
    }
}

