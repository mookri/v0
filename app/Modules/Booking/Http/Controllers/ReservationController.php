<?php

namespace App\Modules\Booking\Http\Controllers;

use App\Models\User;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingTransition;
use App\Modules\Customers\Contracts\NotifiesCustomers;
use App\Platform\Api\CursorPagination;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reservations (B5, ADR-005): caller-scoped reads plus the cancel /
 * reschedule verbs through BookingLifecycle. Cancel is idempotent on
 * CANCELLED; every mutation appends a transition row, audits, and
 * notifies via the customers Contracts port (never a cross-module model
 * import). Machine principals are denied — reservations are human-only.
 */
final class ReservationController
{
    public function __construct(
        private readonly AuditWriter $audit,
        private readonly NotifiesCustomers $notifier,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    /**
     * @return array{user: User, booking: Booking}|JsonResponse
     */
    private function ownBooking(Request $request, string $id): array|JsonResponse
    {
        if ($request->user() instanceof \App\Platform\Identity\MachinePrincipal) {
            return new JsonResponse([
                'error' => ['code' => 'membership_inactive', 'message' => 'No active membership in this tenant.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        $user = $request->user();

        if (! $user instanceof User) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        // Foreign ids resolve to nothing (no existence leak).
        $booking = Booking::query()
            ->where('id', $id)
            ->where('user_id', $user->getAuthIdentifier())
            ->first();

        if ($booking === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Booking not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return ['user' => $user, 'booking' => $booking];
    }

    public function index(Request $request): JsonResponse
    {
        if ($request->user() instanceof \App\Platform\Identity\MachinePrincipal) {
            return new JsonResponse([
                'error' => ['code' => 'membership_inactive', 'message' => 'No active membership in this tenant.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        $user = $request->user();

        if (! $user instanceof User) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $query = Booking::query()->where('user_id', $user->getAuthIdentifier());

        $section = $request->query('section');
        if (is_string($section) && in_array($section, BookingLifecycle::SECTIONS_LIST, true)) {
            $query->whereIn('status', BookingLifecycle::SECTIONS[$section]);
        }

        [$bookings, $page] = CursorPagination::paginate($query, $request);

        return new JsonResponse([
            'data' => array_map(
                static fn (Booking $booking): array => self::serialize($booking),
                $bookings
            ),
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $found = $this->ownBooking($request, $id);
        if ($found instanceof JsonResponse) {
            return $found;
        }

        return new JsonResponse([
            'data' => self::serialize($found['booking']),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $found = $this->ownBooking($request, $id);
        if ($found instanceof JsonResponse) {
            return $found;
        }
        ['user' => $user, 'booking' => $booking] = $found;

        // Idempotent replay: cancelling a CANCELLED booking returns the
        // same representation (safe retries, same as reschedule versions).
        if ($booking->status === BookingLifecycle::STATUS_CANCELLED) {
            return new JsonResponse([
                'data' => self::serialize($booking),
                'meta' => ['request_id' => $this->requestId($request)],
            ]);
        }

        if (! BookingLifecycle::canCancel($booking->status)) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Cannot cancel in this state.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $from = $booking->status;
        $booking->forceFill([
            'status' => BookingLifecycle::STATUS_CANCELLED,
            'version' => $booking->version + 1,
        ])->save();

        $this->recordTransition($booking, $from, BookingLifecycle::STATUS_CANCELLED, 'cancelled', (string) $user->getAuthIdentifier());

        $this->audit->record(
            'booking.reservation.cancelled',
            ['module' => 'booking', 'resource_type' => 'booking', 'resource_id' => (string) $booking->getKey()],
            null,
            ['user_id' => (string) $user->getAuthIdentifier(), 'from' => $from]
        );

        $this->notifier->notify(
            (string) app(TenantContext::class)->tenantId(),
            (string) $user->getAuthIdentifier(),
            'booking',
            'Booking cancelled',
            'Your booking '.(string) $booking->getKey().' was cancelled.'
        );

        return new JsonResponse([
            'data' => self::serialize($booking->fresh()),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function reschedule(Request $request, string $id): JsonResponse
    {
        $found = $this->ownBooking($request, $id);
        if ($found instanceof JsonResponse) {
            return $found;
        }
        ['user' => $user, 'booking' => $booking] = $found;

        $validated = $request->validate([
            'new_start_time' => ['required', 'date', 'after:now'],
            'version' => ['required', 'integer', 'min:1'],
        ]);

        if (! BookingLifecycle::canReschedule($booking->status)) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Cannot reschedule in this state.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        if ((int) $validated['version'] !== (int) $booking->version) {
            return new JsonResponse([
                'error' => ['code' => 'version_conflict', 'message' => 'Booking changed since you read it.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $newStart = CarbonImmutable::parse($validated['new_start_time']);
        $newEnd = $newStart->addMinutes($booking->duration_minutes);

        if ($this->overlaps($booking, $newStart, $newEnd)) {
            return new JsonResponse([
                'error' => ['code' => 'conflict', 'message' => 'The new slot overlaps another booking.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $booking->forceFill([
            'scheduled_at' => $newStart->toDateTimeString(),
            'version' => $booking->version + 1,
        ])->save();

        $this->recordTransition($booking, $booking->status, $booking->status, 'rescheduled', (string) $user->getAuthIdentifier());

        $this->audit->record(
            'booking.reservation.rescheduled',
            ['module' => 'booking', 'resource_type' => 'booking', 'resource_id' => (string) $booking->getKey()],
            null,
            ['user_id' => (string) $user->getAuthIdentifier(), 'version' => $booking->version]
        );

        $this->notifier->notify(
            (string) app(TenantContext::class)->tenantId(),
            (string) $user->getAuthIdentifier(),
            'booking',
            'Booking rescheduled',
            'Your booking '.(string) $booking->getKey().' moved to '.$newStart->toDateTimeString().'.'
        );

        return new JsonResponse([
            'data' => self::serialize($booking->fresh()),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function history(Request $request, string $id): JsonResponse
    {
        $found = $this->ownBooking($request, $id);
        if ($found instanceof JsonResponse) {
            return $found;
        }

        $transitions = BookingTransition::query()
            ->where('booking_id', (string) $found['booking']->getKey())
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return new JsonResponse([
            'data' => $transitions->map(static fn (BookingTransition $transition): array => [
                'id' => (string) $transition->getKey(),
                'from' => $transition->from_status,
                'to' => $transition->to_status,
                'reason' => $transition->reason,
                'actor_id' => $transition->actor_id,
                'at' => $transition->created_at,
            ])->all(),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    private function overlaps(Booking $booking, CarbonImmutable $newStart, CarbonImmutable $newEnd): bool
    {
        $userId = (string) $booking->user_id;

        $others = Booking::query()
            ->where('id', '!=', (string) $booking->getKey())
            ->where('user_id', $userId)
            ->whereIn('status', [BookingLifecycle::STATUS_CONFIRMED, BookingLifecycle::STATUS_ASSIGNED])
            ->whereNotNull('scheduled_at')
            ->get();

        foreach ($others as $other) {
            $start = CarbonImmutable::parse($other->scheduled_at);
            $end = $start->addMinutes($other->duration_minutes);

            if ($start < $newEnd && $newStart < $end) {
                return true;
            }
        }

        return false;
    }

    private function recordTransition(Booking $booking, string $from, string $to, ?string $reason, string $actorId): void
    {
        $transition = new BookingTransition();
        $transition->forceFill([
            'booking_id' => (string) $booking->getKey(),
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
            'actor_id' => $actorId,
        ]);
        $transition->save();
    }

    /**
     * @return array<string, mixed>
     */
    private static function serialize(Booking $booking): array
    {
        return [
            'id' => (string) $booking->getKey(),
            'object' => 'booking',
            'status' => $booking->status,
            'service_sku' => $booking->service_sku,
            'scheduled_at' => $booking->scheduled_at,
            'duration_minutes' => $booking->duration_minutes,
            'version' => $booking->version,
            'actions' => BookingLifecycle::actions($booking->status),
        ];
    }
}
