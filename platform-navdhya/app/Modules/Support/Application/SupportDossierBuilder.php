<?php

declare(strict_types=1);

namespace App\Modules\Support\Application;

use App\Modules\Booking\Contracts\ReadsBookings;
use App\Modules\Feedback\Contracts\ReadsFeedbackQuality;
use App\Modules\Incident\Contracts\ManagesIncidents;
use App\Modules\Support\Models\SupportCase;
use App\Modules\Support\Models\SupportCaseResolution;
use App\Modules\Support\Models\SupportCaseStatusHistory;
use App\Modules\Support\Models\SupportGrievance;
use App\Modules\Support\Models\SupportRefundRequest;
use App\Platform\Tenancy\TenantContext;

/**
 * Closed-Loop Case Dossier Builder (E-TS-08 / §18).
 * Strictly composes across Booking, Feedback, and Incident via Deptrac Contracts.
 */
final class SupportDossierBuilder
{
    public function __construct(
        private readonly ?ReadsBookings $bookings = null,
        private readonly ?ReadsFeedbackQuality $feedback = null,
        private readonly ?ManagesIncidents $incidents = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(string $caseId): array
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $case = SupportCase::query()->where('tenant_id', $tenantId)->where('id', $caseId)->firstOrFail();

        $bookingSnapshot = $case->booking_id !== null ? $this->bookings?->bookingSnapshotForTrust($case->booking_id) : null;
        $arrival = $case->booking_id !== null ? $this->bookings?->arrivalForBooking($case->booking_id) : null;
        $feedbackReview = $case->booking_id !== null ? $this->feedback?->reviewSummaryForBooking($case->booking_id) : null;
        $linkedIncidents = $case->booking_id !== null ? ($this->incidents?->incidentSummaryForBooking($case->booking_id) ?? []) : [];

        return [
            'case' => [
                'id' => (string) $case->getKey(),
                'case_reference' => $case->case_reference,
                'case_type' => $case->case_type,
                'category' => $case->category,
                'status' => $case->status,
                'visibility_scope' => $case->visibility_scope,
            ],
            'booking' => $bookingSnapshot,
            'arrival_evidence' => $arrival,
            'feedback_submission' => $feedbackReview,
            'incidents_and_prevention' => $linkedIncidents,
            'grievance' => SupportGrievance::query()->where('tenant_id', $tenantId)->where('case_id', $caseId)->first(),
            'resolutions' => SupportCaseResolution::query()->where('tenant_id', $tenantId)->where('case_id', $caseId)->get()->all(),
            'refund_requests' => SupportRefundRequest::query()->where('tenant_id', $tenantId)->where('case_id', $caseId)->get()->all(),
            'status_history' => SupportCaseStatusHistory::query()->where('tenant_id', $tenantId)->where('case_id', $caseId)->orderBy('id')->get()->all(),
        ];
    }
}

