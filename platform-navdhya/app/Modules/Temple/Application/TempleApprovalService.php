<?php

namespace App\Modules\Temple\Application;

use App\Modules\Temple\Contracts\ManagesTempleApprovals;
use App\Modules\Temple\Models\TempleBookingApproval;
use App\Modules\Temple\Models\TemplePriest;
use App\Modules\Temple\Models\TemplePuja;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Service orchestrating temple priest council approval workflows (C3).
 */
final class TempleApprovalService implements ManagesTempleApprovals
{
    public function __construct(
        private readonly AuditWriter $audit,
        private readonly Outbox $outbox,
    ) {
    }

    public function initiateApprovalIfNeeded(
        string $tenantId,
        string $bookingId,
        string $templeId,
        ?string $revisionId = null,
        ?string $serviceSku = null
    ): bool {
        $templePuja = TemplePuja::query()
            ->where('tenant_id', $tenantId)
            ->where('temple_id', $templeId)
            ->where('requires_approval', true)
            ->first();

        if ($templePuja === null) {
            return false;
        }

        $now = CarbonImmutable::now();
        $slaMinutes = max(15, (int) ($templePuja->approval_sla_minutes ?: 120));

        $approval = new TempleBookingApproval();
        $approval->forceFill([
            'tenant_id' => $tenantId,
            'booking_id' => $bookingId,
            'temple_id' => $templeId,
            'revision_id' => $revisionId,
            'state' => TempleBookingApproval::STATE_PENDING,
            'required_role' => TemplePriest::ROLE_HEAD_PRIEST,
            'sla_expires_at' => $now->addMinutes($slaMinutes),
            'hard_expires_at' => $now->addMinutes($slaMinutes * 2),
        ]);
        $approval->save();

        $this->audit->record(
            'temple.council.approval_requested',
            ['module' => 'temple', 'resource_type' => 'temple_booking_approval', 'resource_id' => (string) $approval->getKey()],
            null,
            ['temple_id' => $templeId, 'booking_id' => $bookingId, 'sla_minutes' => $slaMinutes]
        );

        $this->outbox->append(
            $tenantId,
            'temple_booking_approval',
            (string) $approval->getKey(),
            'temple.council.approval_requested',
            [
                'approval_id' => (string) $approval->getKey(),
                'tenant_id' => $tenantId,
                'booking_id' => $bookingId,
                'temple_id' => $templeId,
                'sla_expires_at' => $approval->sla_expires_at->toIso8601String(),
            ]
        );

        return true;
    }

    public function recordDecision(
        string $tenantId,
        string $approvalId,
        int $actorUserId,
        string $decision,
        ?string $reason = null
    ): array {
        /** @var TempleBookingApproval $approval */
        $approval = TempleBookingApproval::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $approvalId)
            ->firstOrFail();

        // 1. Verify actor is a council member or head priest for this temple
        $priest = TemplePriest::query()
            ->where('tenant_id', $tenantId)
            ->where('temple_id', $approval->temple_id)
            ->where('user_id', $actorUserId)
            ->where(function ($query): void {
                $query->where('council_member', true)
                    ->orWhere('role', TemplePriest::ROLE_HEAD_PRIEST);
            })
            ->first();

        if ($priest === null) {
            throw new InvalidArgumentException("User {$actorUserId} is not an authorized temple council member.");
        }

        // 2. Maker/Checker SoD: Assigned officiating priest cannot approve their own booking
        if ($priest->pandit_id !== null) {
            $isOfficiating = \App\Modules\Pandit\Models\PanditAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('booking_id', $approval->booking_id)
                ->where('pandit_id', $priest->pandit_id)
                ->exists();

            if ($isOfficiating) {
                throw new InvalidArgumentException('Maker/Checker violation: Assigned officiating priest cannot approve their own council booking.');
            }
        }

        $validDecisions = [TempleBookingApproval::STATE_APPROVED, TempleBookingApproval::STATE_DECLINED];
        if (! in_array($decision, $validDecisions, true)) {
            throw new InvalidArgumentException("Invalid council decision '{$decision}'.");
        }

        $approval->forceFill([
            'state' => $decision,
            'decision_by' => $actorUserId,
            'decision_reason' => $reason,
            'decided_at' => now(),
        ])->save();

        $this->audit->record(
            'temple.council.decided',
            ['module' => 'temple', 'resource_type' => 'temple_booking_approval', 'resource_id' => (string) $approval->getKey()],
            null,
            ['decision' => $decision, 'decided_by' => $actorUserId, 'reason' => $reason]
        );

        return [
            'id' => (string) $approval->getKey(),
            'state' => $approval->state,
            'decision_by' => (int) $approval->decision_by,
            'decided_at' => (string) $approval->decided_at?->toJSON(),
        ];
    }
}
