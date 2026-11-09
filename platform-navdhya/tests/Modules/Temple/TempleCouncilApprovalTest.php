<?php

namespace Tests\Modules\Temple;

use App\Models\User;
use App\Modules\Booking\Application\ConfirmBookingFulfillmentUseCase;
use App\Modules\Booking\Application\CreateReservationDraftUseCase;
use App\Modules\Booking\Application\InitiateCheckoutHoldUseCase;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Contracts\ListsCatalogItems;
use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditProfile;
use App\Modules\Temple\Contracts\ManagesTempleApprovals;
use App\Modules\Temple\Models\Temple;
use App\Modules\Temple\Models\TempleBookingApproval;
use App\Modules\Temple\Models\TemplePriest;
use App\Modules\Temple\Models\TemplePuja;
use App\Platform\Tenancy\Models\Tenant;
use InvalidArgumentException;
use Tests\TenantIntegrationTestCase;

/**
 * Priest Council Approval & SLA Refund tests (Sub-phase 3.3).
 */
final class TempleCouncilApprovalTest extends TenantIntegrationTestCase
{
    private function seedTemple(Tenant $tenant): Temple
    {
        $this->resolveContext($tenant);
        try {
            $user = User::factory()->create();
            $this->makeMembership($tenant, $user);

            return Temple::factory()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'owner_id' => $user->getAuthIdentifier(),
                'status' => Temple::STATUS_ACTIVE,
                'verification_status' => Temple::VERIFICATION_VERIFIED,
            ]);
        } finally {
            $this->clearContext();
        }
    }

    private function mockCatalog(string $sku): void
    {
        $catalogMock = $this->createMock(ListsCatalogItems::class);
        $catalogMock->method('itemsBySkus')->willReturn([
            [
                'sku' => $sku,
                'status' => 'ACTIVE',
                'name' => 'Kashi Rudrabhishek',
                'base_price_minor' => 350000,
            ],
        ]);
        $this->app->instance(ListsCatalogItems::class, $catalogMock);
    }

    public function test_booking_confirmation_initiates_council_approval_when_required(): void
    {
        $tenant = $this->makeTenant();
        $temple = $this->seedTemple($tenant);
        $this->mockCatalog('KASHI_RUDRABHISHEK');

        $this->resolveContext($tenant);
        try {
            // Seed temple puja requiring approval
            TemplePuja::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'temple_id' => (string) $temple->getKey(),
                'puja_id' => '01JQZZZZZZZZZZZZZZZZZZZZ99',
                'capacity' => 10,
                'requires_approval' => true,
                'approval_sla_minutes' => 60,
                'status' => TemplePuja::STATUS_ACTIVE,
            ]);

            /** @var CreateReservationDraftUseCase $draftUseCase */
            $draftUseCase = app(CreateReservationDraftUseCase::class);
            /** @var InitiateCheckoutHoldUseCase $holdUseCase */
            $holdUseCase = app(InitiateCheckoutHoldUseCase::class);
            /** @var ConfirmBookingFulfillmentUseCase $confirmUseCase */
            $confirmUseCase = app(ConfirmBookingFulfillmentUseCase::class);

            $booking = $draftUseCase->execute(
                (string) $tenant->getKey(),
                '1',
                'idemp-council-01',
                [
                    'service_sku' => 'KASHI_RUDRABHISHEK',
                    'scheduled_at' => '2026-11-20T10:00:00Z',
                    'duration_minutes' => 90,
                    'selected_temple_id' => (string) $temple->getKey(),
                ]
            );

            $held = $holdUseCase->execute(
                (string) $tenant->getKey(),
                (string) $booking->getKey(),
                '1'
            );
            $this->assertSame(BookingLifecycle::STATUS_PENDING_PAYMENT, $held->status);

            // Execute confirmation
            $confirmed = $confirmUseCase->execute(
                (string) $tenant->getKey(),
                (string) $booking->getKey(),
                '1'
            );

            // Because ritual requires approval, booking stays in PAYMENT_CONFIRMED with detail PENDING_COUNCIL_APPROVAL
            $this->assertSame(BookingLifecycle::STATUS_PAYMENT_CONFIRMED, $confirmed->status);
            $this->assertSame('PENDING_COUNCIL_APPROVAL', $confirmed->status_detail);

            $approval = TempleBookingApproval::query()
                ->where('tenant_id', (string) $tenant->getKey())
                ->where('booking_id', (string) $booking->getKey())
                ->first();

            $this->assertNotNull($approval);
            $this->assertSame(TempleBookingApproval::STATE_PENDING, $approval->state);
            $this->assertSame((string) $temple->getKey(), $approval->temple_id);
        } finally {
            $this->clearContext();
        }
    }

    public function test_maker_checker_sod_prevents_officiating_priest_from_approving_own_booking(): void
    {
        $tenant = $this->makeTenant();
        $temple = $this->seedTemple($tenant);

        $officiatingUser = User::factory()->create();
        $this->makeMembership($tenant, $officiatingUser);

        $councilUser = User::factory()->create();
        $this->makeMembership($tenant, $councilUser);

        $bookingId = '01JQZZZZZZZZZZZZZZZZZZZZ71';

        $this->resolveContext($tenant);
        try {
            // Seed officiating pandit profile
            $panditProfile = PanditProfile::factory()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'user_id' => $officiatingUser->getAuthIdentifier(),
            ]);
            $panditId = (string) $panditProfile->getKey();

            // Seed officiating priest
            TemplePriest::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'temple_id' => (string) $temple->getKey(),
                'user_id' => $officiatingUser->getAuthIdentifier(),
                'pandit_id' => $panditId,
                'role' => TemplePriest::ROLE_PRIEST,
                'council_member' => true,
            ]);

            // Seed second council priest
            TemplePriest::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'temple_id' => (string) $temple->getKey(),
                'user_id' => $councilUser->getAuthIdentifier(),
                'role' => TemplePriest::ROLE_HEAD_PRIEST,
                'council_member' => true,
            ]);

            // Seed assignment linking officiating priest
            PanditAssignment::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'pandit_id' => $panditId,
                'booking_id' => $bookingId,
                'status' => PanditAssignment::STATUS_PENDING,
            ]);

            // Seed approval
            $approval = TempleBookingApproval::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'booking_id' => $bookingId,
                'temple_id' => (string) $temple->getKey(),
                'state' => TempleBookingApproval::STATE_PENDING,
                'sla_expires_at' => now()->addHour(),
                'hard_expires_at' => now()->addHours(2),
            ]);

            /** @var ManagesTempleApprovals $approvalsService */
            $approvalsService = app(ManagesTempleApprovals::class);

            // Attempt approval by the officiating priest -> Must throw SoD violation!
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Maker/Checker violation: Assigned officiating priest cannot approve their own council booking.');

            $approvalsService->recordDecision(
                (string) $tenant->getKey(),
                (string) $approval->getKey(),
                (int) $officiatingUser->getAuthIdentifier(),
                TempleBookingApproval::STATE_APPROVED
            );
        } finally {
            $this->clearContext();
        }
    }

    public function test_sla_hard_deadline_triggers_auto_decline_and_100_percent_refund(): void
    {
        $tenant = $this->makeTenant();
        $temple = $this->seedTemple($tenant);

        $this->resolveContext($tenant);
        try {
            $user = User::factory()->create();
            $this->makeMembership($tenant, $user);

            $booking = Booking::factory()->create([
                'status' => BookingLifecycle::STATUS_PAYMENT_CONFIRMED,
                'status_detail' => 'PENDING_COUNCIL_APPROVAL',
                'total_minor' => 350000,
            ]);

            $approval = TempleBookingApproval::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'booking_id' => (string) $booking->getKey(),
                'temple_id' => (string) $temple->getKey(),
                'state' => TempleBookingApproval::STATE_PENDING,
                'sla_expires_at' => now()->addMinutes(30),
                'hard_expires_at' => now()->addHour(),
            ]);

            // Travel forward past the hard deadline
            $this->travel(70)->minutes();

            $this->artisan('temple:process-approval-sla', ['--tenant' => (string) $tenant->getKey()])
                ->assertExitCode(0);

            $approval->refresh();
            $this->assertSame(TempleBookingApproval::STATE_DECLINED, $approval->state);
            $this->assertSame('approval_timeout', $approval->decision_reason);

            $booking->refresh();
            $this->assertSame(BookingLifecycle::STATUS_REFUNDED, $booking->status);
            $this->assertSame(350000, (int) $booking->refund_amount_minor);
            $this->assertSame('APPROVAL_TIMEOUT_REFUND', $booking->status_detail);
        } finally {
            $this->clearContext();
        }
    }
}
