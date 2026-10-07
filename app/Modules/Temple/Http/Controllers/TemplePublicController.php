<?php

namespace App\Modules\Temple\Http\Controllers;

use App\Modules\Temple\Contracts\ListsTemples;
use App\Modules\Temple\Contracts\ManagesTemplePujas;
use App\Modules\Temple\Models\Temple;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Temple public controller (public browse + search).
 */
final class TemplePublicController
{
    public function __construct(
        private readonly ListsTemples $profiles,
        private readonly ManagesTemplePujas $pujas,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    // PUBLIC: Search/list temples
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'city' => ['sometimes', 'string', 'max:100'],
            'state' => ['sometimes', 'string', 'max:100'],
            'amenities' => ['sometimes', 'array'],
            'amenities.*' => ['string'],
            'lat' => ['sometimes', 'numeric', 'between:-90,90'],
            'lng' => ['sometimes', 'numeric', 'between:-180,180'],
            'radius' => ['sometimes', 'numeric', 'min:1', 'max:500'],
            'q' => ['sometimes', 'string', 'max:100'],
            'sort' => ['sometimes', 'string', 'in:relevance,distance,rating,price'],
        ]);

        $filters = $request->only(['city', 'state', 'amenities', 'lat', 'lng', 'radius', 'q']);
        $limit = min((int) $request->query('limit', 20), 100);
        $cursor = is_string($request->query('cursor')) ? $request->query('cursor') : null;

        $result = $this->profiles->publicProfiles($filters, $limit, $cursor);

        return new JsonResponse([
            'data' => $result['items'],
            'meta' => ['request_id' => $this->requestId($request), 'page' => $result['page']],
        ]);
    }

    // PUBLIC: Show temple by slug or ID (fail-closed: VERIFIED + ACTIVE only)
    public function show(Request $request, string $identifier): JsonResponse
    {
        $profile = $this->profiles->profileById($identifier);

        // Absent, unverified and non-active temples collapse into the
        // same 404 (P11i) — no existence oracle before vetting.
        if ($profile === null
            || $profile['verification_status'] !== Temple::VERIFICATION_VERIFIED
            || $profile['status'] !== Temple::STATUS_ACTIVE) {
            return new JsonResponse([
                'error' => ['code' => 'TEMPLE_NOT_FOUND', 'message' => 'Temple not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return new JsonResponse([
            'data' => $profile,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // PUBLIC: Temple pujas (active assignments of a publicly visible temple)
    public function pujas(Request $request, string $templeId): JsonResponse
    {
        $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $limit = min((int) $request->query('limit', 20), 100);
        $cursor = is_string($request->query('cursor')) ? $request->query('cursor') : null;

        $result = $this->pujas->publicPujas($templeId, $limit, $cursor);

        if ($result === null) {
            // Absent, unverified and non-active temples collapse into the
            // same 404 (P11g) — no existence oracle before vetting.
            return new JsonResponse([
                'error' => ['code' => 'TEMPLE_NOT_FOUND', 'message' => 'Temple not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return new JsonResponse([
            'data' => $result['items'],
            'meta' => ['request_id' => $this->requestId($request), 'page' => $result['page']],
        ]);
    }

    // PUBLIC: Temple availability (weekly windows from active puja schedules)
    public function availability(Request $request, string $templeId): JsonResponse
    {
        $request->validate([
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $result = $this->pujas->publicAvailability(
            $templeId,
            is_string($startDate) ? $startDate : null,
            is_string($endDate) ? $endDate : null,
        );

        if ($result === null) {
            return new JsonResponse([
                'error' => ['code' => 'TEMPLE_NOT_FOUND', 'message' => 'Temple not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return new JsonResponse([
            'data' => $result['items'],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
