<?php

namespace App\Modules\Booking\Http\Controllers;

use App\Models\User;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingReview;
use App\Platform\Api\CursorPagination;
use App\Platform\Audit\AuditWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Booking reviews (B5): author-only, one per booking, on COMPLETED
 * bookings. Foreign bookings and non-completed states are distinguished
 * (404 vs 409) so clients can branch correctly.
 */
final class BookingReviewController
{
    public function __construct(private readonly AuditWriter $audit)
    {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    private function ownUser(Request $request): User|JsonResponse
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

        return $user;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        [$reviews, $page] = CursorPagination::paginate(
            BookingReview::query()->where('user_id', $user->getAuthIdentifier()),
            $request
        );

        return new JsonResponse([
            'data' => array_map(
                static fn (BookingReview $review): array => self::serialize($review),
                $reviews
            ),
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }

    public function store(Request $request, string $id): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $booking = Booking::query()
            ->where('id', $id)
            ->where('user_id', $user->getAuthIdentifier())
            ->first();

        if ($booking === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Booking not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        if ($booking->status !== BookingLifecycle::STATUS_COMPLETED) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Only completed bookings can be reviewed.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        if (BookingReview::query()->where('booking_id', (string) $booking->getKey())->exists()) {
            return new JsonResponse([
                'error' => ['code' => 'conflict', 'message' => 'This booking already has a review.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $review = new BookingReview();
        $review->forceFill([
            'booking_id' => (string) $booking->getKey(),
            'user_id' => (string) $user->getAuthIdentifier(),
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);
        $review->save();

        $this->audit->record(
            'booking.review.created',
            ['module' => 'booking', 'resource_type' => 'booking_review', 'resource_id' => (string) $review->getKey()],
            null,
            ['user_id' => (string) $user->getAuthIdentifier(), 'booking_id' => (string) $booking->getKey()]
        );

        return new JsonResponse([
            'data' => self::serialize($review),
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $review = BookingReview::query()
            ->where('id', $id)
            ->where('user_id', (string) $user->getAuthIdentifier())
            ->first();

        if ($review === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Review not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $review->delete();

        $this->audit->record(
            'booking.review.removed',
            ['module' => 'booking', 'resource_type' => 'booking_review', 'resource_id' => $id],
            null,
            ['user_id' => (string) $user->getAuthIdentifier()]
        );

        return new JsonResponse([
            'data' => ['object' => 'booking_review_deletion', 'deleted' => true],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function serialize(BookingReview $review): array
    {
        return [
            'id' => (string) $review->getKey(),
            'object' => 'booking_review',
            'booking_id' => $review->booking_id,
            'rating' => $review->rating,
            'comment' => $review->comment,
        ];
    }
}
