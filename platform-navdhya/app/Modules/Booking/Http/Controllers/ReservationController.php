<?php

namespace App\Modules\Booking\Http\Controllers;

use App\Models\User;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Domain\BookingRequestFingerprint;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingTransition;
use App\Modules\Catalog\Contracts\CalculatesQuotes;
use App\Modules\Catalog\Contracts\ListsCatalogItems;
use App\Modules\Payment\Contracts\CreatesPaymentIntents;
use App\Platform\Api\CursorPagination;
use App\Platform\Audit\AuditWriter;
use App\Platform\Features\FeatureFlags;
use App\Platform\Outbox\Outbox;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reservations (B5, ADR-005): caller-scoped reads plus the cancel /
 * reschedule / confirm verbs through BookingLifecycle. Cancel is
 * idempotent on CANCELLED; every mutation appends a transition row,
 * audits, and writes its lifecycle fact to the transactional outbox in
 * the same tenant transaction (N2 — the drain replays facts into
 * customer notifications through the customers port; the controller
 * never notifies directly). Machine principals are denied —
 * reservations are human-only. Creation endpoint (P8): DRAFT status,
 * idempotent on Idempotency-Key, overlap guard via existing overlaps().
 *
 * Checkout (B3): :confirm re-quotes the SKU through the catalog quote
 * port, snapshots the amount, begins a payment intent through the
 * payment Contracts port and moves DRAFT → PENDING_PAYMENT in one
 * transaction; replaying it returns the same intent. Gated on the
 * fail-closed `payments.enabled` feature flag.
 */
