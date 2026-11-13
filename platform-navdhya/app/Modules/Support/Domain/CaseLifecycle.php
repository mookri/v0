<?php

declare(strict_types=1);

namespace App\Modules\Support\Domain;

use App\Domain\Trust\Workflow\TrustStateMachine;
use App\Modules\Support\Domain\Enums\CaseStatus;

/**
 * Authoritative SupportCase state machine backed by Domain\Trust\Workflow.
 *
 * Enforces column width invariant (TS-03) and blocks CLOSED / RESOLVED
 * transitions when zero SupportCaseResolution records exist.
 */
final class CaseLifecycle
{
    public const STATUS_COLUMN_WIDTH = 40;

    /** @var array<string, list<string>> */
    public const TRANSITIONS = [
        'NEW' => ['ACKNOWLEDGED', 'CLASSIFIED', 'WITHDRAWN'],
        'ACKNOWLEDGED' => ['CLASSIFIED', 'TRIAGED', 'WITHDRAWN'],
        'CLASSIFIED' => ['TRIAGED', 'UNDER_INVESTIGATION', 'WITHDRAWN', 'REFERRED_EXTERNALLY'],
        'TRIAGED' => ['UNDER_INVESTIGATION', 'WAITING_FOR_CUSTOMER', 'PENDING_THIRD_PARTY', 'RESOLUTION_PROPOSED', 'WITHDRAWN', 'REFERRED_EXTERNALLY'],
        'UNDER_INVESTIGATION' => ['WAITING_FOR_CUSTOMER', 'PENDING_THIRD_PARTY', 'RESOLUTION_PROPOSED', 'REFUND_PENDING', 'WITHDRAWN', 'REFERRED_EXTERNALLY'],
        'WAITING_FOR_CUSTOMER' => ['UNDER_INVESTIGATION', 'RESOLUTION_PROPOSED', 'WITHDRAWN'],
        'PENDING_THIRD_PARTY' => ['UNDER_INVESTIGATION', 'RESOLUTION_PROPOSED', 'REFERRED_EXTERNALLY'],
        'RESOLUTION_PROPOSED' => ['REFUND_PENDING', 'RESOLVED', 'UNDER_INVESTIGATION', 'REFERRED_EXTERNALLY'],
        'REFUND_PENDING' => ['REFUND_COMPLETED', 'UNDER_INVESTIGATION', 'REFERRED_EXTERNALLY'],
        'REFUND_COMPLETED' => ['RESOLVED', 'CLOSED'],
        'RESOLVED' => ['CLOSED', 'REOPENED', 'REFERRED_EXTERNALLY'],
        'CLOSED' => ['REOPENED', 'REFERRED_EXTERNALLY'],
        'REOPENED' => ['TRIAGED', 'UNDER_INVESTIGATION', 'RESOLUTION_PROPOSED'],
        'WITHDRAWN' => [],
        'REFERRED_EXTERNALLY' => ['UNDER_INVESTIGATION', 'RESOLVED', 'CLOSED'],
    ];

    public static function assertColumnWidthSafe(): void
    {
        TrustStateMachine::assertColumnWidthFits(CaseStatus::class, self::STATUS_COLUMN_WIDTH);
    }

    public static function canTransition(CaseStatus|string $from, CaseStatus|string $to): bool
    {
        $fromValue = $from instanceof CaseStatus ? $from->value : strtoupper($from);
        $toValue = $to instanceof CaseStatus ? $to->value : strtoupper($to);

        return in_array($toValue, self::TRANSITIONS[$fromValue] ?? [], true);
    }

    public static function assertCanTransition(CaseStatus|string $from, CaseStatus|string $to): void
    {
        $fromValue = $from instanceof CaseStatus ? $from->value : strtoupper($from);
        $toValue = $to instanceof CaseStatus ? $to->value : strtoupper($to);

        TrustStateMachine::assertCanTransition($fromValue, $toValue, self::TRANSITIONS, 'support_case');
    }

    public static function requiresResolutionRecord(CaseStatus|string $to): bool
    {
        $toValue = $to instanceof CaseStatus ? $to->value : strtoupper($to);

        return in_array($toValue, [CaseStatus::RESOLVED->value, CaseStatus::CLOSED->value], true);
    }
}

