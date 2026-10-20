<?php

namespace App\Http\Controllers\Ops;

use App\Modules\SeoContent\Models\SeoPage;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cross-module read-only roll-up for the console (W2): SEO coverage summary.
 * Capability `platform:ops:view` gates the read. SeoPage is central, so the
 * counts are platform-wide.
 */
final class SeoController
{
    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    public function index(Request $request): JsonResponse
    {
        $total = SeoPage::query()->count();
        $noindex = SeoPage::query()->where('noindex', true)->count();
        $clusters = SeoPage::query()->distinct()->count('cluster');

        return new JsonResponse([
            'data' => [
                'pages' => $total,
                'noindex' => $noindex,
                'indexed' => $total - $noindex,
                'clusters' => $clusters,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
