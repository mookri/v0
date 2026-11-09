<?php

namespace Tests\Modules\Pandit;

use App\Models\User;
use App\Modules\Booking\Application\CreateReservationDraftUseCase;
use App\Modules\Booking\Application\InitiateCheckoutHoldUseCase;
use App\Modules\Booking\Domain\Exceptions\ResourceUnavailableException;
use App\Modules\Booking\Models\ResourceAllocation;
use App\Modules\Catalog\Contracts\ListsCatalogItems;
use App\Modules\Pandit\Models\PanditAvailability;
use App\Modules\Pandit\Models\PanditProfile;
use App\Modules\Pandit\Models\PanditServiceEligibility;
use App\Platform\Tenancy\Models\Tenant;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Tests\TenantIntegrationTestCase;

/**
 * Pandit Booking Guard & Travel Buffer Hold tests (Sub-phase 2.3).
 */
final class PanditBookingGuardTest extends TenantIntegrationTestCase
{
    private function seedProfile(Tenant $tenant, array $overrides = []): PanditProfile
    {
        $this->resolveContext($tenant);
        try {
            $user = User::factory()->create();
            $this->makeMembership($tenant, $user);

            return PanditProfile::factory()->create(array_merge([
                'user_id' => $user->getAuthIdentifier(),
                'verification_status' => PanditProfile::VERIFICATION_VERIFIED,
                'status' => PanditProfile::STATUS_ACTIVE,
                'travel_buffer_minutes' => 45,
                'timezone' => 'Asia/Kolkata',
            ], $overrides));
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
                'name' => 'Satyanarayan Puja',
                'base_price_minor' => 250000,
            ],
        ]);
        $this->app->instance(ListsCatalogItems::class, $catalogMock);
    }

    public function test_draft_reservation_rejected_when_pandit_is_not_qualified(): void
    {
        $tenant = $this->makeTenant();
        $profile = $this->seedProfile($tenant);
        $this->mockCatalog('SATYANARAYAN_PUJA');

        $this->resolveContext($tenant);
        try {
            /** @var CreateReservationDraftUseCase $useCase */
            $useCase = app(CreateReservationDraftUseCase::class);

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage("not qualified or inactive for service SKU 'SATYANARAYAN_PUJA'");

            $useCase->execute(
                (string) $tenant->getKey(),
                '1',
                'idemp-test-01',
                [
                    'service_sku' => 'SATYANARAYAN_PUJA',
                    'scheduled_at' => '2026-11-16 10:00:00',
                    'duration_minutes' => 60,
                    'selected_pandit_id' => (string) $profile->getKey(),
                ]
            );
        } finally {
            $this->clearContext();
        }
    }

    public function test_draft_reservation_accepted_when_pandit_is_qualified_and_available(): void
    {
        $tenant = $this->makeTenant();
        $profile = $this->seedProfile($tenant);
        $this->mockCatalog('SATYANARAYAN_PUJA');

        $this->resolveContext($tenant);
        try {
            // Seed qualification
            PanditServiceEligibility::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'pandit_id' => (string) $profile->getKey(),
                'service_sku' => 'SATYANARAYAN_PUJA',
                'is_qualified' => true,
            ]);

            // Seed availability covering the window (day 1 = Monday)
            PanditAvailability::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'pandit_id' => (string) $profile->getKey(),
                'day_of_week' => 1,
                'start_time' => '08:00:00',
                'end_time' => '18:00:00',
                'is_recurring' => true,
            ]);

            /** @var CreateReservationDraftUseCase $useCase */
            $useCase = app(CreateReservationDraftUseCase::class);

            $booking = $useCase->execute(
                (string) $tenant->getKey(),
                '1',
                'idemp-test-02',
                [
                    'service_sku' => 'SATYANARAYAN_PUJA',
                    'scheduled_at' => '2026-11-16 10:00:00',
                    'duration_minutes' => 60,
                    'selected_pandit_id' => (string) $profile->getKey(),
                ]
            );

            $this->assertNotNull($booking);
            $this->assertSame('DRAFT', $booking->status);
        } finally {
            $this->clearContext();
        }
    }

    public function test_checkout_hold_pads_interval_with_travel_buffer(): void
    {
        $tenant = $this->makeTenant();
        $profile = $this->seedProfile($tenant, ['travel_buffer_minutes' => 45]);
        $this->mockCatalog('SATYANARAYAN_PUJA');

        $this->resolveContext($tenant);
        try {
            PanditServiceEligibility::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'pandit_id' => (string) $profile->getKey(),
                'service_sku' => 'SATYANARAYAN_PUJA',
                'is_qualified' => true,
            ]);

            PanditAvailability::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'pandit_id' => (string) $profile->getKey(),
                'day_of_week' => 1,
                'start_time' => '08:00:00',
                'end_time' => '18:00:00',
                'is_recurring' => true,
            ]);

            /** @var CreateReservationDraftUseCase $draftUseCase */
            $draftUseCase = app(CreateReservationDraftUseCase::class);
            /** @var InitiateCheckoutHoldUseCase $holdUseCase */
            $holdUseCase = app(InitiateCheckoutHoldUseCase::class);

            // Booking 1: 10:00 - 11:00 UTC
            $booking1 = $draftUseCase->execute(
                (string) $tenant->getKey(),
                '1',
                'idemp-hold-01',
                [
                    'service_sku' => 'SATYANARAYAN_PUJA',
                    'scheduled_at' => '2026-11-16T10:00:00Z',
                    'duration_minutes' => 60,
                    'selected_pandit_id' => (string) $profile->getKey(),
                ]
            );

            $heldBooking1 = $holdUseCase->execute(
                (string) $tenant->getKey(),
                (string) $booking1->getKey(),
                'actor-user-1'
            );

            $this->assertSame('PENDING_PAYMENT', $heldBooking1->status);

            // Assert allocation row has interval padded by 45 mins (09:15 to 11:45)
            $allocation = ResourceAllocation::query()
                ->where('tenant_id', (string) $tenant->getKey())
                ->where('booking_id', (string) $booking1->getKey())
                ->first();

            $this->assertNotNull($allocation);
            $this->assertSame('2026-11-16 09:15:00', CarbonImmutable::parse($allocation->interval_start)->toDateTimeString());
            $this->assertSame('2026-11-16 11:45:00', CarbonImmutable::parse($allocation->interval_end)->toDateTimeString());

            // Booking 2: 11:15 - 12:15 UTC (raw slot doesn't collide with 10:00-11:00, but collides with buffer ending at 11:45!)
            $booking2 = $draftUseCase->execute(
                (string) $tenant->getKey(),
                '2',
                'idemp-hold-02',
                [
                    'service_sku' => 'SATYANARAYAN_PUJA',
                    'scheduled_at' => '2026-11-16T11:15:00Z',
                    'duration_minutes' => 60,
                    'selected_pandit_id' => (string) $profile->getKey(),
                ]
            );

            $this->expectException(ResourceUnavailableException::class);
            $holdUseCase->execute(
                (string) $tenant->getKey(),
                (string) $booking2->getKey(),
                'actor-user-2'
            );
        } finally {
            $this->clearContext();
        }
    }
}
