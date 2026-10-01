<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Modules\Customers\Models\SavedItem;
use App\Platform\Api\CursorPagination;
use App\Platform\Audit\AuditWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Saved-item bookmarks (B4): cursor list, shape-validated adds
 * (ref_type + ULID ref_id; existence is resolved by readers, never
 * joined here), idempotent-safe deletes. Duplicates are 409, malformed
 * refs 422, absent rows 404.
 */
final class SavedItemController extends CustomerSelfServiceController
{
    public function __construct(private readonly AuditWriter $audit)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        [$items, $page] = CursorPagination::paginate(
            SavedItem::query()->where('user_id', $user->getAuthIdentifier()),
            $request
        );

        return new JsonResponse([
            'data' => array_map(
                static fn (SavedItem $item): array => self::serialize($item),
                $items
            ),
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        $userId = (string) $user->getAuthIdentifier();

        $validated = $request->validate([
            'ref_type' => ['required', 'string', 'in:puja,content,service'],
            'ref_id' => ['required', 'string', 'max:64'],
        ]);

        // ULID-shaped references only: opaque bookmarks still need a
        // well-formed key so readers can resolve them deterministically.
        if (! preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/i', $validated['ref_id'])) {
            return new JsonResponse([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => 'Unknown reference.',
                    'details' => ['fields' => ['ref_id' => ['malformed_reference']]],
                    'request_id' => $this->requestId($request),
                ],
            ], 422);
        }

        $refId = strtoupper($validated['ref_id']);

        $existing = SavedItem::query()
            ->where('user_id', $userId)
            ->where('ref_type', $validated['ref_type'])
            ->where('ref_id', $refId)
            ->first();

        if ($existing !== null) {
            return new JsonResponse([
                'error' => ['code' => 'conflict', 'message' => 'Already saved.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $item = new SavedItem();
        $item->forceFill([
            'user_id' => $userId,
            'ref_type' => $validated['ref_type'],
            'ref_id' => $refId,
        ]);
        $item->save();

        $this->audit->record(
            'customers.saved.added',
            ['module' => 'customers', 'resource_type' => 'saved_item', 'resource_id' => (string) $item->getKey()],
            null,
            ['user_id' => $userId, 'ref_type' => $item->ref_type, 'ref_id' => $item->ref_id]
        );

        return new JsonResponse([
            'data' => self::serialize($item),
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $item = SavedItem::query()
            ->where('id', $id)
            ->where('user_id', (string) $user->getAuthIdentifier())
            ->first();

        if ($item === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Saved item not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $item->delete();

        $this->audit->record(
            'customers.saved.removed',
            ['module' => 'customers', 'resource_type' => 'saved_item', 'resource_id' => $id],
            null,
            ['user_id' => (string) $user->getAuthIdentifier()]
        );

        return new JsonResponse([
            'data' => ['object' => 'saved_item_deletion', 'deleted' => true],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function serialize(SavedItem $item): array
    {
        return [
            'id' => (string) $item->getKey(),
            'object' => 'saved_item',
            'ref_type' => $item->ref_type,
            'ref_id' => $item->ref_id,
            'saved_at' => $item->created_at,
        ];
    }
}
