<?php

declare(strict_types=1);

namespace Tests\Modules\Support;

use App\Modules\Support\Domain\Enums\CaseCategory;
use App\Modules\Support\Domain\Enums\CaseStatus;
use App\Modules\Support\Domain\Enums\CaseType;
use App\Modules\Support\Domain\Enums\RemedyType;
use App\Modules\Support\Domain\Grievance\StatutoryClockService;
use App\Modules\Support\Models\SupportGrievance;
use App\Modules\Support\Models\TrustSlaPolicy;
use App\Platform\Outbox\OutboxEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Tests\TenantIntegrationTestCase;

final class GrievanceComplianceTest extends TenantIntegrationTestCase
{
    public function test_public_grievance_officer_info_is_accessible_without_auth_or_hardcoded_personal_email(): void
    {
        $res = $this->getJson('/api/v1/support/grievance-officer')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['name', 'designation', 'email', 'office_address', 'intake_channels'],
            ]);

        $this->assertNotEmpty($res->json('data.name'));
        $this->assertNotEmpty($res->json('data.email'));
    }

    public function test_statutory_calendar_month_arithmetic_clamps_end_of_month_deterministically(): void
    {
        $clock = new StatutoryClockService();

        // Non-leap year: Jan 31 + 1 calendar month -> Feb 28
        $nonLeap = $clock->applyCalendarRule(CarbonImmutable::parse('2026-01-31 10:00:00', 'UTC'), 0, 'calendar_month');
        $this->assertSame('2026-02-28 10:00:00', $nonLeap->toDateTimeString());

        // Leap year: Jan 31 + 1 calendar month -> Feb 29
        $leap = $clock->applyCalendarRule(CarbonImmutable::parse('2028-01-31 10:00:00', 'UTC'), 0, 'calendar_month');
        $this->assertSame('2028-02-29 10:00:00', $leap->toDateTimeString());
    }

    public function test_formal_grievance_issues_copy_detects_sla_breach_blocks_close_until_decision_and_tracks_nch_fallback(): void
    {
        Storage::fake('local');

        $tenant = $this->makeTenant();
        [$devotee] = $this->actingAsMembership($tenant);
        [$officer] = $this->actingAsMembership($tenant, [
            'support:case:view',
            'support:case:manage',
            'support:case:resolve',
            'support:grievance:manage',
        ]);

        $this->resolveContext($tenant);
        try {
            TrustSlaPolicy::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'scope' => 'statutory',
                'metric' => 'acknowledgment',
                'duration_minutes' => 2880,
                'calendar_handling' => 'wall_clock',
                'effective_from' => '2026-01-01 00:00:00',
                'version' => 'statutory-v1',
            ]);
            TrustSlaPolicy::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'scope' => 'statutory',
                'metric' => 'redress',
                'duration_minutes' => 43200,
                'calendar_handling' => 'calendar_month',
                'effective_from' => '2026-01-01 00:00:00',
                'version' => 'statutory-v1',
            ]);
        } finally {
            $this->clearContext();
        }

        $headers = $this->tenantHeaders($tenant);

        // 1. Open case & promote to formal grievance
        $caseRes = $this->actingAs($devotee)
            ->postJson('/api/v1/support/cases', [
                'subject' => 'Overcharged samagri outside approved quote',
                'body' => 'Paid extra INR 1500 directly to venue coordinator under pressure.',
                'case_type' => CaseType::FORMAL_GRIEVANCE->value,
                'category' => CaseCategory::ADDITIONAL_CHARGES_EXTORTION->value,
            ], $headers)
            ->assertCreated();

        $caseId = (string) $caseRes->json('data.id');

        $grievanceRes = $this->actingAs($devotee)
            ->postJson("/api/v1/support/grievances/{$caseId}", [
                'verbatim_complaint' => 'Paid extra INR 1500 directly to venue coordinator under pressure.',
            ], $headers)
            ->assertCreated();

        $this->assertStringStartsWith('NV-GRV-', (string) $grievanceRes->json('data.complaint_record_id'));
        $this->assertSame('statutory-v1', $grievanceRes->json('data.policy_version'));
        $this->assertTrue(OutboxEvent::withoutGlobalScopes()->where('tenant_id', (string) $tenant->getKey())->where('event_type', 'support.grievance.copy_issued')->exists());

        // 2. Complaint copy artefact saved and retrievable by devotee
        $copy = $this->actingAs($devotee)
            ->getJson("/api/v1/support/grievances/{$caseId}/complaint-copy", $headers)
            ->assertOk();

        $this->assertSame('Paid extra INR 1500 directly to venue coordinator under pressure.', $copy->json('data.verbatim_complaint'));
        Storage::disk('local')->assertExists("support/grievances/{$tenant->getKey()}/{$caseId}/complaint-copy.json");

        // 3. Simulate past statutory ack deadline -> SLA scanner emits support.grievance.breached
        $this->resolveContext($tenant);
        try {
            SupportGrievance::query()
                ->where('case_id', $caseId)
                ->update(['statutory_ack_due_at' => now('UTC')->subHour()->toDateTimeString()]);
        } finally {
            $this->clearContext();
        }

        $this->artisan('support:scan-grievance-slas', ['--tenant' => (string) $tenant->getKey()])
            ->assertSuccessful();
        $this->assertTrue(OutboxEvent::withoutGlobalScopes()->where('tenant_id', (string) $tenant->getKey())->where('event_type', 'support.grievance.breached')->exists());

        // 4. Transition to UNDER_INVESTIGATION & RESOLVED, but closing WITHOUT officer_decision MUST fail closed (409)
        $this->actingAs($officer)
            ->postJson("/api/v1/support/admin/cases/{$caseId}/transition", [
                'status' => CaseStatus::CLASSIFIED->value,
                'reason' => 'Grievance intake verified',
            ], $headers)
            ->assertOk();

        $this->actingAs($officer)
            ->postJson("/api/v1/support/admin/cases/{$caseId}/transition", [
                'status' => CaseStatus::UNDER_INVESTIGATION->value,
                'reason' => 'Reviewing payment logs',
            ], $headers)
            ->assertOk();

        $this->actingAs($officer)
            ->postJson("/api/v1/support/admin/cases/{$caseId}/transition", [
                'status' => CaseStatus::RESOLUTION_PROPOSED->value,
                'reason' => 'Proposing full monetary refund after ledger audit',
            ], $headers)
            ->assertOk();

        $this->actingAs($officer)
            ->postJson("/api/v1/support/admin/cases/{$caseId}/resolve", [
                'remedy_type' => RemedyType::MONETARY_ORIGINAL_METHOD->value,
                'decision_summary' => 'Confirmed unauthorized surcharge',
                'close_immediately' => true,
            ], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'grievance_decision_required');

        // 5. Record Officer decision -> closing succeeds; also test NCH manual fallback packet + docket recording
        $this->actingAs($officer)
            ->postJson("/api/v1/support/admin/grievances/{$caseId}/record-decision", [
                'decision' => 'Refund approved and warning issued to venue desk.',
            ], $headers)
            ->assertOk();

        $this->actingAs($officer)
            ->postJson("/api/v1/support/admin/cases/{$caseId}/resolve", [
                'remedy_type' => RemedyType::MONETARY_ORIGINAL_METHOD->value,
                'decision_summary' => 'Confirmed unauthorized surcharge; full refund issued',
                'close_immediately' => true,
            ], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', CaseStatus::CLOSED->value);

        $packet = $this->actingAs($officer)
            ->postJson("/api/v1/support/admin/grievances/{$caseId}/nch-referral", [
                'action' => 'prepare',
            ], $headers)
            ->assertOk();
        $this->assertSame('manual_fallback', $packet->json('data.adapter_mode'));

        $rec = $this->actingAs($officer)
            ->postJson("/api/v1/support/admin/grievances/{$caseId}/nch-referral", [
                'action' => 'record',
                'external_reference' => 'NCH-DOCKET-2026-88412',
            ], $headers)
            ->assertCreated();
        $this->assertSame('NCH-DOCKET-2026-88412', $rec->json('data.external_reference'));

        $this->actingAs($officer)
            ->postJson("/api/v1/support/admin/grievances/{$caseId}/nch-referral", [
                'action' => 'reconcile',
                'referral_id' => $rec->json('data.id'),
                'external_status' => 'RESOLVED_ON_NCH',
            ], $headers)
            ->assertOk()
            ->assertJsonPath('data.external_status', 'RESOLVED_ON_NCH');
    }
}
