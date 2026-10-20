<?php

namespace App\Platform\Api;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Cursor pagination for kernel API surfaces (doc 06 §3.3): cursor-only
 * (offset is banned), limit default 25 / max 100, opaque base64 cursor,
 * default ordering created_at DESC, id DESC.
 */
final class CursorPagination
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return array{0: list<TModel>, 1: array{limit: int, has_more: bool, next_cursor: string|null}}
     */
    public static function paginate(Builder $query, Request $request, int $defaultLimit = 25): array
    {
        $limitInput = $request->query('limit', $defaultLimit);
        $limit = is_numeric($limitInput) ? max(1, min(100, (int) $limitInput)) : $defaultLimit;

        $cursor = self::decodeCursor($request->query('cursor'));

        if ($cursor !== null) {
            $query->where(function (Builder $scoped) use ($cursor): void {
                /** @var Builder<Model> $scoped */
                $scoped->where('created_at', '<', $cursor['created_at'])
                    ->orWhere(function (Builder $tie) use ($cursor): void {
                        /** @var Builder<Model> $tie */
                        $tie->where('created_at', $cursor['created_at'])
                            ->where('id', '<', $cursor['id']);
                    });
            });
        }

        $rows = $query->orderByDesc('created_at')->orderByDesc('id')->take($limit + 1)->get();

        $hasMore = $rows->count() > $limit;
        $items = $rows->slice(0, $limit)->values();

        $nextCursor = null;

        if ($hasMore) {
            $last = $items->last();

            if ($last instanceof Model) {
                $nextCursor = self::encodeCursor($last);
            }
        }

        return [
            array_values($items->all()),
            ['limit' => $limit, 'has_more' => $hasMore, 'next_cursor' => $nextCursor],
        ];
    }

    /**
     * @return array{created_at: string, id: string}|null
     */
    private static function decodeCursor(mixed $cursor): ?array
    {
        if (! is_string($cursor) || $cursor === '') {
            return null;
        }

        $decoded = base64_decode($cursor, true);

        if ($decoded === false) {
            return null;
        }

        $payload = json_decode($decoded, true);

        if (! is_array($payload)
            || ! isset($payload['created_at'], $payload['id'])
            || ! is_string($payload['created_at'])
            || ! is_string($payload['id'])) {
            return null;
        }

        return ['created_at' => $payload['created_at'], 'id' => $payload['id']];
    }

    private static function encodeCursor(Model $model): string
    {
        $createdAt = $model->getAttribute('created_at');
        $value = $createdAt instanceof Carbon
            ? $createdAt->format('Y-m-d H:i:s')
            : (string) $createdAt;

        return base64_encode((string) json_encode(
            ['created_at' => $value, 'id' => (string) $model->getKey()],
            JSON_UNESCAPED_SLASHES,
        ));
    }
}
