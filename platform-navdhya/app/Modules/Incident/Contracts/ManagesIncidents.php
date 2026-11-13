<?php

declare(strict_types=1);

namespace App\Modules\Incident\Contracts;

/**
 * Incident read/command port allowing Booking and Support to query
 * attributable causes (§6.3) and open incidents without cross-module coupling.
 */
interface ManagesIncidents
{
    public function openForBooking(
        string $bookingId,
        string $type,
        string $severity,
        string $title,
        string $attributedCause = 'UNKNOWN',
    ): string;

    /**
     * @return list<array{booking_id: string, attributed_cause: string, devotee_outcome: string}>
     */
    public function impactsForBookings(array $bookingIds): array;

    /**
     * Closed-loop summary of incidents and prevention tasks linked to a booking (E-TS-08).
     *
     * @return list<array{incident_id: string, incident_reference: string, type: string, severity: string, status: string, attributed_cause: string, prevention_task_ids: list<string>}>
     */
    public function incidentSummaryForBooking(string $bookingId): array;
}

