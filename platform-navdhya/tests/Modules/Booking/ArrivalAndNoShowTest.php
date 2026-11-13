<?php

declare(strict_types=1);

namespace Tests\Modules\Booking;

use App\Modules\Booking\Application\ConfirmBookingNoShowUseCase;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Domain\Exceptions\BookingStateForbiddenException;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\PanditArrival;
use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

final class ArrivalAndNoShowTest extends TenantIntegrationTestCase
{
    public function test_arrival_schema_has_no_gps_columns_and_records_partner_arrival(): void
    {
        $columns = Schema::connection('tenant_shared')->getColumnListing('pandit_arrivals');
        foreach (['latitude', 'longitude', 'lat', 'lng', 'geofence'] as $forbidden) {
            $this->assertNotContains($forbidden, $columns);
        }

        $tenant = $this->makeTenant();
        [$devotee] = $this->actingAsMembership($tenant);
        [$panditUser] = $this->actingAsMembership($tenant);

        $this->resolveContext($tenant);
        try {
            $pandit = PanditProfile::factory()->create(['user_id' => $panditUser->id, 'status' => PanditProfile::STATUS_ACTIVE]);
            $scheduled = CarbonImmutable::now('UTC')->subMinutes(5);
            $booking = Booking::factory()->create([
                'user_id' => $devotee->id,
                'status' => BookingLifecycle::STATUS_ASSIGNED,
                'scheduled_at' => $scheduled->toDateTimeString(),
            ]);
            PanditAssignment::factory()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'pandit_id' => (string) $pandit->getKey(),
                'booking_id' => (string) $booking->getKey(),
                'status' => PanditAssignment::STATUS_CONFIRMED,
                'assigned_at' => now(),
                'confirmed_at' => now(),
            ]);
        } finally {
            $this->clearContext();
        }

        $headers = $this->tenantHeaders($tenant);

        // Partner marks arrival cleanly
        $res = $this->actingAs($panditUser)
            ->postJson("/api/v1/booking/reservations/{$booking->getKey()}/arrival", [], $headers)
            ->assertCreated();

        $this->assertSame((string) $pandit->getKey(), $res->json('data.pandit_id'));
        $this->assertSame(PanditArrival::SOURCE_PARTNER_APP, $res->json('data.source_channel'));

        // Devotee corroborates arrival
        $corr = $this->actingAs($devotee)
            ->postJson("/api/v1/booking/reservations/{$booking->getKey()}/confirm-arrival", [], $headers)
            ->assertOk();

        $this->assertNotNull($corr->json('data.corroborated_at'));
        $this->assertAuditRecorded($tenant, 'booking.pandit_arrival.recorded');
    }

    public function test_missing_arrival_scan_never_auto_no_shows_and_human_confirmation_reaches_no_show(): void
    {
        $tenant = $this->makeTenant();
        [$devotee] = $this->actingAsMembership($tenant);
        [$ops] = $this->actingAsMembership($tenant, ['booking:noshow:confirm']);

        $this->resolveContext($tenant);
        try {
            $booking = Booking::factory()->create([
                'user_id' => $devotee->id,
                'status' => BookingLifecycle::STATUS_ASSIGNED,
                'scheduled_at' => CarbonImmutable::now('UTC')->subMinutes(40)->toDateTimeString(),
            ]);
        } finally {
            $this->clearContext();
        }

        // Scanner emits overdue alert only; booking stays ASSIGNED
        $this->artisan('booking:scan-missing-arrivals', ['--tenant' => (string) $tenant->getKey()])
            ->assertExitCode(0);

        $this->resolveContext($tenant);
        try {
            $fresh = Booking::query()->findOrFail($booking->getKey());
            $this->assertSame(BookingLifecycle::STATUS_ASSIGNED, $fresh->status);
        } finally {
            $this->clearContext();
        }

        // Without outreach confirmation -> 422
        $headers = $this->tenantHeaders($tenant);
        $this->actingAs($ops)
            ->postJson("/api/v1/booking/admin/reservations/{$booking->getKey()}:confirm-no-show", [
                'outreach_attempted' => false,
                'reason' => 'No answer',
            ], $headers)
            ->assertStatus(422);

        // Explicit ops confirmation transitions ASSIGNED -> NO_SHOW
        $this->actingAs($ops)
            ->postJson("/api/v1/booking/admin/reservations/{$booking->getKey()}:confirm-no-show", [
                'outreach_attempted' => true,
                'reason' => 'Called pandit twice and confirmed with family no arrival.',
            ], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', BookingLifecycle::STATUS_NO_SHOW);

        $this->assertAuditRecorded($tenant, 'booking.no_show.confirmed');

        // Confirm NO_SHOW is blocked if an arrival record actually exists
        $this->resolveContext($tenant);
        try {
            $arrivedBooking = Booking::factory()->create([
                'user_id' => $devotee->id,
                'status' => BookingLifecycle::STATUS_ASSIGNED,
                'scheduled_at' => CarbonImmutable::now('UTC')->subMinutes(40)->toDateTimeString(),
            ]);
            PanditArrival::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'booking_id' => (string) $arrivedBooking->getKey(),
                'pandit_id' => (string) Str::ulid(),
                'arrived_at' => now(),
                'recorded_by_actor_id' => 'system',
                'source_channel' => PanditArrival::SOURCE_OPS_OVERRIDE,
            ]);

            $this->expectException(BookingStateForbiddenException::class);
            app(ConfirmBookingNoShowUseCase::class)->execute(
                (string) $arrivedBooking->getKey(),
                (string) $ops->id,
                true,
                'Attempting wrong no-show'
            );
        } finally {
            $this->clearContext();
        }
    }
}

