<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Models\CatalogItem;
use App\Platform\Api\CursorPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Catalog item reads + lifecycle writes. Creates start DRAFT (publish is
 * an explicit verb, never a flag flip); SKU uniqueness is per tenant
 * (TEN-010); money stays minor-unit integers on the wire here (event
 * payloads) per api-platform §3.1 note.
 */
final class CatalogItemController
{
    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    public function index(Request $request): JsonResponse
    {
        $query = CatalogItem::query();
        if (is_string($request->query('status')) && $request->query('status') !== '') {
            $query->where('status', $request->query('status'));
        }

        [$items, $page] = CursorPagination::paginate($query, $request);

        return new JsonResponse([
            'data' => array_map(static fn (CatalogItem $item): array => self::serialize($item), iterator_to_array($items)),
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $item = CatalogItem::query()->find($id);

        if ($item === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Item not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return new JsonResponse([
            'data' => self::serialize($item),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string'],
            'currency' => ['nullable', 'string', 'size:3'],
            'price_minor' => ['nullable', 'integer', 'min:0'],
            'attributes' => ['nullable', 'array'],
        ]);

        if (CatalogItem::query()->where('sku', $validated['sku'])->exists()) {
            return new JsonResponse([
                'error' => ['code' => 'conflict', 'message' => 'SKU already exists in this tenant.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $item = new CatalogItem();
        $item->forceFill([
            'sku' => $validated['sku'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => CatalogItem::STATUS_DRAFT,
            'currency' => $validated['currency'] ?? 'INR',
            'price_minor' => $validated['price_minor'] ?? 0,
            'attributes' => $validated['attributes'] ?? null,
        ]);
        $item->save();

        return new JsonResponse([
            'data' => self::serialize($item),
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function publish(Request $request, string $id): JsonResponse
    {
        $item = CatalogItem::query()->find($id);

        if ($item === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Item not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        if ($item->status !== CatalogItem::STATUS_DRAFT) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Only drafts publish.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $item->forceFill(['status' => CatalogItem::STATUS_ACTIVE])->save();

        return new JsonResponse([
            'data' => self::serialize($item),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $item = CatalogItem::query()->find($id);

        if ($item === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Item not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        if ($item->status === CatalogItem::STATUS_ARCHIVED) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Archived items are immutable.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:190'],
            'description' => ['sometimes', 'nullable', 'string'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'price_minor' => ['sometimes', 'integer', 'min:0'],
            'attributes' => ['sometimes', 'nullable', 'array'],
        ]);

        $item->forceFill($validated)->save();

        return new JsonResponse([
            'data' => self::serialize($item),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function archive(Request $request, string $id): JsonResponse
    {
        $item = CatalogItem::query()->find($id);

        if ($item === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Item not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        if ($item->status === CatalogItem::STATUS_ARCHIVED) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Already archived.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $item->forceFill(['status' => CatalogItem::STATUS_ARCHIVED])->save();

        return new JsonResponse([
            'data' => self::serialize($item),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function serialize(CatalogItem $item): array
    {
        return [
            'id' => (string) $item->getKey(),
            'object' => 'catalog_item',
            'sku' => $item->sku,
            'name' => $item->name,
            'description' => $item->description,
            'status' => $item->status,
            'currency' => $item->currency,
            'price_minor' => $item->price_minor,
            'attributes' => $item->attributes,
        ];
    }
}
