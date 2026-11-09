<?php

namespace App\Modules\Booking\Http\Controllers;

use App\Models\User;
use App\Modules\Booking\Application\CancelBookingUseCase;
use App\Modules\Booking\Application\ConfirmBookingFulfillmentUseCase;
use App\Modules\Booking\Application\CreateReservationDraftUseCase;
use App\Modules\Booking\Application\InitiateCheckoutHoldUseCase;
use App\Modules\Booking\Application\PreviewCancellationUseCase;
use App\Modules\Booking\Application\PreviewRescheduleUseCase;
use App\Modules\Booking\Application\RebookBookingUseCase;
use App\Modules\Booking\Application\RescheduleBookingUseCase;
use App\Modules\Booking\Domain\CommandDeduplicator;
use App\Modules\Booking\Domain\Exceptions\BookingConflictException;
use App\Modules\Booking\Domain\Exceptions\BookingNotFoundException;
use App\Modules\Booking\Domain\Exceptions\BookingStateForbiddenException;
use App\Modules\Booking\Domain\Exceptions\BookingVersionConflictException;
use App\Modules\Booking\Domain\Exceptions\IdempotencyConflictException;
use App\Modules\Booking\Domain\Exceptions\ResourceUnavailableException;
use App\Modules\Booking\Http\BookingProblemResponder;
use App\Modules\Booking\Http\ReservationPresenter;
use App\Modules\Booking\Models\Booking;
use App\Platform\Identity\MachinePrincipal;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Booking V2 REST API Controller.
 * Provides canonical weak ETags, RFC 7807 problem details, read-only previews,
 * strict precondition guards, and idempotency deduplication.
 */
final class ReservationV2Controller
{
    public function __construct(
        private readonly CreateReservationDraftUseCase $createDraft,
        private readonly InitiateCheckoutHoldUseCase $checkoutHold,
        private readonly ConfirmBookingFulfillmentUseCase $confirmFulfillment,
        private readonly PreviewCancellationUseCase $previewCancellation,
        private readonly CancelBookingUseCase $cancelBooking,
        private readonly PreviewRescheduleUseCase $previewReschedule,
        private readonly RescheduleBookingUseCase $rescheduleBooking,
        private readonly RebookBookingUseCase $rebookBooking,
        private readonly CommandDeduplicator $deduplicator,
    ) {
    }

