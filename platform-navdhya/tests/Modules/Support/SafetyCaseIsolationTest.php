<?php

declare(strict_types=1);

namespace Tests\Modules\Support;

use App\Domain\Trust\Parties\PartyEntitlement;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Feedback\Application\IssueFeedbackInvitationUseCase;
use App\Modules\Feedback\Models\FeedbackInvitation;
use App\Modules\Pandit\Models\PanditProfile;
use App\Modules\Support\Domain\Enums\CaseCategory;
use App\Modules\Support\Domain\Enums\CaseStatus;
use App\Modules\Support\Domain\Enums\CaseType;
use App\Modules\Support\Domain\Enums\RemedyType;
use App\Modules\Support\Models\SupportCaseMessage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Tests\TenantIntegrationTestCase;

final class SafetyCaseIsolationTest extends TenantIntegrationTestCase
{
    public function test_safety_case_enforces_compartmentalization_encryption_112_signposting_no_mediation_and_feedback_suppression(): void
    {
        Storage::fake('local');

        $tenant = $this->makeTenant();
        [$devotee] = $this->actingAsMembership($tenant);
        [$regularStaff] = $this->actingAsMembership($tenant, [
            'support:case:view',
            'support:case:manage',
            'support:case:resolve',
            'incident:record:command',
        ]);
        [$safetyLead] = $this->actingAsMembership($tenant, [
            'support:case:view',
            'support:case:manage',
            'support:case:resolve',
            'support:safety_case:access',
            'incident:record:create',
            'incident:record:command',
            'incident:record:close',
            'incident:restriction:approve',
        ]);

        $this->resolveContext($tenant);
        try {
            $pandit = PanditProfile::factory()->create(['status' => PanditProfile::STATUS_ACTIVE]);
            $booking = Booking::factory()->create([
                'user_id' => $devotee->id,
                'status' => BookingLifecycle::STATUS_COMPLETED,
            ]);
            app(IssueFeedbackInvitationUseCase::class)->issueForBooking((string) $booking->getKey());
        } finally {
            $this->clearContext();
        }

        $headers = $this->tenantHeaders($tenant);

        // 1. Open safety report -> returns 112 emergency signposting & escalates outbox
        $opened = $this->actingAs($devotee)
            ->postJson('/api/v1/support/cases', [
                'subject' => 'Intimidating behavior after ceremony',
                'body' => 'Felt unsafe when asked for cash alone.',
                'case_type' => CaseType::SAFETY_REPORT->value,
                'category' => CaseCategory::HARASSMENT_SAFETY->value,
                'booking_id' => (string) $booking->getKey(),
            ], $headers)
            ->assertCreated();

        $caseId = (string) $opened->json('data.id');
        $this->assertSame(PartyEntitlement::SCOPE_RESTRICTED_SAFETY, $opened->json('data.visibility_scope'));
        $this->assertSame('112', $opened->json('data.emergency_signposting.emergency_number'));

        // Outbox drain triggers Feedback invitation suppression for the booking
        $this->artisan('outbox:drain')->assertSuccessful();
        $this->resolveContext($tenant);
        try {
            $inv = FeedbackInvitation::query()->where('booking_id', (string) $booking->getKey())->firstOrFail();
            $this->assertSame(FeedbackInvitation::STATE_SUPPRESSED_BY_SAFETY, $inv->state);
        } finally {
            $this->clearContext();
        }

        // 2. Generic staff NEVER sees safety case or confidential vault
        $this->actingAs($regularStaff)->getJson('/api/v1/support/admin/cases', $headers)
            ->assertOk()
            ->assertJsonMissing(['id' => $caseId]);

        $this->actingAs($regularStaff)->getJson("/api/v1/support/admin/safety-cases/{$caseId}", $headers)
            ->assertStatus(403);

        // 3. Confidential message encrypted at rest; safety evidence vaulted under RESTRICTED_SAFETY
        $this->actingAs($devotee)
            ->postJson("/api/v1/support/safety-cases/{$caseId}/messages", [
                'body' => 'Exact witness phone number and statement kept private.',
            ], $headers)
            ->assertCreated()
            ->assertJsonPath('data.visibility_scope', PartyEntitlement::SCOPE_REPORTER_PRIVATE)
            ->assertJsonPath('data.encrypted', true);

        $ev = $this->actingAs($devotee)
            ->postJson("/api/v1/support/safety-cases/{$caseId}/evidence", [
                'description' => 'Doorbell clip metadata',
                'purpose' => 'Safety review corroboration',
                'content' => 'private-evidence-payload-v1',
            ], $headers)
            ->assertCreated();

        $this->assertSame(PartyEntitlement::SCOPE_RESTRICTED_SAFETY, $ev->json('data.sensitivity_class'));
        Storage::disk('local')->assertExists((string) $ev->json('data.storage_reference'));

        $this->resolveContext($tenant);
        try {
            $storedMsg = SupportCaseMessage::query()
                ->where('case_id', $caseId)
                ->where('visibility_scope', PartyEntitlement::SCOPE_REPORTER_PRIVATE)
                ->firstOrFail();
            $this->assertStringNotContainsString('Exact witness phone number', $storedMsg->body);
        } finally {
            $this->clearContext();
        }

        // 4. Safety Lead decrypts cleanly, checks triage SLA metrics, and cannot mediate via credit/apology (422)
        $this->actingAs($safetyLead)
            ->getJson("/api/v1/support/admin/safety-cases/{$caseId}", $headers)
            ->assertOk()
            ->assertJsonFragment(['body' => 'Exact witness phone number and statement kept private.']);

        $this->actingAs($safetyLead)
            ->getJson('/api/v1/support/admin/safety-cases/metrics', $headers)
            ->assertOk()
            ->assertJsonPath('data.total_safety_cases', 1);

        $this->actingAs($safetyLead)
            ->postJson("/api/v1/support/admin/cases/{$caseId}/transition", ['status' => CaseStatus::CLASSIFIED->value, 'reason' => 'Classified as restricted safety report'], $headers)
            ->assertOk();
        $this->actingAs($safetyLead)
            ->postJson("/api/v1/support/admin/cases/{$caseId}/transition", ['status' => CaseStatus::TRIAGED->value, 'reason' => 'Safety lead triaged'], $headers)
            ->assertOk();
        $this->actingAs($safetyLead)
            ->postJson("/api/v1/support/admin/cases/{$caseId}/transition", ['status' => CaseStatus::UNDER_INVESTIGATION->value, 'reason' => 'Investigating'], $headers)
            ->assertOk();
        $this->actingAs($safetyLead)
            ->postJson("/api/v1/support/admin/cases/{$caseId}/transition", ['status' => CaseStatus::RESOLUTION_PROPOSED->value, 'reason' => 'Proposing protective remedy'], $headers)
            ->assertOk();

        $this->actingAs($safetyLead)
            ->postJson("/api/v1/support/admin/cases/{$caseId}/resolve", [
                'remedy_type' => RemedyType::VOLUNTARY_CREDIT->value,
                'decision_summary' => 'Trying to mediate safety with voucher',
            ], $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'safety_no_mediation_forbidden');

        $this->actingAs($safetyLead)
            ->postJson("/api/v1/support/admin/cases/{$caseId}/resolve", [
                'remedy_type' => RemedyType::EXPLANATION_ONLY->value,
                'decision_summary' => 'Trying to mediate safety with apology only',
            ], $headers)
            ->assertStatus(422);

        // 5. Time-boxed interim restriction rejects >30 days (422) and accepts <=14 days, hidden SEV_0 stays off generic incident feed
        $this->actingAs($safetyLead)
            ->postJson('/api/v1/incident/restrictions', [
                'subject_type' => 'pandit',
                'subject_id' => (string) $pandit->getKey(),
                'restriction_kind' => 'ASSIGNMENT_PAUSE',
                'reason' => 'Interim safety review',
                'proportionality_note' => 'Limited 14-day assignment pause while safety investigation runs.',
                'expires_at' => CarbonImmutable::now('UTC')->addDays(90)->toJSON(),
                'support_case_id' => $caseId,
            ], $headers)
            ->assertStatus(422);

        $this->actingAs($safetyLead)
            ->postJson('/api/v1/incident/restrictions', [
                'subject_type' => 'pandit',
                'subject_id' => (string) $pandit->getKey(),
                'restriction_kind' => 'ASSIGNMENT_PAUSE',
                'reason' => 'Interim safety review',
                'proportionality_note' => 'Limited 14-day assignment pause while safety investigation runs.',
                'expires_at' => CarbonImmutable::now('UTC')->addDays(14)->toJSON(),
                'support_case_id' => $caseId,
            ], $headers)
            ->assertCreated();

        $sev0 = $this->actingAs($safetyLead)
            ->postJson('/api/v1/incident/incidents', [
                'type' => 'DEVOTEE_SAFETY_INCIDENT',
                'severity' => 'SEV_0',
                'title' => 'Confidential safety escalation',
            ], $headers)
            ->assertCreated();

        $this->actingAs($regularStaff)
            ->getJson('/api/v1/incident/incidents', $headers)
            ->assertOk()
            ->assertJsonMissing(['id' => $sev0->json('data.id')]);
    }
}
