<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Modules\Booking\Contracts\RanksServices;
use App\Modules\Catalog\Contracts\ListsCatalogItems;
use App\Modules\Customers\Models\SavedItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Discover feed (B6): deterministic, SQL-only composition matching the
 * frontend DiscoverFeed shape inside the kernel envelope.
 *
 * - recent: newest ACTIVE catalog items (limit 10).
 * - popular: catalog items ranked by tenant booking volume, trailing
 *   30 days; falls back to recency when nothing was booked yet.
 * - recommended: popular SKUs minus the caller's booked SKUs and saved
 *   refs, backfilled from recent (v1 novelty heuristic — no ML, no
 *   Redis; documented in parity-customers.md).
 *
 * Reads cross modules only through Contracts ports (deptrac-blessed).
 */
final class CustomerDiscoverController extends CustomerSelfServiceController
{
    private const FEED_LIMIT = 10;

    private const POPULARITY_DAYS = 30;

    public function __construct(
        private readonly ListsCatalogItems $catalog,
        private readonly RanksServices $rankings,
    ) {
    }

    public function feed(Request $request): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        $userId = (string) $user->getAuthIdentifier();

        $recent = $this->catalog->activeItems(self::FEED_LIMIT);

        $popularSkus = $this->rankings->popularSkus(self::POPULARITY_DAYS, self::FEED_LIMIT);
        $popular = $popularSkus === []
            ? $recent
            : $this->catalog->itemsBySkus($popularSkus);

        $seen = $this->rankings->customerSkus($userId, 100);
        $savedIds = SavedItem::query()
            ->where('user_id', $userId)
            ->pluck('ref_id')
            ->all();

        $excludedSkus = array_flip($seen);
        $recommended = [];
        foreach ($popular as $item) {
            if (count($recommended) >= self::FEED_LIMIT) {
                break;
            }
            if (! isset($excludedSkus[$item['sku']]) && ! in_array($item['id'], $savedIds, true)) {
                $recommended[] = $item;
            }
        }
        foreach ($recent as $item) {
            if (count($recommended) >= self::FEED_LIMIT) {
                break;
            }
            $known = array_column($recommended, 'id');
            if (! isset($excludedSkus[$item['sku']])
                && ! in_array($item['id'], $savedIds, true)
                && ! in_array($item['id'], $known, true)) {
                $recommended[] = $item;
            }
        }

        $present = static fn (array $item): array => [
            'id' => $item['id'],
            'title' => $item['name'],
            'kind' => 'puja',
        ];

        return new JsonResponse([
            'data' => [
                'recommended' => array_map($present, $recommended),
                'popular' => array_map($present, $popular),
                'recent' => array_map($present, $recent),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
