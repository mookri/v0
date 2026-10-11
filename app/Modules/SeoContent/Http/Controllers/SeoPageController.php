<?php

namespace App\Modules\SeoContent\Http\Controllers;

use App\Modules\SeoContent\Models\SeoPage;
use App\Modules\SeoContent\Models\SeoCluster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeoPageController
{
    /**
     * GET /api/v1/seo/pages/{slug}
     * Full page JSON (platform-global, no tenant).
     */
    public function show(string $slug): JsonResponse
    {
        $page = SeoPage::public()->where('slug', $slug)->first(['id', 'slug', 'cluster', 'noindex', 'title', 'meta_title', 'meta_description', 'last_updated', 'automation_generated_at', 'content_hash', 'page_json']);

        if (! $page) {
            return response()->json([
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Page not found'],
            ], 404);
        }

        return response()->json([
            'data' => $page->toArray(),
        ]);
    }

    /**
     * GET /api/v1/seo/registry
     * Clusters + slug index (replaces registry.json at runtime).
     */
    public function registry(): JsonResponse
    {
        $clusters = SeoCluster::all()->keyBy('cluster')->map(fn ($c) => [
            'label' => $c->label,
            'base_path' => $c->base_path,
        ])->toArray();

        $pages = SeoPage::public()
            ->get(['slug', 'cluster', 'noindex', 'automation_generated_at'])
            ->mapWithKeys(fn ($p) => [$p->slug => [
                'cluster' => $p->cluster,
                'noindex' => $p->noindex,
                'generated_at' => $p->automation_generated_at?->toIso8601String(),
            ]])
            ->toArray();

        return response()->json([
            'data' => [
                'clusters' => $clusters,
                'pages' => $pages,
            ],
        ]);
    }

    /**
     * GET /api/v1/seo/pages?cluster=
     * Lightweight list for sitemap/llms.txt.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SeoPage::public();

        if ($cluster = $request->query('cluster')) {
            $query->where('cluster', $cluster);
        }

        $pages = $query->get(['slug', 'cluster', 'title', 'meta_title', 'meta_description', 'last_updated', 'automation_generated_at']);

        return response()->json([
            'data' => $pages->toArray(),
        ]);
    }

    /**
     * GET /api/v1/seo/search-docs
     * Lite projection for BFF BM25 index (no body HTML).
     */
    public function searchDocs(): JsonResponse
    {
        $pages = SeoPage::public()->get([
            'slug',
            'cluster',
            'title',
            'meta_title',
            'meta_description',
            'primary_keyword' => DB::raw("JSON_UNQUOTE(JSON_EXTRACT(page_json, '$.primaryKeyword'))"),
            'keywords' => DB::raw("JSON_EXTRACT(page_json, '$.secondaryKeywords')"),
            'outline' => DB::raw("JSON_EXTRACT(page_json, '$.outline')"),
            'faq_questions' => DB::raw("JSON_EXTRACT(page_json, '$.faqs.question')"),
            'last_updated' => DB::raw("JSON_UNQUOTE(JSON_EXTRACT(page_json, '$.lastUpdated'))"),
        ]);

        return response()->json([
            'data' => $pages->toArray(),
        ]);
    }
}