final class ReservationController
{
    public function __construct(
        private readonly AuditWriter $audit,
        private readonly Outbox $outbox,
        private readonly ListsCatalogItems $catalog,
        private readonly CalculatesQuotes $quotes,
        private readonly CreatesPaymentIntents $payments,
        private readonly FeatureFlags $flags,
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

        if (!$user instanceof User) {
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

        if (!$user instanceof User) {
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
                static fn(Booking $booking): array => self::serialize($booking),
                $bookings
            ),
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // Machine principals denied — human-only, same as cancel/reschedule
        if ($request->user() instanceof \App\Platform\Identity\MachinePrincipal) {
            return new JsonResponse([
                'error' => ['code' => 'membership_inactive', 'message' => 'No active membership in this tenant.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        $user = $request->user();

        if (!$user instanceof User) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        // Idempotency-Key required (fail-closed)
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
        if ($idempotencyKey === '' || strlen($idempotencyKey) > 128) {
            return new JsonResponse([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => 'Idempotency-Key header is required.',
                    'request_id' => $this->requestId($request),
                    'details' => ['fields' => ['Idempotency-Key' => 'Required']],
                ],
            ], 422);
        }

        $validated = $request->validate([
            'service_sku' => ['required', 'string', 'max:64'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'address_id' => ['nullable', 'string', 'max:26'],
            'address_snapshot' => ['nullable', 'array'],
        ]);

        // Verify SKU exists and is ACTIVE via Catalog port (no model import)
        $catalogItems = $this->catalog->itemsBySkus([$validated['service_sku']]);
        if ($catalogItems === [] || ($catalogItems[0]['status'] ?? '') !== 'ACTIVE') {
            return new JsonResponse([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => 'Invalid or inactive service SKU.',
                    'request_id' => $this->requestId($request),
                    'details' => ['fields' => ['service_sku' => 'Must be an active catalog item']],
                ],
            ], 422);
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $userId = (string) $user->getAuthIdentifier();

        $idempotencyHash = BookingRequestFingerprint::hash($validated);

        $existing = Booking::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing !== null) {
            if (!BookingRequestFingerprint::matches($validated, $existing->idempotency_hash, (int) $existing->idempotency_hash_version, $existing->address_snapshot)) {
                return new JsonResponse([
                    'error' => [
                        'code' => 'idempotency_key_conflict',
                        'message' => 'Idempotency-Key was already used with a different payload.',
                        'request_id' => $this->requestId($request),
                    ],
                ], 409);
            }

            // True replay: the same booking comes back.
            return new JsonResponse([
                'data' => self::serialize($existing),
                'meta' => ['request_id' => $this->requestId($request)],
            ], 200);
        }

        $newStart = CarbonImmutable::parse($validated['scheduled_at'])->utc();
        $duration = $validated['duration_minutes'] ?? 60;
        $newEnd = $newStart->addMinutes($duration);

        // Overlap guard (reuses existing logic, but for a new booking)
        $overlap = Booking::query()
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->whereIn('status', [BookingLifecycle::STATUS_CONFIRMED, BookingLifecycle::STATUS_ASSIGNED])
            ->whereNotNull('scheduled_at')
            ->get()
            ->filter(function (Booking $other) use ($newEnd, $newStart): bool {
                $start = CarbonImmutable::parse($other->scheduled_at);
                $end = $start->addMinutes($other->duration_minutes);
                return $start < $newEnd && $newStart < $end;
            })
            ->first();

        if ($overlap !== null) {
            return new JsonResponse([
                'error' => ['code' => 'conflict', 'message' => 'The new slot overlaps another booking.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        // Build address snapshot
        $addressSnapshot = $validated['address_snapshot'] ?? null;
        if ($addressSnapshot === null && isset($validated['address_id'])) {
            // TODO: Fetch address via Customers port when available
            // For v1, accept inline snapshot or omit
        }

        $booking = new Booking();
        $booking->forceFill([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'service_sku' => $validated['service_sku'],
            'idempotency_key' => $idempotencyKey,
            'idempotency_hash' => $idempotencyHash,
            'idempotency_hash_version' => BookingRequestFingerprint::VERSION,
            'status' => BookingLifecycle::STATUS_DRAFT,
            'scheduled_at' => $newStart->toDateTimeString(),
            'duration_minutes' => $duration,
            'address_snapshot' => $addressSnapshot,
            'version' => 1,
        ]);

        // Same-tx outbox (N2): the booking row, its creation transition
        // and the lifecycle fact commit atomically on the ambient tenant
        // connection — a crash between them can lose neither the state
        // nor the fact. The drain replays the fact into notifications.
        try {
            $booking->getConnection()->transaction(function () use ($booking, $tenantId, $userId, $validated, $newStart): void {
                $booking->save();

                $this->recordTransition($booking, '', BookingLifecycle::STATUS_DRAFT, 'created', $userId);
                $this->audit->record(
                    'booking.reservation.created',
                    ['module' => 'booking', 'resource_type' => 'booking', 'resource_id' => (string) $booking->getKey()],
                    null,
                    ['user_id' => $userId, 'service_sku' => $validated['service_sku']],
                );

                $this->outbox->append(
                    $tenantId,
                    'booking',
                    (string) $booking->getKey(),
                    'booking.reservation.created',
                    [
                        'booking_id' => (string) $booking->getKey(),
                        'user_id' => $userId,
                        'service_sku' => (string) $validated['service_sku'],
                        'scheduled_at' => $newStart->toDateTimeString(),
                    ],
                );
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // Lost the race on the (tenant, user, idempotency key) index:
            // the winner's booking is the answer.
            $winner = Booking::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($winner === null) {
                throw new \RuntimeException('booking idempotency conflict could not be resolved');
            }

            if (!BookingRequestFingerprint::matches($validated, $winner->idempotency_hash, (int) $winner->idempotency_hash_version, $winner->address_snapshot)) {
                return new JsonResponse([
                    'error' => [
                        'code' => 'idempotency_key_conflict',
                        'message' => 'Idempotency-Key was already used with a different payload.',
                        'request_id' => $this->requestId($request),
                    ],
                ], 409);
            }

            return new JsonResponse([
                'data' => self::serialize($winner),
                'meta' => ['request_id' => $this->requestId($request)],
            ], 200);
        }

        return new JsonResponse([
            'data' => self::serialize($booking->fresh()),
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
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

    public function cancel(Request $request, string $id, \App\Modules\Booking\Application\CancelBookingUseCase $cancelUseCase): JsonResponse
    {
        $found = $this->ownBooking($request, $id);
        if ($found instanceof JsonResponse) {
            return $found;
        }
        ['user' => $user, 'booking' => $booking] = $found;
        $tenantId = (string) app(TenantContext::class)->tenantId();

        try {
            $result = $cancelUseCase->execute(
                tenantId: $tenantId,
                bookingId: (string) $booking->getKey(),
                userId: $user->getAuthIdentifier(),
                reason: $request->input('reason')
            );

            return new JsonResponse([
                'data' => self::serialize($result['booking']),
                'meta' => ['request_id' => $this->requestId($request)],
            ]);
        } catch (\App\Modules\Booking\Domain\Exceptions\BookingStateForbiddenException $e) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 409);
        } catch (\App\Modules\Booking\Domain\Exceptions\BookingNotFoundException $e) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 404);
        }
    }

    /**
     * Begin checkout (B3): DRAFT → PENDING_PAYMENT with a payment intent
     * for the freshly re-quoted amount (server authority — the client
     * never supplies a total). Idempotent: a booking already awaiting
     * payment returns its existing intent, so safe retries never open a
     * second provider order.
     */
    public function confirm(Request $request, string $id): JsonResponse
    {
        $found = $this->ownBooking($request, $id);
        if ($found instanceof JsonResponse) {
            return $found;
        }
        ['user' => $user, 'booking' => $booking] = $found;

        // Feature gate (doc 07 §10 rule 1): payments.enabled is seeded
        // default-off; while off the checkout surface does not exist
        // (fail-closed, no oracle).
        if (!$this->flags->enabled('payments.enabled')) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Booking not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $bookingId = (string) $booking->getKey();

        // Idempotent replay: checkout already began — the same intent
        // comes back (safe retries, no second provider order).
        if ($booking->status === BookingLifecycle::STATUS_PENDING_PAYMENT) {
            $intent = $this->payments->forBooking($tenantId, $bookingId);
            if ($intent === null) {
                return new JsonResponse([
                    'error' => ['code' => 'state_forbidden', 'message' => 'Booking awaits payment without an intent — operator attention.', 'request_id' => $this->requestId($request)],
                ], 409);
            }

            return new JsonResponse([
                'data' => $intent,
                'meta' => ['request_id' => $this->requestId($request)],
            ], 201);
        }

        if ($booking->status !== BookingLifecycle::STATUS_DRAFT) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Cannot initiate payment in this state.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        // Currency of record rides the catalog item (the read port
        // carries it); the amount is re-quoted HERE via QuoteCalculator,
        // never client-supplied.
        $items = $this->catalog->itemsBySkus([(string) $booking->service_sku]);
        $currency = $items[0]['currency'] ?? null;
        if ($items === [] || !is_string($currency) || $currency === '') {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => 'Service SKU is no longer payable.', 'request_id' => $this->requestId($request)],
            ], 422);
        }

        $quote = $this->quotes->quote($currency, [
            ['sku' => (string) $booking->service_sku, 'quantity' => 1],
        ], CarbonImmutable::now('UTC'));

        if (!($quote['ok'] ?? false)) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => 'Quote failed: ' . (string) ($quote['error'] ?? 'unavailable'), 'request_id' => $this->requestId($request)],
            ], 422);
        }

        // Two COMMITTED transactions, not one.
        //
        // TX1 - the payment intent and the provider order. The booking
        // row is locked here, so two concurrent confirms serialise and
        // only ONE provider order is ever opened. The intent row, the
        // outbox fact and the audit row commit with it.
        //
        // TX2 - the booking state change. It can fail on its own (the
        // booking was cancelled in between). When it does, the provider
        // order is still recorded by a COMMITTED intent row, and a
        // retry reuses that order instead of opening a second, payable
        // one. With a single transaction a rollback destroyed the
        // intent row while the order lived on at the provider - an
        // orphan the customer could still pay.
        $intentOutcome = $booking->getConnection()->transaction(function () use ($booking, $user, $tenantId, $currency, $quote): array {
            $locked = Booking::query()
                ->whereKey((string) $booking->getKey())
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                return ['not_found' => true];
            }

            if ($locked->status === BookingLifecycle::STATUS_PENDING_PAYMENT) {
                return ['replay' => true];
            }

            if ($locked->status !== BookingLifecycle::STATUS_DRAFT) {
                return ['conflict' => true];
            }

            return $this->payments->begin(
                tenantId: $tenantId,
                bookingId: (string) $locked->getKey(),
                userId: (int) $user->getAuthIdentifier(),
                amountMinor: (int) $quote['total_minor'],
                currency: $currency,
                quote: $quote,
            );
        });

        if (isset($intentOutcome['not_found'])) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Booking not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        if (isset($intentOutcome['replay'])) {
            // The intent already exists: the same one comes back.
            $intent = $this->payments->forBooking($tenantId, $bookingId);
            if ($intent === null) {
                return new JsonResponse([
                    'error' => ['code' => 'state_forbidden', 'message' => 'Booking awaits payment without an intent — operator attention.', 'request_id' => $this->requestId($request)],
                ], 409);
            }

            return new JsonResponse([
                'data' => $intent,
                'meta' => ['request_id' => $this->requestId($request)],
            ], 201);
        }

        if (isset($intentOutcome['conflict'])) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Cannot initiate payment in this state.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        if (!($intentOutcome['ok'] ?? false)) {
            if (($intentOutcome['reason'] ?? null) === 'gateway_unavailable') {
                return new JsonResponse([
                    'error' => ['code' => 'dependency_unavailable', 'message' => 'Payment provider is unavailable.', 'request_id' => $this->requestId($request)],
                ], 503);
            }

            return new JsonResponse([
                'error' => ['code' => 'dependency_unavailable', 'message' => 'Payment could not be initiated.', 'request_id' => $this->requestId($request)],
            ], 503);
        }

        $outcome = $booking->getConnection()->transaction(function () use ($booking, $user, $intentOutcome): array {
            $locked = Booking::query()
                ->whereKey((string) $booking->getKey())
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                return ['not_found' => true];
            }

            if ($locked->status === BookingLifecycle::STATUS_PENDING_PAYMENT) {
                return ['replay' => true];
            }

            if ($locked->status !== BookingLifecycle::STATUS_DRAFT) {
                return ['conflict' => true];
            }

            $locked->forceFill([
                'status' => BookingLifecycle::STATUS_PENDING_PAYMENT,
                'total_minor' => (int) $intentOutcome['intent']['amount_minor'],
                'currency' => $intentOutcome['intent']['currency'],
                'version' => $locked->version + 1,
            ])->save();

            $this->recordTransition(
                $locked,
                BookingLifecycle::STATUS_DRAFT,
                BookingLifecycle::STATUS_PENDING_PAYMENT,
                'payment_initiated',
                (string) $user->getAuthIdentifier(),
            );

            $this->audit->record(
                'booking.payment.initiated',
                ['module' => 'booking', 'resource_type' => 'booking', 'resource_id' => (string) $locked->getKey()],
                ['status' => BookingLifecycle::STATUS_DRAFT],
                ['status' => BookingLifecycle::STATUS_PENDING_PAYMENT, 'total_minor' => (int) $intentOutcome['intent']['amount_minor'], 'currency' => $intentOutcome['intent']['currency']],
            );

            return ['intent' => $intentOutcome['intent']];
        });

        if (isset($outcome['not_found'])) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Booking not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        if (isset($outcome['replay'])) {
            // Same-tx construction: the intent exists with the booking's
            // PENDING_PAYMENT row it raced past.
            $intent = $this->payments->forBooking($tenantId, $bookingId);
            if ($intent === null) {
                return new JsonResponse([
                    'error' => ['code' => 'state_forbidden', 'message' => 'Booking awaits payment without an intent — operator attention.', 'request_id' => $this->requestId($request)],
                ], 409);
            }

            return new JsonResponse([
                'data' => $intent,
                'meta' => ['request_id' => $this->requestId($request)],
            ], 201);
        }

        if (isset($outcome['conflict'])) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Cannot initiate payment in this state.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        if (($outcome['reason'] ?? null) === 'gateway_unavailable') {
            return new JsonResponse([
                'error' => ['code' => 'dependency_unavailable', 'message' => 'Payment provider is unavailable.', 'request_id' => $this->requestId($request)],
            ], 503);
        }

        return new JsonResponse([
            'data' => $outcome['intent'],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function reschedule(Request $request, string $id, \App\Modules\Booking\Application\RescheduleBookingUseCase $rescheduleUseCase): JsonResponse
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

        $newStart = CarbonImmutable::parse($validated['new_start_time'])->utc();
        $tenantId = (string) app(TenantContext::class)->tenantId();

        try {
            $result = $rescheduleUseCase->execute(
                tenantId: $tenantId,
                bookingId: (string) $booking->getKey(),
                userId: $user->getAuthIdentifier(),
                newStart: $newStart,
                expectedVersion: (int) $validated['version'],
                reason: $request->input('reason')
            );

            return new JsonResponse([
                'data' => self::serialize($result['booking']),
                'meta' => ['request_id' => $this->requestId($request)],
            ]);
        } catch (\App\Modules\Booking\Domain\Exceptions\BookingVersionConflictException $e) {
            return new JsonResponse([
                'error' => ['code' => 'version_conflict', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 409);
        } catch (\App\Modules\Booking\Domain\Exceptions\BookingStateForbiddenException $e) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 409);
        } catch (\App\Modules\Booking\Domain\Exceptions\BookingConflictException $e) {
            return new JsonResponse([
                'error' => ['code' => 'conflict', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 409);
        } catch (\App\Modules\Booking\Domain\Exceptions\BookingNotFoundException $e) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 404);
        }
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
            'data' => $transitions->map(static fn(BookingTransition $transition): array => [
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
            // Checkout snapshot (B3): null until :confirm re-quotes.
            'total_minor' => $booking->total_minor,
            'currency' => $booking->currency,
            'version' => $booking->version,
            'actions' => BookingLifecycle::actions($booking->status),
        ];
    }
}
