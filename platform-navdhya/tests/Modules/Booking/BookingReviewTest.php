<?php

namespace Tests\Modules\Booking;

use App\Models\User;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Customers\Models\BuyerProfile;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Booking reviews (B5): author-only creation on COMPLETED bookings, one
 * per booking, foreign 404s, list + delete lifecycle.
 */
final class BookingReviewTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: Tenant, 1: User}
     */
    private function customer(): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $user->forceFill(['external_id' => 'cust_'.strtolower((string) Str::ulid())])->save();
        $this->makeMembership($tenant, $user);

        $this->resolveContext($tenant);
        try {
            BuyerProfile::factory()->create(['user_id' => $user->id]);
        } finally {
            $this->clearContext();
        }

        $this->actingAs($user);

        return [$tenant, $user];
    }

    private function seedBooking(Tenant $tenant, User $user, string $status): Booking
    {
        $this->resolveContext($tenant);
        try {
            return Booking::factory()->create(['user_id' => $user->id, 'status' => $status]);
        } finally {
            $this->clearContext();
        }
    }

    public function test_review_lifecycle(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);
        $actor = fn () => $this->actingAs($user);

        $done = $this->seedBooking($tenant, $user, BookingLifecycle::STATUS_COMPLETED);
        $did = (string) $done->getKey();

        $created = $actor()->postJson("/api/v1/booking/reservations/{$did}/reviews", [
            'rating' => 5, 'comment' => 'Wonderful ceremony.',
        ], $headers)->assertCreated();
        $rid = $created->json('data.id');

        // One review per booking.
        $actor()->postJson("/api/v1/booking/reservations/{$did}/reviews", ['rating' => 4], $headers)
            ->assertStatus(409)->assertJsonPath('error.code', 'conflict');

        $listed = $actor()->getJson('/api/v1/booking/reviews', $headers)->assertOk();
        $this->assertSame([$rid], collect($listed->json('data'))->pluck('id')->all());

        $actor()->deleteJson("/api/v1/booking/reviews/{$rid}", [], $headers)->assertOk();
        $actor()->deleteJson("/api/v1/booking/reviews/{$rid}", [], $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'not_found');

        $this->assertAuditRecorded($tenant, 'booking.review.created');
    }

    public function test_non_completed_and_foreign(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);
        $actor = fn () => $this->actingAs($user);

        $live = $this->seedBooking($tenant, $user, BookingLifecycle::STATUS_CONFIRMED);
        $actor()->postJson('/api/v1/booking/reservations/'.(string) $live->getKey().'/reviews', ['rating' => 5], $headers)
            ->assertStatus(409)->assertJsonPath('error.code', 'state_forbidden');

        // Rating bounds are validated on eligible bookings.
        $done = $this->seedBooking($tenant, $user, BookingLifecycle::STATUS_COMPLETED);
        $actor()->postJson('/api/v1/booking/reservations/'.(string) $done->getKey().'/reviews', ['rating' => 9], $headers)
            ->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');

        $other = $this->makeTenant();
        [$otherUser] = $this->actingAsMembership($other);
        $foreign = $this->seedBooking($other, $otherUser, BookingLifecycle::STATUS_COMPLETED);
        $actor()->postJson('/api/v1/booking/reservations/'.(string) $foreign->getKey().'/reviews', ['rating' => 5], $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'not_found');
    }
}
