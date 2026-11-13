<?php

declare(strict_types=1);

namespace App\Modules\Incident\Application;

use App\Modules\Booking\Contracts\ReadsBookings;
use App\Modules\Incident\Models\Incident;
use App\Modules\Pandit\Contracts\ManagesPanditAssignments;

/**
 * Privacy-Bounded Emergency Unavailability Reporting (E-TS-06 / TS-13).
 *
 * Accepts strictly bounded reason categories (`FAMILY_EMERGENCY`, `ILLNESS`,
 * `TRAVEL_DISRUPTION`, `SAFETY_CONCERN`, `OTHER`) and rejects clinical/medical
 * payloads so pandit health details are never stored on platform.
 */
final class EmergencyUnavailabilityUseCase
{
    public const REASON_CATEGORIES = [
        'FAMILY_EMERGENCY',
        'ILLNESS',
        'TRAVEL_DISRUPTION',
        'SAFETY_CONCERN',
        'OTHER',
    ];

    public const FORBIDDEN_MEDICAL_KEYS = [
        'diagnosis',
        'medical_certificate',
        'prescription',
        'clinical_notes',
        'health_record',
    ];

    public function __construct(
        private readonly IncidentLifecycleService $lifecycle,
        private readonly ReadsBookings $bookings,
        private readonly ManagesPanditAssignments $assignments,
    ) {
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function execute(array $input): Incident
    {
        foreach (self::FORBIDDEN_MEDICAL_KEYS as $forbiddenKey) {
            if (array_key_exists($forbiddenKey, $input) && $input[$forbiddenKey] !== null && $input[$forbiddenKey] !== '') {
                throw new \InvalidArgumentException(
                    "privacy_medical_forbidden: Field '{$forbiddenKey}' is not accepted; select a broad category without uploading medical records."
                );
            }
        }

        $category = strtoupper((string) ($input['reason_category'] ?? ''));
        if (! in_array($category, self::REASON_CATEGORIES, true)) {
            throw new \InvalidArgumentException("Invalid unavailability reason_category: {$category}");
        }

        $bookingId = (string) $input['booking_id'];
        $snapshot = $this->bookings->bookingSnapshotForTrust($bookingId);
        if ($snapshot === null) {
            throw new \RuntimeException('not_found: Booking not found.');
        }

        $assignment = $this->assignments->assignmentForBooking($bookingId);
        $panditId = (string) ($input['pandit_id'] ?? $assignment['pandit_id'] ?? '');

        return $this->lifecycle->declareIncident([
            'type' => 'EMERGENCY_UNAVAILABILITY',
            'severity' => 'SEV_1',
            'title' => "Emergency unavailability ({$category}) for booking {$bookingId}",
            'summary' => isset($input['notes']) ? (string) $input['notes'] : "Category: {$category}",
            'attributed_cause' => 'PROVIDER',
            'actor_id' => (string) $input['actor_id'],
            'impacts' => [
                [
                    'booking_id' => $bookingId,
                    'subject_type' => $panditId !== '' ? 'pandit' : null,
                    'subject_id' => $panditId !== '' ? $panditId : null,
                    'attributed_cause' => 'PROVIDER',
                    'devotee_outcome' => 'RECOVERY_REQUESTED',
                ],
            ],
        ]);
    }
}

