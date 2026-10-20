<?php

namespace App\Modules\Booking\Listeners;

use App\Modules\Customers\Contracts\NotifiesCustomers;
use App\Platform\Outbox\OutboxEvent;

/**
 * Booking lifecycle listener (N2): consumes the Booking module's outbox
 * events and writes customer notifications through the NotifiesCustomers
 * port — `outbox:drain` drives it with the tenant context already
 * resolved, and the handler's inbox writes ride the drain's claim
 * transaction (exactly once per successful claim).
 *
 * Every notify() carries the outbox event id as its dedup identity, so
 * an at-least-once replay of the handler collapses into the same inbox
 * row instead of doubling it (N1 dedup). Preferences, quiet hours and
 * channel routing stay the dispatcher's policy — this class only maps
 * facts to customer copy.
 *
 * Event payload contract (schema v1), produced same-transaction by
 * ReservationController (created/cancelled/rescheduled) and the two
 * outcome ports (transitioned):
 *   booking.reservation.created      {booking_id, user_id, service_sku, scheduled_at}
 *   booking.reservation.cancelled    {booking_id, user_id, from, to}
 *   booking.reservation.rescheduled  {booking_id, user_id, old_start, new_start}
 *   booking.reservation.transitioned {booking_id, user_id, from, to, reason, actor_id}
 *
 * transitioned maps only the customer-meaningful target statuses; any
 * other hop is a fact without an inbox row (silently skipped — never a
 * wrong message).
 */
final class BookingLifecycleNotifier
{
    /** @var list<string> The lifecycle event types this listener claims. */
    public const EVENT_TYPES = [
        'booking.reservation.created',
        'booking.reservation.cancelled',
        'booking.reservation.rescheduled',
        'booking.reservation.transitioned',
    ];

    /**
     * Customer copy per transition target (payload status values).
     * `{id}` expands to the booking id.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const TRANSITION_MESSAGES = [
        'CONFIRMED' => ['Payment received', 'Payment for booking {id} was received — your booking is confirmed.'],
        'ASSIGNED' => ['Pandit assigned', 'A pandit has been assigned to booking {id}.'],
        'COMPLETED' => ['Booking completed', 'Booking {id} has been completed.'],
        'FAILED' => ['Payment failed', 'Payment for booking {id} failed. Please retry with another payment method.'],
        'REFUNDED' => ['Payment refunded', 'Payment for booking {id} was refunded.'],
    ];

    public function __construct(
        private readonly NotifiesCustomers $notifier,
    ) {
    }

    public function handle(OutboxEvent $event): void
    {
        $payload = $event->payload;
        $bookingId = (string) ($payload['booking_id'] ?? '');
        $userId = isset($payload['user_id']) && $payload['user_id'] !== '' ? (string) $payload['user_id'] : '';

        if ($bookingId === '' || $userId === '') {
            // Producer contract violation: fail loud so the drain records
            // the attempt (attempts grow, ops reads last_error) instead
            // of silently dropping a customer-facing fact.
            throw new \InvalidArgumentException(
                "booking lifecycle payload for '{$event->event_type}' requires booking_id and user_id"
            );
        }

        match ($event->event_type) {
            'booking.reservation.created' => $this->notify(
                $event,
                'Booking created',
                'Your booking '.$bookingId.' was created for '.(string) ($payload['scheduled_at'] ?? '').'.',
            ),
            'booking.reservation.cancelled' => $this->notify(
                $event,
                'Booking cancelled',
                'Your booking '.$bookingId.' was cancelled.',
            ),
            'booking.reservation.rescheduled' => $this->notify(
                $event,
                'Booking rescheduled',
                'Your booking '.$bookingId.' moved to '.(string) ($payload['new_start'] ?? '').'.',
            ),
            'booking.reservation.transitioned' => $this->transitioned($event, $payload, $bookingId),
            default => null, // the registry only dispatches EVENT_TYPES
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function transitioned(OutboxEvent $event, array $payload, string $bookingId): void
    {
        $message = self::TRANSITION_MESSAGES[(string) ($payload['to'] ?? '')] ?? null;

        if ($message === null) {
            return; // fact without customer copy — dispatched, never notified
        }

        $this->notify($event, $message[0], str_replace('{id}', $bookingId, $message[1]));
    }

    private function notify(OutboxEvent $event, string $title, string $body): void
    {
        $this->notifier->notify(
            (string) $event->tenant_id,
            (string) $event->payload['user_id'],
            'booking',
            $title,
            $body,
            (string) $event->id, // dedup identity: handler replays collapse
        );
    }
}
