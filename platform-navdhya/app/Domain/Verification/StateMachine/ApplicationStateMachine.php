<?php

namespace App\Domain\Verification\StateMachine;

/**
 * Closed 15-state onboarding lifecycle machine (T-TR02-02, Closes T-08).
 * Every transition must be declared in TRANSITIONS; unknown states fail closed.
 */
final class ApplicationStateMachine
{
    public const STATES = [
        'DRAFT',
        'SUBMITTED',
        'QUEUED',
        'UNDER_REVIEW',
        'INFO_REQUESTED',
        'ESCALATED',
        'COUNTERSIGN_PENDING',
        'APPROVED_PROVISIONAL',
        'APPROVED',
        'REJECTED',
        'APPEALED',
        'EXPIRED',
        'SUSPENDED',
        'REVOKED',
        'WITHDRAWN',
    ];

    /**
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        'DRAFT' => ['SUBMITTED', 'WITHDRAWN', 'EXPIRED'],
        'SUBMITTED' => ['QUEUED', 'UNDER_REVIEW', 'INFO_REQUESTED', 'WITHDRAWN', 'EXPIRED'],
        'QUEUED' => ['UNDER_REVIEW', 'ESCALATED', 'WITHDRAWN', 'EXPIRED'],
        'UNDER_REVIEW' => [
            'INFO_REQUESTED',
            'DRAFT', // legacy request-info compatibility
            'ESCALATED',
            'COUNTERSIGN_PENDING',
            'APPROVED_PROVISIONAL',
            'APPROVED',
            'REJECTED',
        ],
        'INFO_REQUESTED' => ['SUBMITTED', 'UNDER_REVIEW', 'WITHDRAWN', 'EXPIRED'],
        'ESCALATED' => ['UNDER_REVIEW', 'COUNTERSIGN_PENDING', 'APPROVED', 'REJECTED', 'INFO_REQUESTED'],
        'COUNTERSIGN_PENDING' => ['APPROVED_PROVISIONAL', 'APPROVED', 'REJECTED', 'ESCALATED', 'UNDER_REVIEW'],
        'APPROVED_PROVISIONAL' => ['APPROVED', 'SUSPENDED', 'REVOKED', 'EXPIRED'],
        'APPROVED' => ['SUSPENDED', 'REVOKED', 'EXPIRED'],
        'REJECTED' => ['APPEALED', 'EXPIRED'],
        'APPEALED' => ['UNDER_REVIEW', 'COUNTERSIGN_PENDING', 'APPROVED', 'REJECTED'],
        'SUSPENDED' => ['APPROVED', 'REVOKED', 'EXPIRED'],
        'EXPIRED' => [],
        'REVOKED' => ['APPEALED'],
        'WITHDRAWN' => [],
    ];

    public function canTransition(string $from, string $to): bool
    {
        if (! in_array($from, self::STATES, true) || ! in_array($to, self::STATES, true)) {
            return false;
        }

        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public function assertTransition(string $from, string $to): void
    {
        if (! $this->canTransition($from, $to)) {
            throw new IllegalStateTransitionException($from, $to);
        }
    }
}
