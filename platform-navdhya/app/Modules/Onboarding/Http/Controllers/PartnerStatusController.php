<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Domain\Verification\Claims\ClaimManager;
use App\Domain\Verification\Lifecycle\DecisionExplanationService;
use App\Domain\Verification\Lifecycle\ReviewQueuePrioritizer;
use App\Domain\Verification\Models\VerificationBankInstrument;
use App\Domain\Verification\Models\VerificationClaim;
use App\Domain\Verification\Models\VerificationTaxRegistration;
use App\Modules\Onboarding\Models\PartnerApplication;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PartnerStatusController
{
    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ok(Request $request, array $data, int $status = 200): JsonResponse
    {
        return new JsonResponse([
            'data' => $data,
            'meta' => ['request_id' => $this->requestId($request)],
        ], $status);
    }

    /**
     * Partner-facing claim truth status, masked fiscal refs, explanation, and cache revalidation tag (T-TR08-03, T-TR08-04, T-TR08-06).
     */
    public function show(
        Request $request,
        TenantContext $context,
        ClaimManager $claims,
        DecisionExplanationService $explainer,
    ): JsonResponse {
        $tenantId = $context->tenantId();
        $identityId = (string) $request->user()?->getKey();
        $subjectType = (string) $request->query('subject_type', 'pandit');
        $subjectId = (string) $request->query('subject_id', $identityId);

        $application = PartnerApplication::query()
            ->where('tenant_id', $tenantId)
            ->where('identity_id', $identityId)
            ->orderByDesc('created_at')
            ->first();

        $claimRows = VerificationClaim::query()
            ->with('scopes')
            ->where('tenant_id', $tenantId)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->orderByDesc('created_at')
            ->get()
            ->map(static fn(VerificationClaim $c): array => [
                'id' => (string) $c->getKey(),
                'claim_kind' => $c->claim_kind,
                'status' => $c->status,
                'mode' => $c->mode,
                'confidence' => $c->confidence,
                'legacy_asserted' => (bool) $c->legacy_asserted,
                'reason_code' => $c->reason_code,
                'policy_version' => $c->policy_version,
                'expires_at' => $c->expires_at?->toIso8601String(),
                'scopes' => $c->scopes->map(static fn($s): array => [
                    'type' => $s->scope_type,
                    'value' => $s->scope_value,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        $trustSummary = $claims->summarizeTrustProfile($tenantId, $subjectType, $subjectId);
        $explanation = $explainer->explain(
            $application?->rejection_code,
            $application?->status ?? 'DRAFT',
        );

        $bankMasked = VerificationBankInstrument::query()
            ->where('tenant_id', $tenantId)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->orderByDesc('created_at')
            ->get()
            ->map(static fn(VerificationBankInstrument $b): array => $b->toMaskedArray())
            ->values()
            ->all();

        $taxMasked = VerificationTaxRegistration::query()
            ->where('tenant_id', $tenantId)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->orderByDesc('created_at')
            ->get()
            ->map(static fn(VerificationTaxRegistration $t): array => $t->toMaskedArray())
            ->values()
            ->all();

        return $this->ok($request, [
            'application_id' => $application?->getKey(),
            'application_status' => $application?->status ?? 'NONE',
            'trust_summary' => $trustSummary,
            'claims' => $claimRows,
            'bank_instruments' => $bankMasked,
            'tax_registrations' => $taxMasked,
            'explanation' => $explanation,
            'revalidation_tag' => "onboarding-status:{$tenantId}:{$subjectId}",
            'poll_interval_ms' => 15000,
        ]);
    }

    /**
     * Workload- & skill-aware reviewer queue with transparent component scores (T-TR08-02).
     */
    public function prioritizedQueue(
        Request $request,
        TenantContext $context,
        ReviewQueuePrioritizer $prioritizer,
    ): JsonResponse {
        $tenantId = $context->tenantId();
        $apps = PartnerApplication::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', [
                PartnerApplication::STATUS_SUBMITTED,
                PartnerApplication::STATUS_QUEUED,
                PartnerApplication::STATUS_UNDER_REVIEW,
                PartnerApplication::STATUS_ESCALATED,
                PartnerApplication::STATUS_COUNTERSIGN_PENDING,
                PartnerApplication::STATUS_APPEALED,
            ])
            ->get();

        $scored = $apps->map(static function (PartnerApplication $app) use ($prioritizer): array {
            $ageHours = max(0.0, (float) $app->created_at?->diffInHours(now()));
            $risk = $app->status === PartnerApplication::STATUS_ESCALATED ? 0.90 : 0.30;
            $urgency = $app->status === PartnerApplication::STATUS_APPEALED ? 0.85 : 0.45;
            $complexity = $app->track === 'temple' ? 0.80 : 0.40;

            $rank = $prioritizer->score($app->track, $risk, $ageHours, $urgency, $complexity);

            return [
                'application_id' => (string) $app->getKey(),
                'track' => $app->track,
                'status' => $app->status,
                'priority_score' => $rank['priority_score'],
                'component_scores' => $rank['component_scores'],
                'recommended_skill' => $rank['recommended_skill'],
                'routing_rationale' => $rank['routing_rationale'],
            ];
        })->sortByDesc('priority_score')->values()->all();

        return $this->ok($request, ['queue' => $scored]);
    }

    /**
     * Public/customer-facing data-derived badge DTO (Closes T-02).
     */
    public function trustBadge(
        string $subjectType,
        string $subjectId,
        Request $request,
        TenantContext $context,
        ClaimManager $claims,
    ): JsonResponse {
        $summary = $claims->summarizeTrustProfile($context->tenantId(), $subjectType, $subjectId);

        return $this->ok($request, [
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'badge_tier' => $summary['badge_tier'],
            'label' => $summary['label'],
            'highest_mode' => $summary['highest_mode'],
            'legacy_asserted' => $summary['legacy_asserted'],
            'claim_kinds' => $summary['claim_kinds'],
        ]);
    }
}

