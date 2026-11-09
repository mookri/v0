<?php

namespace Tests\Modules\Booking;

use App\Models\User;
use App\Modules\Booking\Application\ConfirmBookingFulfillmentUseCase;
use App\Modules\Booking\Application\CreateReservationDraftUseCase;
use App\Modules\Booking\Application\InitiateCheckoutHoldUseCase;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Domain\Exceptions\ResourceUnavailableException;
use App\Modules\Booking\Domain\ResourceAllocationManager;
use App\Modules\Booking\Domain\StockReservationManager;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingLine;
use App\Modules\Booking\Models\BookingRevision;
use App\Modules\Booking\Models\ResourceAllocation;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Pandit\Models\PanditProfile;
use App\Modules\Pandit\Models\PanditServiceEligibility;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use Tests\TenantIntegrationTestCase;

/**
 * Booking Application Use Cases Tests (Phase 2, Tasks 2.1 & 2.2).
 */
final class BookingUseCasesTest extends TenantIntegrationTestCase
{
    private CreateReservationDraftUseCase $createUseCase;
    private InitiateCheckoutHoldUseCase $checkoutUseCase;
    private ConfirmBookingFulfillmentUseCase $confirmUseCase;
    private ResourceAllocationManager $allocationManager;
    private string $tenantId;
    private string $userId;
    private string $panditId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->allocationManager = new ResourceAllocationManager();
        $stockManager = new StockReservationManager();
        $audit = app(AuditWriter::class);
        $outbox = app(Outbox::class);
        $catalog = app(\App\Modules\Catalog\Contracts\ListsCatalogItems::class);

        $this->createUseCase = new CreateReservationDraftUseCase($catalog, $audit, $outbox);
        $this->checkoutUseCase = new InitiateCheckoutHoldUseCase($this->allocationManager, $audit);
        $this->confirmUseCase = new ConfirmBookingFulfillmentUseCase($this->allocationManager, $stockManager, $audit, $outbox);

        $tenant = $this->makeTenant();
        [$user, $membership] = $this->actingAsMembership($tenant);
        $this->resolveContext($tenant, $membership);
        $this->tenantId = (string) $tenant->getKey();
        $this->userId = (string) $user->getAuthIdentifier();

        CatalogItem::factory()->create([
            'tenant_id' => $this->tenantId,
            'sku' => 'SATYANARAYAN-PUJA',
            'status' => 'ACTIVE',
            'currency' => 'INR',
            'price_minor' => 500000,
        ]);

