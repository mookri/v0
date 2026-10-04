<?php

namespace App\Modules\Pandit\Http\Controllers;

use App\Modules\Pandit\AvailabilityConflictException;
use App\Modules\Pandit\Contracts\ManagesPanditAvailability;
use App\Modules\Pandit\Models\PanditProfile;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pandit availability controller (public + self-serve).
 */
final class PanditAvailabilityController
{
    public function __construct(
        private readonly AuditWriter $audit,
        private readonly ManagesPanditAvailability $availability,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    // PUBLIC: Get availability for a verified pandit
    public function publicIndex(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = is_string($request->query('start_date')) ? $request->query('start_date') : null;
        $endDate = is_string($request->query('end_date')) ? $request->query('end_date') : null;

        // The port itself is fail-closed: it returns nothing for unknown,
        // unverified or suspended pandits.
        $slots = $this->availability->getPublicAvailability($id, $startDate, $endDate);

        if ($slots === []) {
            // Distinguish "no rows" from "not publicly visible" — but only
            // with the same 404, so an unverified pandit never leaks its
            // existence (PANDIT_NOT_FOUND, same as a missing id).
            $tenantId = (string) app(TenantContext::class)->tenantId();
            $profile = PanditProfile::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $id)
                ->first();

            $visible = $profile !== null
                && $profile->verification_status === PanditProfile::VERIFICATION_VERIFIED
                && $profile->status === PanditProfile::STATUS_ACTIVE;

            if (! $visible) {
                return new JsonResponse([
                    'error' => ['code' => 'PANDIT_NOT_FOUND', 'message' => 'Pandit not found.', 'request_id' => $this->requestId($request)],
                ], 404);
            }
        }

        return new JsonResponse([
            'data' => $slots,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // SELF: Get own availability
    public function ownIndex(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof \App\Models\User) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $profile = PanditProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->getAuthIdentifier())
            ->first();

        if ($profile === null) {
            return new JsonResponse([
                'error' => ['code' => 'PANDIT_NOT_FOUND', 'message' => 'Pandit profile not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $request->validate([
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = is_string($request->query('start_date')) ? $request->query('start_date') : null;
        $endDate = is_string($request->query('end_date')) ? $request->query('end_date') : null;

        $slots = $this->availability->getAvailability((string) $profile->getKey(), $startDate, $endDate);

        return new JsonResponse([
            'data' => $slots,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // SELF: Replace own availability (full upsert)
    public function replaceOwn(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof \App\Models\User) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $profile = PanditProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->getAuthIdentifier())
            ->first();

        if ($profile === null) {
            return new JsonResponse([
                'error' => ['code' => 'PANDIT_NOT_FOUND', 'message' => 'Pandit profile not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $validated = $request->validate([
            'slots' => ['required', 'array', 'min:0', 'max:50'],
            'slots.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'slots.*.start_time' => ['required', 'date_format:H:i:s'],
            'slots.*.end_time' => ['required', 'date_format:H:i:s', 'after:slots.*.start_time'],
            'slots.*.slot_duration_minutes' => ['sometimes', 'integer', 'min:15', 'max:480'],
            'slots.*.is_recurring' => ['sometimes', 'boolean'],
            'slots.*.specific_date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $slots = $validated['slots'];

        try {
            $slots = $this->availability->replaceAvailability((string) $profile->getKey(), $slots);
        } catch (AvailabilityConflictException $e) {
            return new JsonResponse([
                'error' => ['code' => 'PANDIT_AVAILABILITY_CONFLICT', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $this->audit->record(
            'pandit.availability.updated',
            ['module' => 'pandit', 'resource_type' => 'pandit_availability', 'resource_id' => (string) $profile->getKey()],
            null,
            ['user_id' => (string) $user->getAuthIdentifier(), 'slots_count' => count($slots)]
        );

        return new JsonResponse([
            'data' => $slots,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
