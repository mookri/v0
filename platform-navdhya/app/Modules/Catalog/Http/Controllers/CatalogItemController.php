<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Models\CatalogCategory;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Catalog\Outbox\CatalogFactAppender;
use App\Platform\Api\CursorPagination;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\PlatformScope;
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
    private readonly CatalogFactAppender $facts;

    public function __construct(?CatalogFactAppender $facts = null)
    {
        $this->facts = $facts ?? app(CatalogFactAppender::class);
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    /**
     * Public browse (B1): the marketing site's Quick Book lists ACTIVE
     * items for one ACTIVE tenant without a session (route opts out of
     * auth.any + tenant, IP-throttled). Scope bypass is the documented
     * kernel pattern — the tenant filter is explicit here, the field set
     * is minimal (no attributes / vendor internals), writes impossible.
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string', 'max:26'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $tenant = Tenant::query()->find($validated['tenant_id']);

        if ($tenant === null || $tenant->state !== Tenant::STATE_ACTIVE) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Tenant unavailable.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $items = PlatformScope::run(
            'public_catalog_browse',
            static function () use ($tenant, $validated, $request): array {
                $query = CatalogItem::query()
                    ->where('tenant_id', (string) $tenant->getKey())
                    ->where('status', CatalogItem::STATUS_ACTIVE);

                if (! empty($validated['category'])) {
                    $cat = CatalogCategory::query()
                        ->where(function ($q) use ($tenant) {
                            $q->where('tenant_id', (string) $tenant->getKey())
                                ->orWhereNull('tenant_id');
                        })
                        ->where(function ($q) use ($validated) {
                            $q->where('slug', $validated['category'])
                                ->orWhere('id', $validated['category']);
                        })
                        ->first();

                    if ($cat !== null) {
                        $categoryIds = CatalogCategory::query()
                            ->where('path', 'like', $cat->path . '%')
                            ->pluck('id')
                            ->push($cat->id)
                            ->unique()
                            ->all();

                        $query->whereIn('category_id', $categoryIds);
                    }
                }

                return $query->orderBy('name')
                    ->get()
                    ->map(static function (CatalogItem $item) use ($request): array {
                        $payload = [
                            'id' => (string) $item->getKey(),
                            'object' => 'catalog_item',
                            'sku' => $item->sku,
                            'name' => $item->name,
                            'description' => $item->description,
                            'currency' => $item->currency,
                            'price_minor' => $item->price_minor,
                        ];

                        if ($request->boolean('include_details') || $request->has('category')) {
                            $payload['slug'] = $item->slug;
                            $payload['item_kind'] = $item->item_kind;
                            $payload['category_id'] = $item->category_id;
                            $payload['base_duration_minutes'] = $item->base_duration_minutes;
                        }

                        return $payload;
                    })
                    ->values()
                    ->all();
            },
            tenantId: (string) $tenant->getKey(),
        );

        return new JsonResponse(
            [
                'data' => $items,
                'meta' => ['request_id' => $this->requestId($request)],
            ],
            200,
            [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'public, max-age=60',
            ]
        );
    }

    public function publicCategories(Request $request): JsonResponse
    {
        $tenantId = $request->query('tenant_id');
        $resolvedTenant = is_string($tenantId) && $tenantId !== '' ? $tenantId : null;

        $categories = PlatformScope::run(
            'public_catalog_categories',
            static function () use ($resolvedTenant) {
                $query = CatalogCategory::query()->where('status', CatalogCategory::STATUS_ACTIVE);
                if ($resolvedTenant !== null) {
                    $query->where(function ($q) use ($resolvedTenant): void {
                        $q->where('tenant_id', $resolvedTenant)->orWhereNull('tenant_id');
                    });
                }

                return $query->orderBy('sort')->get();
            },
            tenantId: $resolvedTenant,
        );
        $roots = $categories->whereNull('parent_id')->values()->map(function (CatalogCategory $root) use ($categories): array {
            return [
                'id' => $root->id,
                'name' => $root->name,
                'slug' => $root->slug,
                'kind' => $root->kind,
                'provenance' => $root->provenance,
                'depth' => $root->depth,
                'children' => $categories->where('parent_id', $root->id)->values()->map(fn (CatalogCategory $c): array => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'kind' => $c->kind,
                    'provenance' => $c->provenance,
                    'depth' => $c->depth,
                ])->all(),
            ];
        })->all();

        return new JsonResponse([
            'data' => $roots,
            'meta' => ['request_id' => $this->requestId($request)],
        ], 200, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'public, max-age=60',
        ]);
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

        $this->facts->append('catalog.item.created', $item->sku, [
            'sku' => $item->sku,
            'name' => $item->name,
            'status' => $item->status,
        ]);

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

        $this->facts->append('catalog.item.published', $item->sku, [
            'sku' => $item->sku,
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

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

        $this->facts->append('catalog.item.updated', $item->sku, [
            'sku' => $item->sku,
            'changes' => array_keys($validated),
        ]);

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

        $this->facts->append('catalog.item.archived', $item->sku, [
            'sku' => $item->sku,
            'status' => CatalogItem::STATUS_ARCHIVED,
        ]);

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
            'vendor_id' => $item->vendor_id,
        ];
    }
}
