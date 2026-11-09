<?php

namespace App\Modules\Temple\Contracts;

/**
 * Priest Council Post-Payment Approval port (Sub-phase 3.3).
 */
interface ManagesTempleApprovals
{
    /**
     * Check if ritual requires council approval and initiate pending approval.
     */
    public function initiateApprovalIfNeeded(
        string $tenantId,
        string $bookingId,
        string $templeId,
        ?string $revisionId = null,
        ?string $serviceSku = null
    ): bool;

    /**
     * Record a council decision (APPROVED or DECLINED).
     * Enforces Maker/Checker SoD: the assigned officiating priest cannot approve their own council booking.
     *
     * @return array{id: string, state: string, decision_by: int, decided_at: string}
     */
    public function recordDecision(
        string $tenantId,
        string $approvalId,
        int $actorUserId,
        string $decision,
        ?string $reason = null
    ): array;
}

