<?php

namespace App\Modules\Pandit\Http\Controllers;

use App\Modules\Pandit\AssignmentNotFoundException;
use App\Modules\Pandit\BookingWrongStateException;
use App\Modules\Pandit\Contracts\ManagesPanditAssignments;
use App\Modules\Pandit\Models\PanditProfile;
use App\Modules\Pandit\UnknownBookingException;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pandit assignment controller (self-serve + admin). Ownership is the
 * actor on the self surface (same posture as the customers module);
 * audit records are written by the assignment manager, not here.
 */
final class PanditAssignmentController
{
    public function __construct(
        private readonly ManagesPanditAssignments $assignments,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    /**
     * @param  array<string, list<string>>  $fields
     */
    private function error(Request $request, string $code, string $message, array $fields = []): JsonResponse
    {
        $error = ['code' => $code, 'message' => $message, 'request_id' => $this->requestId($request)];

        if ($fields !== []) {
            $error['details'] = ['fields' => $fields];
        }

        return new JsonResponse(['error' => $error]);
    }

    private function actor(Request $request): ?\App\Models\User
    {
        $user = $request->user();

        return $user instanceof \App\Models\User ? $user : null;
    }

    /**
     * The caller's own pandit profile (401 unauthenticated, 404 when the
     * user has no pandit profile).
     */
    private function ownProfile(Request $request): JsonResponse|PanditProfile
    {
        $user = $this->actor($request);

        if ($user === null) {
            return $this->error($request, 'unauthenticated', 'Unauthenticated.')->setStatusCode(401);
        }

        $profile = PanditProfile::query()
            ->where('tenant_id', (string) app(TenantContext::class)->tenantId())
            ->where('user_id', $user->getAuthIdentifier())
            ->first();

        if ($profile === null) {
            return $this->error($request, 'PANDIT_NOT_FOUND', 'Pandit profile not found.')->setStatusCode(404);
        }

        return $profile;
    }

    // SELF: Get own assignments
    public function ownIndex(Request $request): JsonResponse
    {
        $profile = $this->ownProfile($request);

        if ($profile instanceof JsonResponse) {
            return $profile;
        }

        $request->validate([
            'status' => ['sometimes', 'string', 'in:PENDING,CONFIRMED,IN_PROGRESS,DECLINED,COMPLETED,CANCELLED'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $status = is_string($request->query('status')) ? $request->query('status') : null;
        $limit = min((int) $request->query('limit', 20), 100);
        $cursor = is_string($request->query('cursor')) ? $request->query('cursor') : null;

        $result = $this->assignments->getAssignments((string) $profile->getKey(), $status, $limit, $cursor);

        return new JsonResponse([
            'data' => $result['items'],
            'meta' => ['request_id' => $this->requestId($request), 'page' => $result['page']],
        ]);
    }

    // SELF: Confirm assignment (accepts the booking: CONFIRMED → ASSIGNED)
    public function confirm(Request $request, string $id): JsonResponse
    {
        $profile = $this->ownProfile($request);

        if ($profile instanceof JsonResponse) {
            return $profile;
        }

        try {
            $assignment = $this->assignments->confirm((string) $profile->getKey(), $id);
        } catch (AssignmentNotFoundException $e) {
            return $this->error($request, 'PANDIT_ASSIGNMENT_NOT_FOUND', $e->getMessage())->setStatusCode(404);
        } catch (BookingWrongStateException $e) {
            return $this->error($request, 'PANDIT_BOOKING_WRONG_STATE', $e->getMessage())->setStatusCode(409);
        } catch (\InvalidArgumentException $e) {
            return $this->error($request, 'PANDIT_ASSIGNMENT_WRONG_STATE', $e->getMessage())->setStatusCode(409);
        }

        return new JsonResponse([
            'data' => $assignment,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // SELF: Start assignment (begins ceremony: ASSIGNED → IN_PROGRESS)
    public function start(Request $request, string $id): JsonResponse
    {
        $profile = $this->ownProfile($request);

        if ($profile instanceof JsonResponse) {
            return $profile;
        }

        $validated = $request->validate([
            'checklist' => ['sometimes', 'array'],
        ]);

        try {
            $assignment = $this->assignments->start(
                (string) $profile->getKey(),
                $id,
                is_array($validated['checklist'] ?? null) ? $validated['checklist'] : [],
            );
        } catch (AssignmentNotFoundException $e) {
            return $this->error($request, 'PANDIT_ASSIGNMENT_NOT_FOUND', $e->getMessage())->setStatusCode(404);
        } catch (BookingWrongStateException $e) {
            return $this->error($request, 'PANDIT_BOOKING_WRONG_STATE', $e->getMessage())->setStatusCode(409);
        } catch (\InvalidArgumentException $e) {
            return $this->error($request, 'PANDIT_ASSIGNMENT_WRONG_STATE', $e->getMessage())->setStatusCode(409);
        }

        return new JsonResponse([
            'data' => $assignment,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // SELF: Complete assignment (completes the booking: ASSIGNED|IN_PROGRESS → COMPLETED)
    public function complete(Request $request, string $id): JsonResponse
    {
        $profile = $this->ownProfile($request);

        if ($profile instanceof JsonResponse) {
            return $profile;
        }

        try {
            $assignment = $this->assignments->complete((string) $profile->getKey(), $id);
        } catch (AssignmentNotFoundException $e) {
            return $this->error($request, 'PANDIT_ASSIGNMENT_NOT_FOUND', $e->getMessage())->setStatusCode(404);
        } catch (BookingWrongStateException $e) {
            return $this->error($request, 'PANDIT_BOOKING_WRONG_STATE', $e->getMessage())->setStatusCode(409);
        } catch (\InvalidArgumentException $e) {
            return $this->error($request, 'PANDIT_ASSIGNMENT_WRONG_STATE', $e->getMessage())->setStatusCode(409);
        }

        return new JsonResponse([
            'data' => $assignment,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // SELF: Decline assignment
    public function decline(Request $request, string $id): JsonResponse
    {
        $profile = $this->ownProfile($request);

        if ($profile instanceof JsonResponse) {
            return $profile;
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $assignment = $this->assignments->decline(
                (string) $profile->getKey(),
                $id,
                (string) $validated['reason'],
            );
        } catch (AssignmentNotFoundException $e) {
            return $this->error($request, 'PANDIT_ASSIGNMENT_NOT_FOUND', $e->getMessage())->setStatusCode(404);
        } catch (\InvalidArgumentException $e) {
            return $this->error($request, 'PANDIT_ASSIGNMENT_WRONG_STATE', $e->getMessage())->setStatusCode(409);
        }

        return new JsonResponse([
            'data' => $assignment,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // ADMIN: Assign pandit to booking (capability: pandit:assignment:manage)
    public function adminStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pandit_id' => ['required', 'string', 'max:26'],
            'booking_id' => ['required', 'string', 'max:26'],
            'compensation_minor' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $assignment = $this->assignments->adminAssign(
                (string) $validated['pandit_id'],
                (string) $validated['booking_id'],
                (int) $validated['compensation_minor'],
            );
        } catch (UnknownBookingException) {
            // Cross-module ids never pass an `exists:` rule (the row lives on
            // another connection) — resolve them through the port instead.
            return $this->error($request, 'validation_failed', 'The given data was invalid.', [
                'booking_id' => ['unknown_booking'],
            ])->setStatusCode(422);
        } catch (ModelNotFoundException) {
            return $this->error($request, 'validation_failed', 'The given data was invalid.', [
                'pandit_id' => ['unknown_pandit'],
            ])->setStatusCode(422);
        } catch (\InvalidArgumentException $e) {
            return $this->error($request, 'PANDIT_ASSIGNMENT_BOOKING_CONFLICT', $e->getMessage())->setStatusCode(409);
        }

        return new JsonResponse([
            'data' => $assignment,
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    // ADMIN: Unassign
    public function adminDestroy(Request $request, string $id): JsonResponse
    {
        try {
            $this->assignments->adminUnassign($id);
        } catch (ModelNotFoundException) {
            return $this->error($request, 'PANDIT_ASSIGNMENT_NOT_FOUND', 'Assignment not found.')->setStatusCode(404);
        }

        return new JsonResponse([
            'data' => ['deleted' => true],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
