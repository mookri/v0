<?php

declare(strict_types=1);

namespace App\Modules\Booking\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Modules\Booking\Application\ConfirmBookingNoShowUseCase;
use App\Modules\Booking\Application\RecordPanditArrivalUseCase;
use App\Modules\Booking\Domain\Exceptions\BookingNotFoundException;
use App\Modules\Booking\Domain\Exceptions\BookingStateForbiddenException;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\PanditArrival;
use App\Modules\Pandit\Contracts\ManagesPanditAssignments;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Arrival evidence & operational NO_SHOW HTTP surface (TS-01 / TS-02).
 * Does not extend App\Http\Controllers\Controller (Deptrac ModuleHttp compliant).
 */
final class BookingArrivalController
{
    public function __construct(
        private readonly RecordPanditArrivalUseCase $arrivals,
        private readonly ConfirmBookingNoShowUseCase $noShows,
        private readonly ManagesPanditAssignments $assignments,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    public function recordArrival(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $validated = $request->validate([
            'arrived_at' => ['sometimes', 'nullable', 'date'],
            'channel' => ['sometimes', 'string', 'in:partner_app,coordinator,ops_override'],
        ]);

        $assignment = $this->assignments->assignmentForBooking($id);
        if ($assignment === null || ($assignment['pandit_user_id'] !== null && (int) $assignment['pandit_user_id'] !== (int) $user->getAuthIdentifier())) {
            if (($validated['channel'] ?? 'partner_app') === PanditArrival::SOURCE_PARTNER_APP) {
                return new JsonResponse([
                    'error' => ['code' => 'forbidden', 'message' => 'Only the assigned pandit may mark arrival via partner app.', 'request_id' => $this->requestId($request)],
                ], 403);
            }
        }

        $arrivedAt = isset($validated['arrived_at']) ? CarbonImmutable::parse($validated['arrived_at']) : null;
        $channel = $validated['channel'] ?? PanditArrival::SOURCE_PARTNER_APP;

        try {
            $arrival = $this->arrivals->recordPrimary(
                $id,
                (string) $user->getAuthIdentifier(),
                $channel,
                $arrivedAt,
                $assignment['pandit_id'] ?? null,
            );
        } catch (BookingNotFoundException $e) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 404);
        } catch (BookingStateForbiddenException $e) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 409);
        }

        return new JsonResponse([
            'data' => [
                'id' => (string) $arrival->getKey(),
                'booking_id' => $arrival->booking_id,
                'pandit_id' => $arrival->pandit_id,
                'arrived_at' => $arrival->arrived_at->toJSON(),
                'recorded_by_actor_id' => $arrival->recorded_by_actor_id,
                'source_channel' => $arrival->source_channel,
                'delta_minutes' => $arrival->delta_minutes,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function corroborateArrival(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $booking = Booking::query()->where('id', $id)->where('user_id', $user->getAuthIdentifier())->first();
        if ($booking === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Booking not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        try {
            $arrival = $this->arrivals->corroborate($id, (string) $user->getAuthIdentifier());
        } catch (BookingNotFoundException $e) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return new JsonResponse([
            'data' => [
                'id' => (string) $arrival->getKey(),
                'booking_id' => $arrival->booking_id,
                'arrived_at' => $arrival->arrived_at->toJSON(),
                'corroborated_at' => $arrival->corroborated_at?->toJSON(),
                'corroborated_by_id' => $arrival->corroborated_by_id,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function confirmNoShow(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $validated = $request->validate([
            'outreach_attempted' => ['required', 'boolean', 'accepted'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'grace_minutes' => ['sometimes', 'integer', 'min:1', 'max:180'],
        ]);

        try {
            $booking = $this->noShows->execute(
                $id,
                (string) $user->getAuthIdentifier(),
                (bool) $validated['outreach_attempted'],
                $validated['reason'],
                (int) ($validated['grace_minutes'] ?? ConfirmBookingNoShowUseCase::DEFAULT_GRACE_MINUTES),
            );
        } catch (BookingNotFoundException $e) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 404);
        } catch (BookingStateForbiddenException $e) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 409);
        }

        return new JsonResponse([
            'data' => [
                'id' => (string) $booking->getKey(),
                'status' => $booking->status,
                'status_detail' => $booking->status_detail,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}