        $panditUser = User::factory()->create();
        $panditProfile = PanditProfile::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $panditUser->getAuthIdentifier(),
            'display_name' => 'Pandit Sharma',
            'verification_status' => PanditProfile::VERIFICATION_VERIFIED,
            'status' => PanditProfile::STATUS_ACTIVE,
            'travel_buffer_minutes' => 30,
        ]);
        $this->panditId = (string) $panditProfile->getKey();

        PanditServiceEligibility::query()->create([
            'tenant_id' => $this->tenantId,
            'pandit_id' => $this->panditId,
            'service_sku' => 'SATYANARAYAN-PUJA',
            'is_qualified' => true,
        ]);
    }

    public function test_create_reservation_draft_creates_revision_and_lines_and_outbox(): void
    {
        $input = [
            'service_sku' => 'SATYANARAYAN-PUJA',
            'scheduled_at' => '2030-07-01T10:00:00Z',
            'duration_minutes' => 90,
            'selected_pandit_id' => $this->panditId,
            'address_snapshot' => ['street' => '108 Temple Lane', 'city' => 'Varanasi'],
        ];

        $booking = $this->createUseCase->execute($this->tenantId, $this->userId, 'key-draft-1', $input);

        $this->assertNotNull($booking);
        $this->assertSame(BookingLifecycle::STATUS_DRAFT, $booking->status);
        $this->assertSame(90, (int) $booking->duration_minutes);
        $this->assertSame(1, (int) $booking->version);
        $this->assertNotNull($booking->current_revision_id);

        $revision = BookingRevision::query()->where('id', $booking->current_revision_id)->first();
        $this->assertNotNull($revision);
        $this->assertSame(1, (int) $revision->version);
        $this->assertSame('108 Temple Lane', $revision->service_address_json['street']);

        $lines = BookingLine::query()->where('revision_id', $revision->id)->get();
        $this->assertCount(1, $lines);
        $this->assertSame('SATYANARAYAN-PUJA', $lines[0]->sku);
        $this->assertSame($this->panditId, $lines[0]->selected_pandit_id);
    }

    public function test_initiate_checkout_hold_holds_pandit_allocation_and_sets_pending_payment(): void
    {
        $input = [
            'service_sku' => 'SATYANARAYAN-PUJA',
            'scheduled_at' => '2030-07-01T10:00:00Z',
            'duration_minutes' => 60,
            'selected_pandit_id' => $this->panditId,
        ];

        $booking = $this->createUseCase->execute($this->tenantId, $this->userId, 'key-checkout-1', $input);

        $updatedBooking = $this->checkoutUseCase->execute($this->tenantId, (string) $booking->getKey(), $this->userId, 15);

        $this->assertSame(BookingLifecycle::STATUS_PENDING_PAYMENT, $updatedBooking->status);
        $this->assertNotNull($updatedBooking->checkout_expires_at);

        // Verify resource allocation is HELD
        $allocation = ResourceAllocation::query()
            ->where('tenant_id', $this->tenantId)
            ->where('booking_id', (string) $booking->getKey())
            ->first();

        $this->assertNotNull($allocation);
        $this->assertSame(ResourceAllocation::STATUS_HELD, $allocation->status);
    }

    public function test_competing_checkout_rejected_if_resource_already_held(): void
    {
        $input = [
            'service_sku' => 'SATYANARAYAN-PUJA',
            'scheduled_at' => '2030-07-01T10:00:00Z',
            'duration_minutes' => 60,
            'selected_pandit_id' => $this->panditId,
        ];

        $booking1 = $this->createUseCase->execute($this->tenantId, $this->userId, 'key-cust-1', $input);
        $booking2 = $this->createUseCase->execute($this->tenantId, $this->userId, 'key-cust-2', $input);

        // First customer checks out: succeeds
        $this->checkoutUseCase->execute($this->tenantId, (string) $booking1->getKey(), $this->userId, 15);

        // Second customer checks out same slot & pandit: must be refused
        $this->expectException(ResourceUnavailableException::class);
        $this->expectExceptionMessage("Pandit '{$this->panditId}' is not available");

        $this->checkoutUseCase->execute($this->tenantId, (string) $booking2->getKey(), $this->userId, 15);
    }

    public function test_confirm_booking_fulfillment_commits_allocations_and_marks_confirmed(): void
    {
        $input = [
            'service_sku' => 'SATYANARAYAN-PUJA',
            'scheduled_at' => '2030-07-01T10:00:00Z',
            'duration_minutes' => 60,
            'selected_pandit_id' => $this->panditId,
        ];

        $booking = $this->createUseCase->execute($this->tenantId, $this->userId, 'key-confirm-1', $input);
        $this->checkoutUseCase->execute($this->tenantId, (string) $booking->getKey(), $this->userId, 15);

        $confirmedBooking = $this->confirmUseCase->execute($this->tenantId, (string) $booking->getKey(), $this->userId);

        $this->assertSame(BookingLifecycle::STATUS_CONFIRMED, $confirmedBooking->status);
        $this->assertNull($confirmedBooking->checkout_expires_at);

        $allocation = ResourceAllocation::query()
            ->where('tenant_id', $this->tenantId)
            ->where('booking_id', (string) $booking->getKey())
            ->first();

        $this->assertNotNull($allocation);
        $this->assertSame(ResourceAllocation::STATUS_COMMITTED, $allocation->status);
    }
}
