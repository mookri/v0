<?php

declare(strict_types=1);

namespace App\Modules\Support\Application;

use App\Domain\Trust\Parties\PartyEntitlement;
use App\Domain\Trust\References\TrustReferenceGenerator;
use App\Modules\Booking\Contracts\ReadsBookings;
use App\Modules\Support\Domain\Enums\CaseCategory;
use App\Modules\Support\Domain\Enums\CaseStatus;
use App\Modules\Support\Domain\Enums\CaseType;
use App\Modules\Support\Models\SupportCase;
use App\Modules\Support\Models\SupportCaseMessage;
use App\Modules\Support\Models\SupportCaseStatusHistory;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Opens a formal SupportCase with minted tenant-unique reference, closed
 * category/type taxonomy, safety isolation, and optional booking linkage (TS-04/TS-10/TS-16).
 */
final class OpenSupportCaseUseCase
{
    public function __construct(
        private readonly AuditWriter $audit,
        private readonly Outbox $outbox,
        private readonly ?ReadsBookings $bookings = null,
    ) {
    }

    /**
     * @param  array{
     *   reporter_user_id: int|string,
     *   subject: string,
     *   body: string,
     *   case_type: CaseType,
     *   category: CaseCategory,
     *   booking_id?: string|null,
     *   intake_channel?: string,
     *   urgency?: string|null,
     * }  $input
     */
    public function execute(array $input): SupportCase
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $now = CarbonImmutable::now('UTC');
        $caseRef = TrustReferenceGenerator::mint(
            TrustReferenceGenerator::KIND_CASE,
            $tenantId,
            (new SupportCase())->getConnectionName(),
            'support_cases',
            'case_reference',
        );

        $caseType = $input['case_type'];
        $category = $input['category'];
        $visibility = $category->requiredVisibilityScope($caseType);
        $urgency = $input['urgency'] ?? $category->defaultUrgency();
        $bookingId = $input['booking_id'] ?? null;

        if ($bookingId !== null && $this->bookings !== null) {
            $snapshot = $this->bookings->bookingSnapshotForTrust($bookingId);
            if ($snapshot === null) {
                throw new \InvalidArgumentException('Referenced booking not found in ambient tenant.');
            }
        }

        $correlationId = (string) Str::ulid();

        return (new SupportCase())->getConnection()->transaction(function () use (
            $tenantId,
            $caseRef,
            $input,
            $caseType,
            $category,
            $visibility,
            $urgency,
            $bookingId,
            $now,
            $correlationId
        ): SupportCase {
            $case = new SupportCase();
            $case->forceFill([
                'tenant_id' => $tenantId,
                'case_reference' => $caseRef,
                'reporter_user_id' => (int) $input['reporter_user_id'],
                'subject' => $input['subject'],
                'case_type' => $caseType->value,
                'category' => $category->value,
                'status' => CaseStatus::NEW->value,
                'urgency' => $urgency,
                'visibility_scope' => $visibility,
                'booking_id' => $bookingId,
                'intake_channel' => $input['intake_channel'] ?? 'web',
                'sla_policy_version' => 'v1',
                'reopen_count' => 0,
                'version' => 1,
            ]);
            $case->save();

            $caseId = (string) $case->getKey();
            $msgVisibility = $visibility === PartyEntitlement::SCOPE_RESTRICTED_SAFETY
                ? PartyEntitlement::SCOPE_REPORTER_PRIVATE
                : PartyEntitlement::SCOPE_PARTICIPANT;

            $message = new SupportCaseMessage();
            $message->forceFill([
                'tenant_id' => $tenantId,
                'case_id' => $caseId,
                'author_id' => (string) $input['reporter_user_id'],
                'author_role' => 'devotee',
                'visibility_scope' => $msgVisibility,
                'body' => $input['body'],
            ]);
            $message->save();

            $history = new SupportCaseStatusHistory();
            $history->forceFill([
                'tenant_id' => $tenantId,
                'case_id' => $caseId,
                'actor_id' => (string) $input['reporter_user_id'],
                'actor_type' => 'devotee',
                'action' => 'opened',
                'previous_state' => 'NONE',
                'new_state' => CaseStatus::NEW->value,
                'reason' => 'Case opened via '.($input['intake_channel'] ?? 'web'),
                'policy_version' => 'v1',
                'correlation_id' => $correlationId,
                'occurred_at' => $now->toDateTimeString(),
            ]);
            $history->save();

            $this->audit->record(
                'support.case.opened',
                ['module' => 'support', 'resource_type' => 'support_case', 'resource_id' => $caseId],
                null,
                [
                    'case_reference' => $caseRef,
                    'case_type' => $caseType->value,
                    'category' => $category->value,
                    'visibility_scope' => $visibility,
                ]
            );

            $this->outbox->append(
                $tenantId,
                'support',
                $caseId,
                'support.case.opened',
                [
                    'case_id' => $caseId,
                    'case_reference' => $caseRef,
                    'category' => $category->value,
                    'visibility_scope' => $visibility,
                ]
            );

            if ($visibility === PartyEntitlement::SCOPE_RESTRICTED_SAFETY) {
                $this->outbox->append(
                    $tenantId,
                    'support',
                    $caseId,
                    'support.safety_case.escalated',
                    [
                        'case_id' => $caseId,
                        'case_reference' => $caseRef,
                        'category' => $category->value,
                        'booking_id' => $bookingId,
                    ]
                );
            }

            return $case;
        });
    }
}
