<?php

namespace App\Modules\Temple\Http\Controllers;

use App\Modules\Temple\Contracts\ManagesTempleManagers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Temple manager controller (partner self-serve).
 */
final class TempleManagerController
{
    public function __construct(private readonly ManagesTempleManagers $managers)
    {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    // PARTNER: List managers for a temple
    public function index(Request $request, string $templeId): JsonResponse
    {
        $managers = $this->managers->listManagers($templeId);

        return new JsonResponse([
            'data' => $managers,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // PARTNER: Invite a manager
    public function store(Request $request, string $templeId): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'string'],
            'role' => ['required', 'string', 'in:admin,manager,viewer'],
        ]);

        try {
            $manager = $this->managers->addManager($templeId, $validated);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 422);
        }

        return new JsonResponse([
            'data' => $manager,
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    // PARTNER: Update manager role
    public function update(Request $request, string $templeId, string $managerId): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:admin,manager,viewer'],
        ]);

        try {
            $manager = $this->managers->updateManagerRole($templeId, $managerId, $validated['role']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return new JsonResponse([
                'error' => ['code' => 'TEMPLE_NOT_FOUND', 'message' => 'Temple not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 422);
        }

        return new JsonResponse([
            'data' => $manager,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // PARTNER: Remove manager
    public function destroy(Request $request, string $templeId, string $managerId): JsonResponse
    {
        try {
            $this->managers->removeManager($templeId, $managerId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return new JsonResponse([
                'error' => ['code' => 'TEMPLE_NOT_FOUND', 'message' => 'Temple not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return new JsonResponse([
            'data' => ['deleted' => true],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
