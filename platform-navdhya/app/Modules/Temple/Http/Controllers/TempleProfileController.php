<?php

namespace App\Modules\Temple\Http\Controllers;

use App\Modules\Temple\Contracts\ListsTemples;
use App\Modules\Temple\Contracts\ManagesTemples;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Temple profile controller (partner self-serve + admin). The public
 * browse/search twin lives in TemplePublicController — this class never
 * had routes for a public index/show (removed as dead code, S12).
 */
final class TempleProfileController
{
    public function __construct(
        private readonly ListsTemples $profiles,
        private readonly ManagesTemples $management,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    // PARTNER: List owned/managed temples
    public function partnerIndex(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string'],
        ]);

        $identityId = (string) $request->user()?->getAuthIdentifier();
        $filters = $request->only(['status']);
        $limit = min((int) $request->query('limit', 20), 100);
        $cursor = is_string($request->query('cursor')) ? $request->query('cursor') : null;

        $result = $this->profiles->partnerProfiles($identityId, $filters, $limit, $cursor);

        return new JsonResponse([
            'data' => $result['items'],
            'meta' => ['request_id' => $this->requestId($request), 'page' => $result['page']],
        ]);
    }

    // PARTNER: Show own temple detail
    public function partnerShow(Request $request, string $id): JsonResponse
    {
        $profile = $this->management->partnerProfile($id);

        if ($profile === null) {
            return new JsonResponse([
                'error' => ['code' => 'TEMPLE_NOT_FOUND', 'message' => 'Temple not found or access denied.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return new JsonResponse([
            'data' => $profile,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // PARTNER: Create temple
    public function partnerStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:100', 'unique:temples,slug'],
            'name' => ['required', 'string', 'max:255'],
            'trust_name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:individual,trust'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string', 'url'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $temple = $this->management->create($validated);

        return new JsonResponse([
            'data' => $temple,
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    // PARTNER: Update own temple
    public function partnerUpdate(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'trust_name' => ['sometimes', 'string', 'max:255'],
            'address' => ['sometimes', 'string', 'max:500'],
            'city' => ['sometimes', 'string', 'max:100'],
            'state' => ['sometimes', 'string', 'max:100'],
            'lat' => ['sometimes', 'numeric', 'between:-90,90'],
            'lng' => ['sometimes', 'numeric', 'between:-180,180'],
            'images' => ['sometimes', 'array'],
            'images.*' => ['string', 'url'],
            'amenities' => ['sometimes', 'array'],
            'amenities.*' => ['string'],
            'description' => ['sometimes', 'string', 'max:2000'],
        ]);

        try {
            $temple = $this->management->update($id, $validated);
        } catch (\App\Modules\Temple\TempleNotFoundException) {
            return new JsonResponse([
                'error' => ['code' => 'TEMPLE_NOT_FOUND', 'message' => 'Temple not found or access denied.', 'request_id' => $this->requestId($request)],
            ], 404);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 422);
        }

        return new JsonResponse([
            'data' => $temple,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // ADMIN: List all temples
    public function adminIndex(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string'],
            'verification_status' => ['sometimes', 'string'],
            'search' => ['sometimes', 'string', 'max:190'],
        ]);

        $filters = $request->only(['status', 'verification_status', 'search']);
        $limit = min((int) $request->query('limit', 20), 100);
        $cursor = is_string($request->query('cursor')) ? $request->query('cursor') : null;

        $result = $this->profiles->adminProfiles($filters, $limit, $cursor);

        return new JsonResponse([
            'data' => $result['items'],
            'meta' => ['request_id' => $this->requestId($request), 'page' => $result['page']],
        ]);
    }

    // ADMIN: Verify temple
    public function verify(Request $request, string $id): JsonResponse
    {
        try {
            $temple = $this->management->verify($id);
        } catch (\App\Modules\Temple\TempleNotFoundException) {
            return new JsonResponse([
                'error' => ['code' => 'TEMPLE_NOT_FOUND', 'message' => 'Temple not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 409);
        }

        return new JsonResponse([
            'data' => $temple,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // ADMIN: Explicitly publish a verified temple
    public function publish(Request $request, string $id): JsonResponse
    {
        try {
            $temple = $this->management->publish($id);
        } catch (\App\Modules\Temple\TempleNotFoundException|\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return new JsonResponse([
                'error' => ['code' => 'TEMPLE_NOT_FOUND', 'message' => 'Temple not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 409);
        }

        return new JsonResponse([
            'data' => $temple,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // ADMIN: Suspend temple
    public function suspend(Request $request, string $id): JsonResponse
    {
        try {
            $temple = $this->management->suspend($id);
        } catch (\App\Modules\Temple\TempleNotFoundException) {
            return new JsonResponse([
                'error' => ['code' => 'TEMPLE_NOT_FOUND', 'message' => 'Temple not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 409);
        }

        return new JsonResponse([
            'data' => $temple,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // ADMIN: Reactivate temple
    public function reactivate(Request $request, string $id): JsonResponse
    {
        try {
            $temple = $this->management->reactivate($id);
        } catch (\App\Modules\Temple\TempleNotFoundException) {
            return new JsonResponse([
                'error' => ['code' => 'TEMPLE_NOT_FOUND', 'message' => 'Temple not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 409);
        }

        return new JsonResponse([
            'data' => $temple,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
