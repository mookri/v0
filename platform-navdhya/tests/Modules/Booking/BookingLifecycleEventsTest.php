<?php

namespace Tests\Modules\Booking;

use App\Models\User;
use App\Modules\Booking\Contracts\TransitionsBookingFulfillment;
use App\Modules\Booking\Contracts\TransitionsBookingPayment;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Customers\Models\BuyerProfile;
use App\Modules\Customers\Models\CustomerNotification;
use App\Modules\Customers\NotificationChannels;
use App\Platform\Outbox\Outbox;
use App\Platform\Outbox\OutboxEvent;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Booking lifecycle listeners (N2): the reservation verbs and both
 * outcome ports append booking.reservation.* facts to the transactional
 * outbox in the same tenant transaction, and `outbox:drain` replays them
 * into customer notifications through the NotifiesCustomers port with
 * the outbox event id as dedup identity — a second drain collapses into
 * the same inbox row instead of doubling it.
 *
 * Assertions read withoutGlobalScopes: the drain resolves each tenant's
 * context itself and clears it when done, tests make no ambient
 * assumption (TEN-009).
 */
final class BookingLifecycleEventsTest extends TenantIntegrationTestCase
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
            CatalogItem::factory()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'sku' => 'N2-PUJA',
                'status' => 'ACTIVE',
                'currency' => 'INR',
                'price_minor' => 450000,
            ]);
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
            return Booking::factory()->create([
                'user_id' => $user->id,
                'status' => $status,
                'scheduled_at' => now()->addDays(3)->toDateTimeString(),
            ]);
        } finally {
            $this->clearContext();
        }
    }

    /**
     * @return OutboxEvent|null the single pending event of $eventType (null = absent)
     */
    private function onlyEvent(string $eventType): ?OutboxEvent
    {
        $rows = OutboxEvent::query()->withoutGlobalScopes()
            ->where('event_type', $eventType)
            ->get();

        $this->assertCount(1, $rows, "expected exactly one '{$eventType}' outbox event");

        return $rows->first();
    }

    private function drain(): void
    {
        $this->assertSame(0, Artisan::call('outbox:drain'));
    }

    private function notificationsFor(User $user)
    {
        return CustomerNotification::query()
            ->withoutGlobalScopes()
            ->where('user_id', $user->getAuthIdentifier())
            ->get();
    }

    public function test_store_writes_the_created_fact_same_tx_and_the_drain_builds_the_inbox(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant) + ['Idempotency-Key' => 'n2-store-001'];

        $response = $this->actingAs($user)->postJson('/api/v1/booking/reservations', [
            'service_sku' => 'N2-PUJA',
            'scheduled_at' => '2030-06-01 10:00:00',
        ], $headers)->assertStatus(201);

        $bookingId = (string) $response->json('data.id');

        // The fact exists before any notification does — the controller
        // no longer notifies directly (N2 outbox-driven delivery).
        $event = $this->onlyEvent('booking.reservation.created');
        $this->assertNotNull($event);
        $this->assertSame('booking', $event->aggregate_type);
        $this->assertSame($bookingId, $event->aggregate_id);
        $this->assertSame($bookingId, $event->payload['booking_id']);
        $this->assertSame((string) $user->getAuthIdentifier(), (string) $event->payload['user_id']);
        $this->assertSame('2030-06-01 10:00:00', $event->payload['scheduled_at']);
        $this->assertSame(0, $this->notificationsFor($user)->count(), 'delivery happens on drain, never inline');

        $this->drain();

        $notification = $this->notificationsFor($user)->sole();
        $this->assertSame('booking', $notification->kind);
        $this->assertSame('Booking created', $notification->title);
        $this->assertSame(
            'Your booking '.$bookingId.' was created for 2030-06-01 10:00:00.',
            (string) $notification->body,
        );
        $this->assertSame(
            NotificationChannels::dedupKey('booking', (string) $event->id, (string) $user->getAuthIdentifier(), 'in_app'),
            $notification->dedup_key,
        );

        // Exactly-once: a second drain re-dispatches nothing and the
        // dedup identity would collapse a replay anyway.
        $this->drain();
        $this->assertSame(1, $this->notificationsFor($user)->count());
    }

    public function test_cancel_and_reschedule_emit_their_facts_and_notifies(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);

        $toCancel = $this->seedBooking($tenant, $user, BookingLifecycle::STATUS_CONFIRMED);
        $this->actingAs($user)
            ->postJson('/api/v1/booking/reservations/'.(string) $toCancel->getKey().':cancel', [], $headers)
            ->assertOk();

        $cancelled = $this->onlyEvent('booking.reservation.cancelled');
        $this->assertSame('CONFIRMED', $cancelled->payload['from']);
        $this->assertSame('CANCELLED', $cancelled->payload['to']);

        $toMove = $this->seedBooking($tenant, $user, BookingLifecycle::STATUS_CONFIRMED);
        $oldStart = $toMove->scheduled_at->toDateTimeString();
        $this->actingAs($user)->postJson(
            '/api/v1/booking/reservations/'.(string) $toMove->getKey().':reschedule',
            ['new_start_time' => '2030-07-01 10:00:00', 'version' => $toMove->version],
            $headers,
        )->assertOk();

        // Drain sees both facts (the created fixture events of the seeds
        // do not exist — the factories write no outbox rows).
        $this->assertSame(0, Artisan::call('outbox:drain'));

        $rescheduled = OutboxEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'booking.reservation.rescheduled')
            ->sole();
        $this->assertSame($oldStart, $rescheduled->payload['old_start']);
        $this->assertSame('2030-07-01 10:00:00', $rescheduled->payload['new_start']);

        $titles = $this->notificationsFor($user)->pluck('title')->all();
        $this->assertContains('Booking cancelled', $titles);
        $this->assertContains('Booking rescheduled', $titles);

        $body = $this->notificationsFor($user)
            ->first(fn (CustomerNotification $n): bool => $n->title === 'Booking rescheduled');
        $this->assertSame(
            'Your booking '.(string) $toMove->getKey().' moved to 2030-07-01 10:00:00.',
            (string) ($body?->body ?? ''),
        );
    }

    public function test_fulfillment_port_emits_transitioned_for_assignment_hops(): void
    {
        [$tenant, $user] = $this->customer();
        $booking = $this->seedBooking($tenant, $user, BookingLifecycle::STATUS_CONFIRMED);
        $bookingId = (string) $booking->getKey();
        $panditUserId = (string) $user->getAuthIdentifier(); // actor, any id in v1

        $this->resolveContext($tenant);
        try {
            $accepted = app(TransitionsBookingFulfillment::class)->assignmentAccepted($bookingId, $panditUserId);
        } finally {
            $this->clearContext();
        }
        $this->assertTrue($accepted['ok'] ?? false);

        $event = $this->onlyEvent('booking.reservation.transitioned');
        $this->assertSame('CONFIRMED', $event->payload['from']);
        $this->assertSame('ASSIGNED', $event->payload['to']);
        $this->assertSame('assignment_accepted', $event->payload['reason']);
        $this->assertSame($panditUserId, $event->payload['actor_id']);

        $this->drain();
        $this->assertSame(
            'Pandit assigned',
            $this->notificationsFor($user)->sole()->title,
        );

        $this->resolveContext($tenant);
        try {
            $completed = app(TransitionsBookingFulfillment::class)->assignmentCompleted($bookingId, $panditUserId);
        } finally {
            $this->clearContext();
        }
        $this->assertTrue($completed['ok'] ?? false);

        $this->drain();
        $titles = $this->notificationsFor($user)->pluck('title')->all();
        $this->assertContains('Booking completed', $titles);
    }

    public function test_payment_port_emits_transitioned_for_money_outcomes(): void
    {
        [$tenant, $user] = $this->customer();

        $confirmed = $this->seedBooking($tenant, $user, BookingLifecycle::STATUS_PENDING_PAYMENT);
        $failed = $this->seedBooking($tenant, $user, BookingLifecycle::STATUS_PENDING_PAYMENT);
        $refunded = $this->seedBooking($tenant, $user, BookingLifecycle::STATUS_CONFIRMED);

        $this->resolveContext($tenant);
        try {
            $port = app(TransitionsBookingPayment::class);
            $this->assertTrue($port->paymentConfirmed((string) $confirmed->getKey())['ok'] ?? false);
            $this->assertTrue($port->paymentFailed((string) $failed->getKey())['ok'] ?? false);
            $this->assertTrue($port->paymentRefunded((string) $refunded->getKey())['ok'] ?? false);
        } finally {
            $this->clearContext();
        }

        $facts = OutboxEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'booking.reservation.transitioned')
            ->get()
            ->keyBy(fn (OutboxEvent $event): string => (string) $event->aggregate_id);

        $this->assertSame('CONFIRMED', $facts[(string) $confirmed->getKey()]->payload['to']);
        $this->assertSame('payment_captured', $facts[(string) $confirmed->getKey()]->payload['reason']);
        $this->assertSame('FAILED', $facts[(string) $failed->getKey()]->payload['to']);
        $this->assertSame('payment_failed', $facts[(string) $failed->getKey()]->payload['reason']);
        $this->assertSame('REFUNDED', $facts[(string) $refunded->getKey()]->payload['to']);
        $this->assertSame('refund_processed', $facts[(string) $refunded->getKey()]->payload['reason']);
        $this->assertSame('system:payment-webhook', $facts[(string) $confirmed->getKey()]->payload['actor_id']);

        $this->drain();

        $titles = $this->notificationsFor($user)->pluck('title')->all();
        $this->assertEqualsCanonicalizing(
            ['Payment received', 'Payment failed', 'Payment refunded'],
            $titles,
        );
    }

    public function test_transition_without_customer_copy_is_dispatched_but_never_notifies(): void
    {
        [$tenant, $user] = $this->customer();
        $booking = $this->seedBooking($tenant, $user, BookingLifecycle::STATUS_IN_PROGRESS);

        $this->resolveContext($tenant);
        try {
            (new Outbox())->append(
                (string) $tenant->getKey(),
                'booking',
                (string) $booking->getKey(),
                'booking.reservation.transitioned',
                [
                    'booking_id' => (string) $booking->getKey(),
                    'user_id' => (string) $user->getAuthIdentifier(),
                    'from' => 'ASSIGNED',
                    'to' => 'IN_PROGRESS',
                    'reason' => 'assignment_started',
                    'actor_id' => 'system:test',
                ],
            );
        } finally {
            $this->clearContext();
        }

        $this->drain();

        $event = $this->onlyEvent('booking.reservation.transitioned');
        $this->assertNotNull($event->dispatched_at, 'the fact still dispatches — it is a fact without customer copy');
        $this->assertSame(0, $this->notificationsFor($user)->count());
    }

    public function test_malformed_lifecycle_payload_fails_the_claim_loudly_for_ops(): void
    {
        [$tenant, $user] = $this->customer();

        $this->resolveContext($tenant);
        try {
            (new Outbox())->append(
                (string) $tenant->getKey(),
                'booking',
                'bkg_malformed',
                'booking.reservation.created',
                ['booking_id' => 'bkg_malformed'], // no user_id — producer bug
            );
        } finally {
            $this->clearContext();
        }

        $this->drain();

        $event = $this->onlyEvent('booking.reservation.created');
        $this->assertNull($event->dispatched_at, 'the claim rolls back — the row stays pending for ops');
        $this->assertSame(1, $event->attempts);
        $this->assertStringContainsString('requires booking_id and user_id', (string) $event->last_error);
        $this->assertSame(0, $this->notificationsFor($user)->count());
    }
}
