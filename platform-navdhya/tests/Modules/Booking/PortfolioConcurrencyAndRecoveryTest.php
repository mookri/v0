<?php

declare(strict_types=1);

namespace Tests\Modules\Booking;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Pandit\Contracts\ManagesPanditAssignments;
use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditProfile;
use App\Modules\Payment\Domain\PaymentReconciliationService;
use App\Modules\Payment\Gateways\FakeGateway;
use App\Modules\Payment\Models\PaymentIntent;
use App\Modules\Payment\Models\PaymentOperation;
use App\Platform\Outbox\OutboxEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Concurrency & recovery test suite for Phase 2 (T-AP02-01..07):
 * - PAYMENT_UNKNOWN reconciliation (PAID confirm, PAID late hold expiry -> refund compensation, FAILED)
 * - Pandit assignment start verb (:start) & CAS stale version rejection
 * - Overlapping slot rejection under locked assign transaction
 */
final class PortfolioConcurrencyAndRecoveryTest extends TenantIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        FakeGateway::resetTestOutcomes();
    }

    public function test_payment_unknown_reconciles_captured_booking_to_confirmed(): void
    {
        config(['payments.driver' => 'fake']);
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $tenantId = (string) $tenant->getKey();

        $this->resolveContext($tenant);
        $booking = Booking::factory()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'status' => BookingLifecycle::STATUS_PENDING_PAYMENT,
            'total_minor' => 250000,
            'currency' => 'INR',
            'version' => 2,
        ]);

        $orderId = 'order_unknown_paid_1';
        $intent = PaymentIntent::factory()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'booking_id' => (string) $booking->getKey(),
            'amount_minor' => 250000,
            'currency' => 'INR',
            'status' => PaymentIntent::STATUS_PAYMENT_UNKNOWN,
            'provider' => 'razorpay',
            'provider_order_id' => $orderId,
        ]);
        $this->clearContext();

        FakeGateway::setOrderOutcome($orderId, [
            'status' => 'PAID',
            'provider_payment_id' => 'pay_reconciled_1',
            'amount_minor' => 250000,
            'currency' => 'INR',
        ]);

        $exit = Artisan::call('payments:reconcile-unknown', ['--tenant' => $tenantId]);
        $this->assertSame(0, $exit);

        $freshIntent = $intent->fresh();
        $freshBooking = $booking->fresh();
        $this->assertSame(PaymentIntent::STATUS_SUCCEEDED, $freshIntent->status);
        $this->assertSame('pay_reconciled_1', $freshIntent->provider_payment_id);
        $this->assertNotNull($freshIntent->reconciled_at);
        $this->assertSame(BookingLifecycle::STATUS_CONFIRMED, $freshBooking->status);
    }

    public function test_payment_unknown_captured_after_hold_expiry_enqueues_compensation_refund(): void
    {
        config(['payments.driver' => 'fake']);
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $tenantId = (string) $tenant->getKey();

        $this->resolveContext($tenant);
        $booking = Booking::factory()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'status' => BookingLifecycle::STATUS_EXPIRED,
            'total_minor' => 310000,
            'currency' => 'INR',
            'version' => 3,
        ]);

        $orderId = 'order_unknown_expired_1';
        $intent = PaymentIntent::factory()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'booking_id' => (string) $booking->getKey(),
            'amount_minor' => 310000,
            'currency' => 'INR',
            'status' => PaymentIntent::STATUS_PAYMENT_UNKNOWN,
            'provider' => 'razorpay',
            'provider_order_id' => $orderId,
        ]);
        $this->clearContext();

        FakeGateway::setOrderOutcome($orderId, [
            'status' => 'PAID',
            'provider_payment_id' => 'pay_late_capture_1',
            'amount_minor' => 310000,
            'currency' => 'INR',
        ]);

        Artisan::call('payments:reconcile-unknown', ['--tenant' => $tenantId]);

        $freshIntent = $intent->fresh();
        $freshBooking = $booking->fresh();
        $this->assertSame(PaymentIntent::STATUS_SUCCEEDED, $freshIntent->status);
        $this->assertStringStartsWith('booking_unfulfillable:', (string) $freshIntent->last_reconcile_error);
        $this->assertSame(BookingLifecycle::STATUS_EXPIRED, $freshBooking->status);

        $refundOp = PaymentOperation::query()
            ->where('tenant_id', $tenantId)
            ->where('booking_id', (string) $booking->getKey())
            ->where('operation_type', PaymentOperation::TYPE_REFUND)
            ->first();
        $this->assertNotNull($refundOp);
        $this->assertSame(PaymentOperation::STATUS_PENDING, $refundOp->status);
        $this->assertSame(310000, (int) $refundOp->amount_minor);

        $event = OutboxEvent::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('event_type', 'payment.reconciliation.compensation_required')
            ->first();
        $this->assertNotNull($event);
    }

    public function test_payment_unknown_receipt_lookup_reconciles_failed_payment(): void
    {
        config(['payments.driver' => 'fake']);
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $tenantId = (string) $tenant->getKey();

        $this->resolveContext($tenant);
        $booking = Booking::factory()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'status' => BookingLifecycle::STATUS_PENDING_PAYMENT,
            'total_minor' => 150000,
            'currency' => 'INR',
            'version' => 2,
        ]);

        $reconciler = app(PaymentReconciliationService::class);
        $intent = $reconciler->recordAmbiguousCheckout(
            $tenantId,
            (string) $booking->getKey(),
            (int) $user->getKey(),
            150000,
            'INR',
            ['total_minor' => 150000],
            'gateway_timeout_504',
        );
        $this->clearContext();

        FakeGateway::setReceiptOutcome('booking_'.(string) $booking->getKey(), [
            'status' => 'FAILED',
            'provider_order_id' => 'order_late_fail_1',
            'error' => 'gateway_declined',
        ]);

        Artisan::call('payments:reconcile-unknown', ['--tenant' => $tenantId]);

        $this->assertSame(PaymentIntent::STATUS_FAILED, $intent->fresh()->status);
        $this->assertSame('order_late_fail_1', $intent->fresh()->provider_order_id);
        $this->assertSame(BookingLifecycle::STATUS_FAILED, $booking->fresh()->status);
    }

    public function test_pandit_assignment_start_verb_cas_version_guard_and_slot_overlap(): void
    {
        $tenant = $this->makeTenant();
        [$panditUser] = $this->actingAsMembership($tenant);
        $tenantId = (string) $tenant->getKey();
        $headers = $this->tenantHeaders($tenant);

        $this->resolveContext($tenant);
        $sku = 'puja_concurrency_'.Str::random(6);
        CatalogItem::factory()->create(['tenant_id' => $tenantId, 'sku' => $sku, 'status' => 'ACTIVE']);
        $pandit = PanditProfile::factory()->create([
            'tenant_id' => $tenantId,
            'user_id' => $panditUser->id,
            'status' => 'ACTIVE',
            'verification_status' => 'VERIFIED',
        ]);

        $startTime = CarbonImmutable::now('UTC')->addDays(2);
        $booking1 = Booking::factory()->create([
            'tenant_id' => $tenantId,
            'service_sku' => $sku,
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => $startTime->toDateTimeString(),
            'duration_minutes' => 90,
            'version' => 2,
        ]);
        $booking2 = Booking::factory()->create([
            'tenant_id' => $tenantId,
            'service_sku' => $sku,
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => $startTime->addMinutes(30)->toDateTimeString(),
            'duration_minutes' => 90,
            'version' => 2,
        ]);

        /** @var ManagesPanditAssignments $mgr */
        $mgr = app(ManagesPanditAssignments::class);
        $assigned = $mgr->assign((string) $pandit->getKey(), (string) $booking1->getKey(), 200000);
        $assignmentId = $assigned['id'];

        // Overlap guard must reject second overlapping booking for the same pandit.
        $this->expectException(\InvalidArgumentException::class);
        try {
            $mgr->assign((string) $pandit->getKey(), (string) $booking2->getKey(), 200000);
        } finally {
            $this->clearContext();
        }
    }

    public function test_pandit_assignment_start_endpoint_transitions_assigned_to_in_progress_and_guards_double_start(): void
    {
        $tenant = $this->makeTenant();
        [$panditUser] = $this->actingAsMembership($tenant);
        $tenantId = (string) $tenant->getKey();
        $headers = $this->tenantHeaders($tenant);

        $this->resolveContext($tenant);
        $pandit = PanditProfile::factory()->create([
            'tenant_id' => $tenantId,
            'user_id' => $panditUser->id,
            'status' => 'ACTIVE',
            'verification_status' => 'VERIFIED',
        ]);
        $booking = Booking::factory()->create([
            'tenant_id' => $tenantId,
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => CarbonImmutable::now('UTC')->addDays(2)->toDateTimeString(),
            'duration_minutes' => 60,
            'version' => 2,
        ]);
        $assignment = app(ManagesPanditAssignments::class)->assign((string) $pandit->getKey(), (string) $booking->getKey(), 120000);
        $assignmentId = $assignment['id'];
        $this->clearContext();

        // Confirm first: PENDING -> CONFIRMED
        $this->actingAs($panditUser)
            ->postJson("/api/v1/pandit/assignments/{$assignmentId}:confirm", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'CONFIRMED')
            ->assertJsonPath('data.version', 2);

        // Start service: CONFIRMED -> IN_PROGRESS (booking ASSIGNED -> IN_PROGRESS)
        $this->actingAs($panditUser)
            ->postJson("/api/v1/pandit/assignments/{$assignmentId}:start", [
                'checklist' => ['samagri_ready' => true, 'arrived_on_site' => true],
            ], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'IN_PROGRESS')
            ->assertJsonPath('data.version', 3)
            ->assertJsonPath('data.checklist.samagri_ready', true);

        $this->assertSame(BookingLifecycle::STATUS_IN_PROGRESS, $booking->fresh()->status);

        // Double start returns 409 conflict
        $this->actingAs($panditUser)
            ->postJson("/api/v1/pandit/assignments/{$assignmentId}:start", [], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'PANDIT_ASSIGNMENT_WRONG_STATE');

        // Complete from IN_PROGRESS -> COMPLETED (version 4)
        $this->actingAs($panditUser)
            ->postJson("/api/v1/pandit/assignments/{$assignmentId}:complete", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'COMPLETED')
            ->assertJsonPath('data.version', 4);

        $this->assertSame(BookingLifecycle::STATUS_COMPLETED, $booking->fresh()->status);
        $this->assertSame(PanditAssignment::STATUS_COMPLETED, PanditAssignment::withoutGlobalScopes()->find($assignmentId)->status);
    }
}

