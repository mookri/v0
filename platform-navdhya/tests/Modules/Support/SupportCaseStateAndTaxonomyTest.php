<?php

declare(strict_types=1);

namespace Tests\Modules\Support;

use App\Domain\Trust\Parties\PartyEntitlement;
use App\Modules\Support\Application\OpenSupportCaseUseCase;
use App\Modules\Support\Application\ResolveSupportCaseUseCase;
use App\Modules\Support\Application\SupportCaseTransitionService;
use App\Modules\Support\Domain\Enums\CaseCategory;
use App\Modules\Support\Domain\Enums\CaseStatus;
use App\Modules\Support\Domain\Enums\CaseType;
use App\Modules\Support\Domain\Enums\RemedyType;
use App\Modules\Support\Models\SupportCase;
use Tests\TenantIntegrationTestCase;

final class SupportCaseStateAndTaxonomyTest extends TenantIntegrationTestCase
{
    public function test_closed_blocked_without_resolution_and_all_states_reachable(): void
    {
        $tenant = $this->makeTenant();
        [$devotee] = $this->actingAsMembership($tenant);
        [$staff] = $this->actingAsMembership($tenant, [
            'support:case:view',
            'support:case:manage',
            'support:case:resolve',
        ]);
        $headers = $this->tenantHeaders($tenant);

        // Open formal case via HTTP
        $opened = $this->actingAs($devotee)
            ->postJson('/api/v1/support/cases', [
                'subject' => 'Extra demands during griha pravesh',
                'body' => 'Pandit demanded extra cash mid-ceremony.',
                'case_type' => CaseType::SERVICE_COMPLAINT->value,
                'category' => CaseCategory::ADDITIONAL_CHARGES_EXTORTION->value,
            ], $headers)
            ->assertCreated();

        $caseId = (string) $opened->json('data.id');
        $this->assertStringStartsWith('NV-CASE-', (string) $opened->json('data.case_reference'));
        $this->assertSame(CaseCategory::ADDITIONAL_CHARGES_EXTORTION->value, $opened->json('data.category'));

        // Transition ACK -> CLASSIFIED -> TRIAGED -> UNDER_INVESTIGATION
        foreach ([
            CaseStatus::ACKNOWLEDGED,
            CaseStatus::CLASSIFIED,
            CaseStatus::TRIAGED,
            CaseStatus::UNDER_INVESTIGATION,
        ] as $target) {
            $this->actingAs($staff)
                ->postJson("/api/v1/support/admin/cases/{$caseId}/transition", [
                    'status' => $target->value,
                    'reason' => 'Progressing investigation step '.$target->value,
                ], $headers)
                ->assertOk()
                ->assertJsonPath('data.status', $target->value);
        }

        // Cannot jump directly to RESOLVED/CLOSED without SupportCaseResolution
        $this->actingAs($staff)
            ->postJson("/api/v1/support/admin/cases/{$caseId}/transition", [
                'status' => CaseStatus::RESOLUTION_PROPOSED->value,
                'reason' => 'Proposing resolution',
            ], $headers)
            ->assertOk();

        $this->actingAs($staff)
            ->postJson("/api/v1/support/admin/cases/{$caseId}/transition", [
                'status' => CaseStatus::RESOLVED->value,
                'reason' => 'Attempting close without resolution record',
            ], $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'resolution_required');

        // Formal resolve with remedy succeeds and reaches CLOSED
        $this->actingAs($staff)
            ->postJson("/api/v1/support/admin/cases/{$caseId}/resolve", [
                'remedy_type' => RemedyType::EXPLANATION_ONLY->value,
                'decision_summary' => 'Verified invoice vs fee schedule and warned partner.',
                'close' => true,
            ], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', CaseStatus::CLOSED->value);

        // Verify remaining states are cleanly reachable via SupportCaseTransitionService
        $this->resolveContext($tenant);
        try {
            $transitions = app(SupportCaseTransitionService::class);
            $opener = app(OpenSupportCaseUseCase::class);
            $resolver = app(ResolveSupportCaseUseCase::class);

            $transitions->transition($caseId, CaseStatus::REOPENED, (string) $staff->id, 'staff', 'reopen', 'Devotee requested follow-up');
            $transitions->transition($caseId, CaseStatus::UNDER_INVESTIGATION, (string) $staff->id, 'staff', 'reinvestigate', 'Reopened check');
            $transitions->transition($caseId, CaseStatus::WAITING_FOR_CUSTOMER, (string) $staff->id, 'staff', 'await_customer', 'Need receipt photo');
            $transitions->transition($caseId, CaseStatus::UNDER_INVESTIGATION, (string) $staff->id, 'staff', 'received', 'Customer replied');
            $transitions->transition($caseId, CaseStatus::PENDING_THIRD_PARTY, (string) $staff->id, 'staff', 'await_temple', 'Temple desk check');
            $transitions->transition($caseId, CaseStatus::UNDER_INVESTIGATION, (string) $staff->id, 'staff', 'temple_replied', 'Temple responded');
            $transitions->transition($caseId, CaseStatus::REFUND_PENDING, (string) $staff->id, 'staff', 'refund_req', 'Pending refund execution');
            $transitions->transition($caseId, CaseStatus::REFUND_COMPLETED, (string) $staff->id, 'staff', 'refund_done', 'Finance settled');
            $resolver->resolve($caseId, RemedyType::MONETARY_ORIGINAL_METHOD, 'Final settlement completed', (string) $staff->id);
            $transitions->transition($caseId, CaseStatus::REFERRED_EXTERNALLY, (string) $staff->id, 'staff', 'nch_ref', 'Referred to external portal');

            $withdrawable = $opener->execute([
                'reporter_user_id' => $devotee->id,
                'subject' => 'Withdrawable inquiry',
                'body' => 'Never mind',
                'case_type' => CaseType::INFORMATION_REQUEST,
                'category' => CaseCategory::BOOKING_SCHEDULE,
            ]);
            $transitions->transition((string) $withdrawable->getKey(), CaseStatus::WITHDRAWN, (string) $devotee->id, 'devotee', 'withdraw', 'Resolved privately');

            // Assert all 15 enum states have valid history entries
            $reached = \App\Modules\Support\Models\SupportCaseStatusHistory::query()
                ->pluck('new_state')
                ->unique()
                ->all();
            foreach (CaseStatus::cases() as $caseState) {
                $this->assertContains($caseState->value, $reached, "State {$caseState->value} was never reached in reachability sweep.");
            }
        } finally {
            $this->clearContext();
        }
    }

    public function test_safety_case_is_classified_and_hidden_from_generic_staff_queue(): void
    {
        $tenant = $this->makeTenant();
        [$devotee] = $this->actingAsMembership($tenant);
        [$normalStaff] = $this->actingAsMembership($tenant, ['support:case:view']);
        $headers = $this->tenantHeaders($tenant);

        $safetyRes = $this->actingAs($devotee)
            ->postJson('/api/v1/support/cases', [
                'subject' => 'Harassment and unauthorized video',
                'body' => 'Unwanted photos were taken inside home sanctuary.',
                'case_type' => CaseType::SAFETY_REPORT->value,
                'category' => CaseCategory::HARASSMENT_SAFETY->value,
            ], $headers)
            ->assertCreated();

        $safetyId = (string) $safetyRes->json('data.id');
        $this->assertSame(PartyEntitlement::SCOPE_RESTRICTED_SAFETY, $safetyRes->json('data.visibility_scope'));

        // Normal staff CANNOT see or fetch the restricted safety case
        $list = $this->actingAs($normalStaff)
            ->getJson('/api/v1/support/admin/cases', $headers)
            ->assertOk();
        $this->assertSame([], $list->json('data'));

        $this->actingAs($normalStaff)
            ->getJson("/api/v1/support/admin/cases/{$safetyId}", $headers)
            ->assertNotFound();
    }
}
