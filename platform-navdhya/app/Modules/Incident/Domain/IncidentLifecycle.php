<?php

declare(strict_types=1);

namespace App\Modules\Incident\Domain;

use App\Domain\Trust\Workflow\TrustStateMachine;

/**
 * Ceremony-extended Incident enums and non-bypassable state transitions (E-TS-05 / ADR-0063).
 */
final class IncidentLifecycle
{
    public const SEVERITY_ORDER = [
        'SEV_0' => 0,
        'SEV_1' => 1,
        'SEV_2' => 2,
        'SEV_3' => 3,
        'SEV_4' => 4,
    ];

    public const TYPES = [
        'SERVICE_DEGRADATION', 'SERVICE_OUTAGE', 'DATA_INTEGRITY', 'SECURITY',
        'PAYMENT', 'FULFILLMENT', 'COMMUNICATION', 'THIRD_PARTY', 'INFRASTRUCTURE', 'UNKNOWN',
        'PANDIT_NO_SHOW', 'LATE_ARRIVAL', 'EMERGENCY_UNAVAILABILITY', 'RITUAL_MISCONDUCT',
        'SAMAGRI_SHORTFALL', 'TEMPLE_ACCESS_DENIAL', 'STREAMING_DISRUPTION',
        'PRASAD_FULFILLMENT_FAILURE', 'DEVOTEE_SAFETY_INCIDENT', 'PRIVACY_BREACH',
    ];

    public const CAUSES = [
        'PROVIDER',
        'VENUE',
        'WEATHER_FORCE_MAJEURE',
        'PLATFORM',
        'DEVOTEE',
        'UNKNOWN',
    ];

    public const COMMAND_ROLES = [
        'COMMANDER',
        'TECHNICAL_LEAD',
        'OPERATIONS_COORDINATOR',
        'COMMUNICATIONS_LEAD',
        'SAFETY_LEAD',
        'PROVIDER_LIAISON',
        'RECORDER',
    ];

    /** @var array<string, list<string>> */
    public const TRANSITIONS = [
        'DETECTED' => ['AWAITING_VERIFICATION', 'TRIAGED', 'CANCELLED', 'MERGED'],
        'AWAITING_VERIFICATION' => ['TRIAGED', 'DECLARED', 'CANCELLED', 'MERGED'],
        'TRIAGED' => ['DECLARED', 'CONTAINED', 'CANCELLED', 'MERGED'],
        'DECLARED' => ['CONTAINED', 'RECOVERY_IN_PROGRESS', 'MITIGATING', 'CANCELLED', 'MERGED'],
        'CONTAINED' => ['RECOVERY_IN_PROGRESS', 'MITIGATING', 'MONITORING'],
        'RECOVERY_IN_PROGRESS' => ['MITIGATING', 'MONITORING', 'RESOLVED'],
        'MITIGATING' => ['RECOVERY_IN_PROGRESS', 'MONITORING', 'DECLARED', 'CANCELLED', 'MERGED'],
        'MONITORING' => ['POSTMORTEM_PENDING', 'RESOLVED', 'MITIGATING'],
        'POSTMORTEM_PENDING' => ['RESOLVED', 'MONITORING'],
        'RESOLVED' => ['POSTMORTEM_PENDING', 'CLOSED', 'MITIGATING'],
        'CLOSED' => [],
        'CANCELLED' => [],
        'MERGED' => [],
    ];

    public static function assertCanTransition(string $from, string $to): void
    {
        TrustStateMachine::assertCanTransition($from, $to, self::TRANSITIONS, 'incident');
    }

    public static function isDowngrade(string $previousSeverity, string $newSeverity): bool
    {
        $prevRank = self::SEVERITY_ORDER[$previousSeverity] ?? 99;
        $newRank = self::SEVERITY_ORDER[$newSeverity] ?? 99;

        return $newRank > $prevRank;
    }

    public static function isSafetyType(string $type, string $severity): bool
    {
        return $severity === 'SEV_0' || in_array($type, ['DEVOTEE_SAFETY_INCIDENT', 'PRIVACY_BREACH'], true);
    }
}
