<?php

declare(strict_types=1);

namespace Tests\Modules\Feedback;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Customers\Models\CustomerFamilyMember;
use App\Modules\Feedback\Application\IssueFeedbackInvitationUseCase;
use App\Modules\Feedback\Application\RecomputeFeedbackQualityService;
use App\Modules\Feedback\Models\FeedbackAppeal;
use App\Modules\Feedback\Models\FeedbackInvitation;
use App\Modules\Feedback\Models\FeedbackModerationAction;
use App\Modules\Feedback\Models\FeedbackPublication;
use App\Modules\Feedback\Models\FeedbackSubmission;
use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditProfile;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

final class FeedbackLifecycleTest extends TenantIntegrationTestCase
{
    public function test_cancelled_and_no_show_bookings_redirect_to_support_report_and_reject_submission(): void
    {
        $tenant = $this->makeTenant();
        [$devotee] = $this->actingAsMembership($tenant);

        $this->resolveContext($tenant);
        try {
            $cancelled = Booking::factory()->create([
                'user_id' => $devotee->id,
                'status' => BookingLifecycle::STATUS_CANCELLED,
            ]);
            $noShow = Booking::factory()->create([
                'user_id' => $devotee->id,
                'status' => BookingLifecycle::STATUS_NO_SHOW,
            ]);
        } finally {
            $this->clearContext();
        }

        $headers = $this->tenantHeaders($tenant);

        $this->actingAs($devotee)
            ->postJson("/api/v1/feedback/eligible/{$noShow->getKey()}", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.eligible', false)
            ->assertJsonPath('data.redirect_flow', 'support_report');

        $this->actingAs($devotee)
            ->postJson('/api/v1/feedback/submissions', [
                'booking_id' => (string) $cancelled->getKey(),
                'overall_rating' => 1,
                'comment' => 'Never happened',
            ], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'redirect_support_report')
            ->assertJsonPath('error.redirect_flow', 'support_report');

        $this->actingAs($devotee)
            ->postJson('/api/v1/feedback/submissions', [
                'booking_id' => (string) $noShow->getKey(),
                'overall_rating' => 1,
            ], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'redirect_support_report');
    }

    public function test_server_derived_subject_rejects_client_pandit_mismatch_and_splits_records_with_pii_redaction(): void
    {
        $tenant = $this->makeTenant();
        [$devotee] = $this->actingAsMembership($tenant);
        [$familyMember] = $this->actingAsMembership($tenant);
        [$stranger] = $this->actingAsMembership($tenant);
        [$panditUser] = $this->actingAsMembership($tenant);

        $this->resolveContext($tenant);
        try {
            $realPandit = PanditProfile::factory()->create([
                'user_id' => $panditUser->id,
                'status' => PanditProfile::STATUS_ACTIVE,
            ]);
            $otherPandit = PanditProfile::factory()->create([
                'status' => PanditProfile::STATUS_ACTIVE,
            ]);
            $booking = Booking::factory()->create([
                'user_id' => $devotee->id,
                'status' => BookingLifecycle::STATUS_COMPLETED,
            ]);
            PanditAssignment::factory()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'pandit_id' => (string) $realPandit->getKey(),
                'booking_id' => (string) $booking->getKey(),
                'status' => PanditAssignment::STATUS_COMPLETED,
                'assigned_at' => now()->subDay(),
                'confirmed_at' => now()->subDay(),
            ]);
            CustomerFamilyMember::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'user_id' => $devotee->id,
                'full_name' => 'Asha Devi',
                'relation' => 'Spouse',
                'data_subject_kind' => CustomerFamilyMember::KIND_SELF,
                'consent_ref' => 'delegated_user:' . $familyMember->id,
            ]);

            // Invitation auto-issued by usecase
            app(IssueFeedbackInvitationUseCase::class)->issueForBooking((string) $booking->getKey());
        } finally {
            $this->clearContext();
        }

        $headers = $this->tenantHeaders($tenant);

        // Stranger is excluded with 404
        $this->actingAs($stranger)
            ->postJson('/api/v1/feedback/submissions', [
                'booking_id' => (string) $booking->getKey(),
                'overall_rating' => 5,
            ], $headers)
            ->assertNotFound();

        // Client attempting to forge another pandit_id is rejected (TS-08)
        $this->actingAs($familyMember)
            ->postJson('/api/v1/feedback/submissions', [
                'booking_id' => (string) $booking->getKey(),
                'overall_rating' => 5,
                'pandit_id' => (string) $otherPandit->getKey(),
            ], $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'subject_mismatch');

        // Authorized family member submits without client override; comment contains PII
        $rawText = 'Pandit ji arrived calmly. Call me at 9876543210 or devotee@example.com for follow up.';
        $res = $this->actingAs($familyMember)
            ->postJson('/api/v1/feedback/submissions', [
                'booking_id' => (string) $booking->getKey(),
                'overall_rating' => 5,
                'comment' => $rawText,
                'share_publicly' => true,
                'dimensions' => [
                    'punctuality_perceived' => 5,
                    'conduct' => 5,
                    'preparation_guidance' => 4,
                    'chanting_audibility_clarity' => 5,
                ],
            ], $headers)
            ->assertCreated();

        $submissionId = (string) $res->json('data.id');
        $this->assertStringStartsWith('NV-REV-', (string) $res->json('data.review_reference'));
        $this->assertSame((string) $realPandit->getKey(), $res->json('data.subject_id'));
        $this->assertSame(FeedbackPublication::STATE_PUBLISHED, $res->json('data.publication_state'));

        $this->resolveContext($tenant);
        try {
            $sub = FeedbackSubmission::query()->findOrFail($submissionId);
            $pub = FeedbackPublication::query()->where('submission_id', $submissionId)->firstOrFail();

            // Immutable original_text untouched, redacted_text masks phone/email
            $this->assertSame($rawText, $sub->original_text);
            $this->assertNotNull($pub->redacted_text);
            $this->assertStringNotContainsString('9876543210', (string) $pub->redacted_text);
            $this->assertStringNotContainsString('devotee@example.com', (string) $pub->redacted_text);
        } finally {
            $this->clearContext();
        }
    }

    public function test_moderation_blocks_religious_disagreement_and_enforces_independent_appeal(): void
    {
        $tenant = $this->makeTenant();
        [$devotee] = $this->actingAsMembership($tenant);
        [$moderator] = $this->actingAsMembership($tenant, ['feedback:publication:moderate', 'feedback:appeal:decide']);
        [$independentReviewer] = $this->actingAsMembership($tenant, ['feedback:appeal:decide']);

        $this->resolveContext($tenant);
        try {
            $pandit = PanditProfile::factory()->create(['status' => PanditProfile::STATUS_ACTIVE]);
            $booking = Booking::factory()->create([
                'user_id' => $devotee->id,
                'status' => BookingLifecycle::STATUS_COMPLETED,
            ]);
            PanditAssignment::factory()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'pandit_id' => (string) $pandit->getKey(),
                'booking_id' => (string) $booking->getKey(),
                'status' => PanditAssignment::STATUS_COMPLETED,
                'assigned_at' => now()->subDay(),
                'confirmed_at' => now()->subDay(),
            ]);
        } finally {
            $this->clearContext();
        }

        $headers = $this->tenantHeaders($tenant);
        $created = $this->actingAs($devotee)
            ->postJson('/api/v1/feedback/submissions', [
                'booking_id' => (string) $booking->getKey(),
                'overall_rating' => 3,
                'comment' => 'Chanted slightly faster than our family custom.',
                'share_publicly' => true,
            ], $headers)
            ->assertCreated();

        $subId = (string) $created->json('data.id');

        // Religious disagreement must fail closed (422)
        $this->actingAs($moderator)
            ->postJson("/api/v1/feedback/admin/submissions/{$subId}/moderate", [
                'action' => 'WITHHOLD',
                'reason_code' => 'RELIGIOUS_DISAGREEMENT',
            ], $headers)
            ->assertStatus(422);

        // Withhold for lawful moderation reason -> provider or devotee appeals
        $this->actingAs($moderator)
            ->postJson("/api/v1/feedback/admin/submissions/{$subId}/moderate", [
                'action' => 'WITHHOLD',
                'reason_code' => 'OFF_TOPIC',
            ], $headers)
            ->assertOk()
            ->assertJsonPath('data.state', FeedbackPublication::STATE_WITHHELD);

        $appeal = $this->actingAs($devotee)
            ->postJson("/api/v1/feedback/submissions/{$subId}/appeal", [
                'role' => 'devotee',
                'grounds' => 'This was directly describing ceremony pacing and is factual.',
            ], $headers)
            ->assertCreated();

        $appealId = (string) $appeal->json('data.id');

        // Original moderator cannot decide their own appeal (409 independence_violation)
        $this->actingAs($moderator)
            ->postJson("/api/v1/feedback/admin/appeals/{$appealId}/decide", [
                'overturn' => true,
                'notes' => 'Approving my own action should fail.',
            ], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'independence_violation');

        // Independent reviewer overturns -> restores PUBLISHED
        $this->actingAs($independentReviewer)
            ->postJson("/api/v1/feedback/admin/appeals/{$appealId}/decide", [
                'overturn' => true,
                'notes' => 'Devotee account describes factual ceremony pacing.',
            ], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', FeedbackAppeal::STATUS_OVERTURNED);

        $this->resolveContext($tenant);
        try {
            $pub = FeedbackPublication::query()->where('submission_id', $subId)->firstOrFail();
            $this->assertSame(FeedbackPublication::STATE_PUBLISHED, $pub->state);
        } finally {
            $this->clearContext();
        }
    }

    public function test_rule_based_integrity_scanner_holds_self_or_incentivized_review_without_fraud_labels(): void
    {
        $tenant = $this->makeTenant();
        [$sameUser] = $this->actingAsMembership($tenant);

        $this->resolveContext($tenant);
        try {
            $pandit = PanditProfile::factory()->create([
                'user_id' => $sameUser->id,
                'status' => PanditProfile::STATUS_ACTIVE,
            ]);
            $booking = Booking::factory()->create([
                'user_id' => $sameUser->id,
                'status' => BookingLifecycle::STATUS_COMPLETED,
            ]);
            PanditAssignment::factory()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'pandit_id' => (string) $pandit->getKey(),
                'booking_id' => (string) $booking->getKey(),
                'status' => PanditAssignment::STATUS_COMPLETED,
                'assigned_at' => now()->subDay(),
                'confirmed_at' => now()->subDay(),
            ]);
        } finally {
            $this->clearContext();
        }

        $headers = $this->tenantHeaders($tenant);
        $res = $this->actingAs($sameUser)
            ->postJson('/api/v1/feedback/submissions', [
                'booking_id' => (string) $booking->getKey(),
                'overall_rating' => 5,
                'comment' => 'Promised cashback for 5 star rating.',
            ], $headers)
            ->assertCreated();

        $this->assertSame(FeedbackPublication::STATE_HELD, $res->json('data.publication_state'));
        $this->assertGreaterThanOrEqual(2, (int) $res->json('data.held_anomalies'));

        $subId = (string) $res->json('data.id');
        $this->resolveContext($tenant);
        try {
            $actions = FeedbackModerationAction::query()->where('submission_id', $subId)->get();
            foreach ($actions as $action) {
                $this->assertNotContains('fraud', array_keys($action->toArray()));
                $this->assertStringNotContainsStringIgnoringCase('fraud', (string) $action->reason_code);
            }
        } finally {
            $this->clearContext();
        }
    }
}
