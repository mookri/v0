<?php

namespace App\Modules\Onboarding\Services;

use App\Domain\Verification\Exceptions\AppealIndependenceViolationException;
use App\Domain\Verification\Models\OnboardingAppeal;
use App\Domain\Verification\Models\OnboardingDecision;
use App\Domain\Verification\StateMachine\ApplicationStateMachine;
use App\Modules\Onboarding\Models\PartnerApplication;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\PlatformScope;
use RuntimeException;

/**
 * Applicant Appeals workflow & independent decider enforcement (Closes T-16 / T-TR07-03).
 */
final class AppealService
{
    public function __construct(
        private readonly ApplicationStateMachine $stateMachine,
        private readonly AuditWriter $audit,
    ) {
    }

    /**
     * @param  list<string>  $newEvidenceIds
     */
    public function submitAppeal(
        string $tenantId,
        string $applicationId,
        string $applicantIdentityId,
        string $groundsCode,
        string $applicantStatement,
        array $newEvidenceIds = [],
    ): OnboardingAppeal {
        return PlatformScope::run('onboarding_appeal_submit', function () use (
            $tenantId,
            $applicationId,
            $applicantIdentityId,
            $groundsCode,
            $applicantStatement,
            $newEvidenceIds,
        ): OnboardingAppeal {
            $app = PartnerApplication::query()
                ->where('tenant_id', $tenantId)
                ->findOrFail($applicationId);

            if ((string) $app->identity_id !== $applicantIdentityId) {
                throw new RuntimeException('Only the application owner may submit an appeal.');
            }

            $this->stateMachine->assertTransition($app->status, PartnerApplication::STATUS_APPEALED);

            $latestDecision = OnboardingDecision::query()
                ->where('tenant_id', $tenantId)
                ->where('application_id', (string) $app->getKey())
                ->orderByDesc('created_at')
                ->first();

            $app->forceFill(['status' => PartnerApplication::STATUS_APPEALED])->save();

            $appeal = new OnboardingAppeal();
            $appeal->forceFill([
                'tenant_id' => $tenantId,
                'application_id' => (string) $app->getKey(),
                'decision_id' => $latestDecision?->getKey(),
                'applicant_identity_id' => $applicantIdentityId,
                'original_actor_id' => $latestDecision?->primary_reviewer_id ?? ($app->reviewer_id !== null ? (string) $app->reviewer_id : null),
                'original_countersign_actor_id' => $latestDecision?->second_reviewer_id,
                'applicant_statement' => $applicantStatement,
                'new_evidence_ids_json' => array_values($newEvidenceIds),
                'grounds_code' => $groundsCode,
                'sla_due_at' => now()->utc()->addDays(7),
            ])->save();

            $this->audit->record(
                'onboarding.appeal.submitted',
                ['module' => 'onboarding', 'resource_type' => 'onboarding_appeal', 'resource_id' => (string) $appeal->getKey()],
                ['application_status' => $app->getOriginal('status')],
                ['application_id' => (string) $app->getKey(), 'grounds_code' => $groundsCode],
            );

            return $appeal->fresh();
        }, tenantId: $tenantId);
    }

    public function decideAppeal(
        string $tenantId,
        string $appealId,
        string $appealReviewerId,
        string $outcome,
        string $note,
    ): OnboardingAppeal {
        if (! in_array($outcome, ['upheld', 'overturned', 'remanded'], true)) {
            throw new RuntimeException("Invalid appeal outcome [{$outcome}].");
        }

        return PlatformScope::run('onboarding_appeal_decide', function () use (
            $tenantId,
            $appealId,
            $appealReviewerId,
            $outcome,
            $note,
        ): OnboardingAppeal {
            $appeal = OnboardingAppeal::query()
                ->where('tenant_id', $tenantId)
                ->findOrFail($appealId);

            // Strict independence guard (T-TR07-03.3).
            if (
                ($appeal->original_actor_id !== null && $appeal->original_actor_id === $appealReviewerId)
                || ($appeal->original_countersign_actor_id !== null && $appeal->original_countersign_actor_id === $appealReviewerId)
            ) {
                throw new AppealIndependenceViolationException(
                    'Original decision maker or countersigner cannot adjudicate an appeal against their own decision.',
                );
            }

            $appeal->forceFill([
                'assigned_reviewer_id' => $appealReviewerId,
                'outcome' => $outcome,
                'outcome_note' => $note,
                'decided_at' => now()->utc(),
            ])->save();

            $app = PartnerApplication::query()
                ->where('tenant_id', $tenantId)
                ->findOrFail($appeal->application_id);

            if ($outcome === 'overturned' || $outcome === 'remanded') {
                $this->stateMachine->assertTransition($app->status, PartnerApplication::STATUS_UNDER_REVIEW);
                $app->forceFill([
                    'status' => PartnerApplication::STATUS_UNDER_REVIEW,
                    'rejection_code' => null,
                ])->save();
            } else {
                $this->stateMachine->assertTransition($app->status, PartnerApplication::STATUS_REJECTED);
                $app->forceFill(['status' => PartnerApplication::STATUS_REJECTED])->save();
            }

            $this->audit->record(
                'onboarding.appeal.resolved',
                ['module' => 'onboarding', 'resource_type' => 'onboarding_appeal', 'resource_id' => (string) $appeal->getKey()],
                null,
                ['outcome' => $outcome, 'appeal_reviewer_id' => $appealReviewerId],
            );

            if ($outcome === 'overturned') {
                $this->audit->record(
                    'onboarding.decision.overturned_on_appeal',
                    ['module' => 'onboarding', 'resource_type' => 'partner_application', 'resource_id' => (string) $app->getKey()],
                    null,
                    ['appeal_id' => (string) $appeal->getKey(), 'appeal_reviewer_id' => $appealReviewerId],
                );
            }

            return $appeal->fresh();
        }, tenantId: $tenantId);
    }
}

