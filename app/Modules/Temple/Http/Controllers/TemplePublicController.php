<?php

namespace App\Modules\Temple\Http\Controllers;

use App\Modules\Temple\Contracts\ListsTemples;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Temple public controller (public browse + search).
 */
final class TemplePublicController
{
    public function __construct(private readonly ListsTemples $profiles)
    {
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

        $result = $this->profiles->publicProfiles($filters, $limit);

        return new JsonResponse([
            'data' => $result['items'],
            'meta' => ['request_id' => $this->requestId($request), 'page' => $result['page']],
        ]);
    }

    // PUBLIC: Show temple by slug or ID
    public function show(Request $request, string $identifier): JsonResponse
    {
        $profile = $this->profiles->profileById($identifier);

        if ($profile === null) {
            return new JsonResponse([
                'error' => ['code' => 'TEMPLE_NOT_FOUND', 'message' => 'Temple not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return new JsonResponse([
            'data' => $profile,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // PUBLIC: Temple pujas
    public function pujas(Request $request, string $templeId): JsonResponse
    {
        // This would need a contract method - for now returning not implemented
        return new JsonResponse([
            'error' => ['code' => 'not_implemented', 'message' => 'Temple pujas endpoint not yet implemented.', 'request_id' => $this->requestId($request)],
        ], 501);
    }

    // PUBLIC: Temple availability
    public function availability(Request $request, string $templeId): JsonResponse
    {
        // This would need a contract method
        return new JsonResponse([
            'error' => ['code' => 'not_implemented', 'message' => 'Temple availability endpoint not yet implemented.', 'request_id' => $this->requestId($request)],
        ], 501);
    }
}
