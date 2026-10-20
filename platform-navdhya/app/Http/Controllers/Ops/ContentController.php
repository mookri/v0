<?php

namespace App\Http\Controllers\Ops;

use App\Modules\SeoContent\Models\SeoPage;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cross-module read-only roll-up for the console (W2): the editorial/content
 * queue. SeoPage is a central (global) table, so this surface reports the
 * most recently updated pages regardless of tenant — the console is the
 * platform-wide editorial view. Capability `platform:ops:view` gates the read.
 */
final class ContentController
{
    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    public function index(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->query('limit', 50), 1), 200);

        $rows = SeoPage::query()
            ->orderByDesc('last_updated')
            ->limit($limit)
            ->get();

        return new JsonResponse([
            'data' => $rows->map(static function (SeoPage $page): array {
                return [
                    'id' => (string) $page->getKey(),
                    'object' => 'seo_page',
                    'slug' => $page->slug,
                    'cluster' => $page->cluster,
                    'title' => $page->title,
                    'noindex' => (bool) $page->noindex,
                    'last_updated' => $page->last_updated,
                ];
            })->values()->all(),
            'meta' => ['request_id' => $this->requestId($request), 'limit' => $limit],
        ]);
    }
}
