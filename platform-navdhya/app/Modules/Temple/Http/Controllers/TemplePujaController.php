<?php

namespace App\Modules\Temple\Http\Controllers;

use App\Modules\Temple\Contracts\ManagesTemplePujas;
use App\Modules\Temple\TempleNotFoundException;
use App\Modules\Temple\TemplePujaNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Temple puja controller (partner self-serve).
 */
final class TemplePujaController
{
    public function __construct(private readonly ManagesTemplePujas $pujas)
    {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    // PARTNER: List pujas for a temple
    public function index(Request $request, string $templeId): JsonResponse
    {
        $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string'],
        ]);

        $filters = $request->only(['status']);
        $limit = min((int) $request->query('limit', 20), 100);
        $cursor = is_string($request->query('cursor')) ? $request->query('cursor') : null;

        try {
            $result = $this->pujas->listPujas($templeId, $filters, $limit, $cursor);
        } catch (TempleNotFoundException) {
            return new JsonResponse([
                'error' => ['code' => 'TEMPLE_NOT_FOUND', 'message' => 'Temple not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return new JsonResponse([
            'data' => $result['items'],
            'meta' => ['request_id' => $this->requestId($request), 'page' => $result['page']],
        ]);
    }

    // PARTNER: Assign puja to temple
    public function store(Request $request, string $templeId): JsonResponse
    {
        $validated = $request->validate([
            'puja_id' => ['required', 'string'],
            'price_override_minor' => ['nullable', 'integer', 'min:0'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'schedule_json' => ['nullable', 'string'],
        ]);

        try {
            $puja = $this->pujas->assignPuja($templeId, $validated);
        } catch (TempleNotFoundException) {
            return new JsonResponse([
                'error' => ['code' => 'TEMPLE_NOT_FOUND', 'message' => 'Temple not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 422);
        }

        return new JsonResponse([
            'data' => $puja,
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    // PARTNER: Show puja assignment
    public function show(Request $request, string $templeId, string $pujaId): JsonResponse
    {
        try {
            $puja = $this->pujas->showPuja($templeId, $pujaId);
        } catch (TempleNotFoundException|TemplePujaNotFoundException) {
            return new JsonResponse([
                'error' => ['code' => 'TEMPLE_PUJA_NOT_FOUND', 'message' => 'Puja assignment not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return new JsonResponse([
            'data' => $puja,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // PARTNER: Update puja assignment
    public function update(Request $request, string $templeId, string $pujaId): JsonResponse
    {
        $validated = $request->validate([
            'price_override_minor' => ['sometimes', 'integer', 'min:0'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'schedule_json' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string', 'in:ACTIVE,INACTIVE'],
        ]);

        try {
            $puja = $this->pujas->updatePuja($templeId, $pujaId, $validated);
        } catch (TempleNotFoundException|TemplePujaNotFoundException) {
            return new JsonResponse([
                'error' => ['code' => 'TEMPLE_PUJA_NOT_FOUND', 'message' => 'Puja assignment not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return new JsonResponse([
            'data' => $puja,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // PARTNER: Remove puja from temple
    public function destroy(Request $request, string $templeId, string $pujaId): JsonResponse
    {
        try {
            $this->pujas->removePuja($templeId, $pujaId);
        } catch (TempleNotFoundException|TemplePujaNotFoundException) {
            return new JsonResponse([
                'error' => ['code' => 'TEMPLE_PUJA_NOT_FOUND', 'message' => 'Puja assignment not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return new JsonResponse([
            'data' => ['deleted' => true],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