    /**
     * @return array{0: string, 1: User}|JsonResponse
     */
    private function resolveTenantAndUser(Request $request): array|JsonResponse
    {
        if ($request->user() instanceof MachinePrincipal) {
            return BookingProblemResponder::create(
                403,
                'membership_inactive',
                'Forbidden',
                'Machine principals cannot operate human customer bookings.'
            );
        }

        $user = $request->user();
        if (! $user instanceof User) {
            return BookingProblemResponder::create(
                401,
                'unauthenticated',
                'Unauthenticated',
                'Authentication required.'
            );
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        if ($tenantId === '') {
            return BookingProblemResponder::create(
                400,
                'tenant_header_missing',
                'Bad Request',
                'Tenant context missing.'
            );
        }

        return [$tenantId, $user];
    }

    private function resolveOwnBooking(string $tenantId, string $bookingId, string $userId): ?Booking
    {
        return Booking::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $bookingId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * POST /api/v2/booking/reservations
     */
    public function store(Request $request): JsonResponse
    {
        $resolved = $this->resolveTenantAndUser($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }
        [$tenantId, $user] = $resolved;

        $validator = Validator::make($request->all(), [
            'service_sku' => ['required', 'string'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['sometimes', 'nullable', 'integer', 'min:15', 'max:1440'],
            'address_id' => ['sometimes', 'nullable', 'string'],
            'address_snapshot' => ['sometimes', 'nullable', 'array'],
            'selected_pandit_id' => ['sometimes', 'nullable', 'string'],
            'selected_temple_id' => ['sometimes', 'nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return BookingProblemResponder::create(
                422,
                'validation_failed',
                'Validation Failed',
                'The given reservation data was invalid.',
                null,
                ['invalid_params' => $validator->errors()->toArray()]
            );
        }

        $idempotencyKey = trim((string) $request->header('Idempotency-Key', (string) Str::uuid()));
        $validated = $validator->validated();

        try {
            $response = $this->deduplicator->execute(
                $tenantId,
                (string) $user->getKey(),
                'booking.reserve',
                $idempotencyKey,
                $validated,
                null,
                function () use ($tenantId, $user, $idempotencyKey, $validated): array {
                    $booking = $this->createDraft->execute(
                        $tenantId,
                        (string) $user->getKey(),
                        $idempotencyKey,
                        $validated
                    );

                    return [
                        'status' => 201,
                        'data' => ReservationPresenter::toArray($booking),
                        'target_id' => (string) $booking->getKey(),
                        'version' => (int) $booking->version,
                    ];
                }
            );
        } catch (IdempotencyConflictException $e) {
            return BookingProblemResponder::create(
                409,
                'idempotency_conflict',
                'Idempotency Conflict',
                $e->getMessage()
            );
        } catch (\InvalidArgumentException $e) {
            return BookingProblemResponder::create(
                422,
                'validation_failed',
                'Invalid Request',
                $e->getMessage()
            );
        }

        $version = $response['version'] ?? 1;
        return new JsonResponse([
            'data' => $response['data'],
            'meta' => ['request_id' => (string) $request->attributes->get('request_id', '')],
        ], $response['status'] ?? 201, [
            'ETag' => 'W/"v' . $version . '"',
        ]);
    }

    /**
     * GET /api/v2/booking/reservations/{bookingId}
     */
    public function show(Request $request, string $bookingId): JsonResponse
    {
        $resolved = $this->resolveTenantAndUser($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }
        [$tenantId, $user] = $resolved;

        $booking = $this->resolveOwnBooking($tenantId, $bookingId, (string) $user->getKey());
        if ($booking === null) {
            return BookingProblemResponder::create(
                404,
                'not_found',
                'Booking Not Found',
                'The requested reservation does not exist or access is denied.'
            );
        }

        return new JsonResponse([
            'data' => ReservationPresenter::toArray($booking),
            'meta' => ['request_id' => (string) $request->attributes->get('request_id', '')],
        ], 200, [
            'ETag' => ReservationPresenter::etag($booking),
        ]);
    }

    /**
     * POST /api/v2/booking/reservations/{bookingId}/checkout
     */
    public function checkout(Request $request, string $bookingId): JsonResponse
    {
        $resolved = $this->resolveTenantAndUser($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }
        [$tenantId, $user] = $resolved;

        $booking = $this->resolveOwnBooking($tenantId, $bookingId, (string) $user->getKey());
        if ($booking === null) {
            return BookingProblemResponder::create(
                404,
                'not_found',
                'Booking Not Found',
                'The requested reservation does not exist.'
            );
        }

        $idempotencyKey = trim((string) $request->header('Idempotency-Key', (string) Str::uuid()));

        try {
            $response = $this->deduplicator->execute(
                $tenantId,
                (string) $user->getKey(),
                'booking.checkout',
                $idempotencyKey,
                ['booking_id' => $bookingId],
                $bookingId,
                function () use ($tenantId, $bookingId, $user): array {
                    $updated = $this->checkoutHold->execute(
                        $tenantId,
                        $bookingId,
                        (string) $user->getKey()
                    );

                    return [
                        'status' => 200,
                        'data' => ReservationPresenter::toArray($updated),
                        'target_id' => $bookingId,
                        'version' => (int) $updated->version,
                    ];
                }
            );
        } catch (BookingStateForbiddenException $e) {
            return BookingProblemResponder::create(
                409,
                'invalid_state_transition',
                'State Conflict',
                $e->getMessage()
            );
        } catch (ResourceUnavailableException $e) {
            return BookingProblemResponder::create(
                409,
                'resource_unavailable',
                'Resource Unavailable',
                $e->getMessage()
            );
        } catch (IdempotencyConflictException $e) {
            return BookingProblemResponder::create(
                409,
                'idempotency_conflict',
                'Idempotency Conflict',
                $e->getMessage()
            );
        }

        $version = $response['version'] ?? (int) $booking->version;
        return new JsonResponse([
            'data' => $response['data'],
            'meta' => ['request_id' => (string) $request->attributes->get('request_id', '')],
        ], $response['status'] ?? 200, [
            'ETag' => 'W/"v' . $version . '"',
        ]);
    }

    /**
     * POST /api/v2/booking/reservations/{bookingId}/confirm
     */
    public function confirm(Request $request, string $bookingId): JsonResponse
    {
        $resolved = $this->resolveTenantAndUser($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }
        [$tenantId, $user] = $resolved;

        $booking = $this->resolveOwnBooking($tenantId, $bookingId, (string) $user->getKey());
        if ($booking === null) {
            return BookingProblemResponder::create(
                404,
                'not_found',
                'Booking Not Found',
                'The requested reservation does not exist.'
            );
        }

        // Strict Precondition check: If-Match required
        $ifMatchVersion = BookingProblemResponder::parseIfMatch($request->header('If-Match'));
        if ($ifMatchVersion === null) {
            return BookingProblemResponder::create(
                412,
                'precondition_required',
                'Precondition Required',
                'State mutations require an If-Match header with weak ETag W/"v{version}".'
            );
        }

        if ($ifMatchVersion !== (int) $booking->version) {
            return BookingProblemResponder::create(
                412,
                'precondition_failed',
                'Precondition Failed',
                "Target booking version is {$booking->version}, but If-Match specified {$ifMatchVersion}."
            );
        }

        $idempotencyKey = trim((string) $request->header('Idempotency-Key', (string) Str::uuid()));

        try {
            $response = $this->deduplicator->execute(
                $tenantId,
                (string) $user->getKey(),
                'booking.confirm',
                $idempotencyKey,
                ['booking_id' => $bookingId, 'version' => $ifMatchVersion],
                $bookingId,
                function () use ($tenantId, $bookingId, $user): array {
                    $updated = $this->confirmFulfillment->execute(
                        $tenantId,
                        $bookingId,
                        (string) $user->getKey()
                    );

                    return [
                        'status' => 200,
                        'data' => ReservationPresenter::toArray($updated),
                        'target_id' => $bookingId,
                        'version' => (int) $updated->version,
                    ];
                }
            );
        } catch (BookingStateForbiddenException $e) {
            return BookingProblemResponder::create(
                409,
                'invalid_state_transition',
                'State Conflict',
                $e->getMessage()
            );
        } catch (IdempotencyConflictException $e) {
            return BookingProblemResponder::create(
                409,
                'idempotency_conflict',
                'Idempotency Conflict',
                $e->getMessage()
            );
        }

        $version = $response['version'] ?? ((int) $booking->version + 1);
        return new JsonResponse([
            'data' => $response['data'],
            'meta' => ['request_id' => (string) $request->attributes->get('request_id', '')],
        ], $response['status'] ?? 200, [
            'ETag' => 'W/"v' . $version . '"',
        ]);
    }

    /**
     * POST /api/v2/booking/reservations/{bookingId}/cancellation-preview
     */
    public function previewCancellation(Request $request, string $bookingId): JsonResponse
    {
        $resolved = $this->resolveTenantAndUser($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }
        [$tenantId, $user] = $resolved;

        try {
            $preview = $this->previewCancellation->execute($tenantId, $bookingId, (string) $user->getKey());
        } catch (BookingNotFoundException) {
            return BookingProblemResponder::create(
                404,
                'not_found',
                'Booking Not Found',
                'The requested reservation does not exist.'
            );
        } catch (BookingStateForbiddenException $e) {
            return BookingProblemResponder::create(
                409,
                'cancellation_not_allowed',
                'Cancellation Forbidden',
                $e->getMessage()
            );
        }

        return new JsonResponse([
            'data' => $preview,
            'meta' => ['request_id' => (string) $request->attributes->get('request_id', '')],
        ]);
    }

    /**
     * POST /api/v2/booking/reservations/{bookingId}/cancel
     */
    public function cancel(Request $request, string $bookingId): JsonResponse
    {
        $resolved = $this->resolveTenantAndUser($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }
        [$tenantId, $user] = $resolved;

        $booking = $this->resolveOwnBooking($tenantId, $bookingId, (string) $user->getKey());
        if ($booking === null) {
            return BookingProblemResponder::create(
                404,
                'not_found',
                'Booking Not Found',
                'The requested reservation does not exist.'
            );
        }

        // Precondition check
        $ifMatchVersion = BookingProblemResponder::parseIfMatch($request->header('If-Match'));
        if ($ifMatchVersion === null) {
            return BookingProblemResponder::create(
                412,
                'precondition_required',
                'Precondition Required',
                'State mutations require an If-Match header with weak ETag W/"v{version}".'
            );
        }

        if ($ifMatchVersion !== (int) $booking->version) {
            return BookingProblemResponder::create(
                412,
                'precondition_failed',
                'Precondition Failed',
                "Target booking version is {$booking->version}, but If-Match specified {$ifMatchVersion}."
            );
        }

        $idempotencyKey = trim((string) $request->header('Idempotency-Key', (string) Str::uuid()));
        $reason = $request->input('reason');

        try {
            $response = $this->deduplicator->execute(
                $tenantId,
                (string) $user->getKey(),
                'booking.cancel',
                $idempotencyKey,
                ['booking_id' => $bookingId, 'reason' => $reason, 'version' => $ifMatchVersion],
                $bookingId,
                function () use ($tenantId, $bookingId, $user, $reason): array {
                    $result = $this->cancelBooking->execute(
                        $tenantId,
                        $bookingId,
                        (string) $user->getKey(),
                        is_string($reason) ? $reason : null
                    );

                    return [
                        'status' => 200,
                        'data' => array_merge(ReservationPresenter::toArray($result['booking']), [
                            'cancellation' => [
                                'fee_minor' => $result['fee_minor'],
                                'refund_minor' => $result['refund_minor'],
                            ],
                        ]),
                        'target_id' => $bookingId,
                        'version' => (int) $result['booking']->version,
                    ];
                }
            );
        } catch (BookingNotFoundException) {
            return BookingProblemResponder::create(
                404,
                'not_found',
                'Booking Not Found',
                'Booking not found.'
            );
        } catch (BookingStateForbiddenException $e) {
            return BookingProblemResponder::create(
                409,
                'cancellation_not_allowed',
                'Cancellation Forbidden',
                $e->getMessage()
            );
        } catch (IdempotencyConflictException $e) {
            return BookingProblemResponder::create(
                409,
                'idempotency_conflict',
                'Idempotency Conflict',
                $e->getMessage()
            );
        }

        $version = $response['version'] ?? ((int) $booking->version + 1);
        return new JsonResponse([
            'data' => $response['data'],
            'meta' => ['request_id' => (string) $request->attributes->get('request_id', '')],
        ], $response['status'] ?? 200, [
            'ETag' => 'W/"v' . $version . '"',
        ]);
    }

    /**
     * POST /api/v2/booking/reservations/{bookingId}/reschedule-preview
     */
    public function previewReschedule(Request $request, string $bookingId): JsonResponse
    {
        $resolved = $this->resolveTenantAndUser($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }
        [$tenantId, $user] = $resolved;

        $validator = Validator::make($request->all(), [
            'new_scheduled_at' => ['required', 'date'],
        ]);

        if ($validator->fails()) {
            return BookingProblemResponder::create(
                422,
                'validation_failed',
                'Validation Failed',
                'New scheduled time is required.',
                null,
                ['invalid_params' => $validator->errors()->toArray()]
            );
        }

        try {
            $newStart = CarbonImmutable::parse((string) $request->input('new_scheduled_at'))->utc();
            $preview = $this->previewReschedule->execute($tenantId, $bookingId, (string) $user->getKey(), $newStart);
        } catch (BookingNotFoundException) {
            return BookingProblemResponder::create(
                404,
                'not_found',
                'Booking Not Found',
                'The requested reservation does not exist.'
            );
        } catch (BookingStateForbiddenException $e) {
            return BookingProblemResponder::create(
                409,
                'reschedule_forbidden',
                'Reschedule Forbidden',
                $e->getMessage()
            );
        }

        return new JsonResponse([
            'data' => $preview,
            'meta' => ['request_id' => (string) $request->attributes->get('request_id', '')],
        ]);
    }

    /**
     * POST /api/v2/booking/reservations/{bookingId}/reschedule
     */
    public function reschedule(Request $request, string $bookingId): JsonResponse
    {
        $resolved = $this->resolveTenantAndUser($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }
        [$tenantId, $user] = $resolved;

        $booking = $this->resolveOwnBooking($tenantId, $bookingId, (string) $user->getKey());
        if ($booking === null) {
            return BookingProblemResponder::create(
                404,
                'not_found',
                'Booking Not Found',
                'The requested reservation does not exist.'
            );
        }

        // Precondition check
        $ifMatchVersion = BookingProblemResponder::parseIfMatch($request->header('If-Match'));
        if ($ifMatchVersion === null) {
            return BookingProblemResponder::create(
                412,
                'precondition_required',
                'Precondition Required',
                'State mutations require an If-Match header with weak ETag W/"v{version}".'
            );
        }

        if ($ifMatchVersion !== (int) $booking->version) {
            return BookingProblemResponder::create(
                412,
                'precondition_failed',
                'Precondition Failed',
                "Target booking version is {$booking->version}, but If-Match specified {$ifMatchVersion}."
            );
        }

        $validator = Validator::make($request->all(), [
            'new_scheduled_at' => ['required', 'date'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return BookingProblemResponder::create(
                422,
                'validation_failed',
                'Validation Failed',
                'Invalid reschedule parameters.',
                null,
                ['invalid_params' => $validator->errors()->toArray()]
            );
        }

        $newStart = CarbonImmutable::parse((string) $request->input('new_scheduled_at'))->utc();
        $reason = $request->input('reason');
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', (string) Str::uuid()));

        try {
            $response = $this->deduplicator->execute(
                $tenantId,
                (string) $user->getKey(),
                'booking.reschedule',
                $idempotencyKey,
                [
                    'booking_id' => $bookingId,
                    'new_scheduled_at' => $newStart->toIso8601String(),
                    'reason' => $reason,
                    'version' => $ifMatchVersion,
                ],
                $bookingId,
                function () use ($tenantId, $bookingId, $user, $newStart, $ifMatchVersion, $reason): array {
                    $result = $this->rescheduleBooking->execute(
                        $tenantId,
                        $bookingId,
                        (string) $user->getKey(),
                        $newStart,
                        $ifMatchVersion,
                        is_string($reason) ? $reason : null
                    );

                    return [
                        'status' => 200,
                        'data' => ReservationPresenter::toArray($result['booking']),
                        'target_id' => $bookingId,
                        'version' => (int) $result['booking']->version,
                    ];
                }
            );
        } catch (BookingNotFoundException) {
            return BookingProblemResponder::create(
                404,
                'not_found',
                'Booking Not Found',
                'Booking not found.'
            );
        } catch (BookingStateForbiddenException $e) {
            return BookingProblemResponder::create(
                409,
                'reschedule_forbidden',
                'Reschedule Forbidden',
                $e->getMessage()
            );
        } catch (BookingConflictException $e) {
            return BookingProblemResponder::create(
                409,
                'slot_unavailable',
                'Slot Unavailable',
                $e->getMessage()
            );
        } catch (BookingVersionConflictException $e) {
            return BookingProblemResponder::create(
                409,
                'version_conflict',
                'Version Conflict',
                $e->getMessage()
            );
        } catch (IdempotencyConflictException $e) {
            return BookingProblemResponder::create(
                409,
                'idempotency_conflict',
                'Idempotency Conflict',
                $e->getMessage()
            );
        }

        $version = $response['version'] ?? ((int) $booking->version + 1);
        return new JsonResponse([
            'data' => $response['data'],
            'meta' => ['request_id' => (string) $request->attributes->get('request_id', '')],
        ], $response['status'] ?? 200, [
            'ETag' => 'W/"v' . $version . '"',
        ]);
    }

    /**
     * POST /api/v2/booking/reservations/{bookingId}/rebook
     */
    public function rebook(Request $request, string $bookingId): JsonResponse
    {
        $resolved = $this->resolveTenantAndUser($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }
        [$tenantId, $user] = $resolved;

        $validator = Validator::make($request->all(), [
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['sometimes', 'nullable', 'integer', 'min:15', 'max:1440'],
        ]);

        if ($validator->fails()) {
            return BookingProblemResponder::create(
                422,
                'validation_failed',
                'Validation Failed',
                'Scheduled time is required for rebooking.',
                null,
                ['invalid_params' => $validator->errors()->toArray()]
            );
        }

        $idempotencyKey = trim((string) $request->header('Idempotency-Key', (string) Str::uuid()));
        $validated = $validator->validated();

        try {
            $response = $this->deduplicator->execute(
                $tenantId,
                (string) $user->getKey(),
                'booking.rebook',
                $idempotencyKey,
                array_merge(['source_booking_id' => $bookingId], $validated),
                null,
                function () use ($tenantId, $bookingId, $user, $idempotencyKey, $validated): array {
                    $newBooking = $this->rebookBooking->execute(
                        $tenantId,
                        $bookingId,
                        (string) $user->getKey(),
                        $idempotencyKey,
                        $validated
                    );

                    return [
                        'status' => 201,
                        'data' => ReservationPresenter::toArray($newBooking),
                        'target_id' => (string) $newBooking->getKey(),
                        'version' => (int) $newBooking->version,
                    ];
                }
            );
        } catch (BookingNotFoundException) {
            return BookingProblemResponder::create(
                404,
                'not_found',
                'Source Booking Not Found',
                'The source booking to rebook was not found.'
            );
        } catch (IdempotencyConflictException $e) {
            return BookingProblemResponder::create(
                409,
                'idempotency_conflict',
                'Idempotency Conflict',
                $e->getMessage()
            );
        } catch (\InvalidArgumentException $e) {
            return BookingProblemResponder::create(
                422,
                'validation_failed',
                'Invalid Request',
                $e->getMessage()
            );
        }

        $version = $response['version'] ?? 1;
        return new JsonResponse([
            'data' => $response['data'],
            'meta' => ['request_id' => (string) $request->attributes->get('request_id', '')],
        ], $response['status'] ?? 201, [
            'ETag' => 'W/"v' . $version . '"',
        ]);
    }

    public function shipment(Request $request, string $bookingId): JsonResponse
    {
        $resolved = $this->resolveTenantAndUser($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }
        [$tenantId, $user] = $resolved;

        $booking = Booking::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('id', $bookingId)
            ->first();

        if ($booking === null) {
            return BookingProblemResponder::create(404, 'not_found', 'Booking Not Found', "Booking {$bookingId} not found.");
        }

        if ((string) $booking->user_id !== (string) $user->getAuthIdentifier() && ! $request->attributes->get('is_admin', false)) {
            return BookingProblemResponder::create(403, 'permission_missing', 'Forbidden', 'Not authorized to view shipment for this booking.');
        }

        $order = (new Booking())->getConnection()
            ->table('vendor_orders')
            ->where('tenant_id', $tenantId)
            ->where('reference', 'bkg_' . $bookingId)
            ->first();

        if ($order === null) {
            return BookingProblemResponder::create(404, 'not_found', 'Shipment Not Found', 'No vendor order found for this booking.');
        }

        $shipment = (new Booking())->getConnection()
            ->table('shipments')
            ->where('tenant_id', $tenantId)
            ->where('order_id', $order->id)
            ->first();

        if ($shipment === null) {
            return BookingProblemResponder::create(404, 'not_found', 'Shipment Not Found', 'No shipment details found for this booking.');
        }

        return new JsonResponse([
            'data' => [
                'id' => (string) $shipment->id,
                'order_id' => (string) $shipment->order_id,
                'status' => (string) $shipment->status,
                'carrier_name' => $shipment->carrier_name,
                'tracking_code' => $shipment->tracking_code,
                'dispatched_at' => $shipment->dispatched_at,
                'delivered_at' => $shipment->delivered_at,
                'pod_note' => $shipment->pod_note,
            ],
            'meta' => ['request_id' => (string) $request->attributes->get('request_id', '')],
        ]);
    }
}

