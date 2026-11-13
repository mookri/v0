<?php

use App\Modules\SeoContent\Http\Controllers\SeoPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SEO Content Public API (platform-global, no tenant)
|--------------------------------------------------------------------------
|
| Public, anonymous, throttled by named limiters, mounted at /api/v1/seo_content/seo/*.
| High volume public page hits use 'seo-public' (120/min) and do NOT throttle
| the internal ISR registry or search-docs endpoints ('seo-registry' at 600/min) (closes P-15).
|
*/

Route::prefix('seo')
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->group(function () {
        Route::get('pages/{slug}', [SeoPageController::class, 'show'])
            ->middleware('throttle:seo-public')
            ->name('seo.pages.show');

        Route::get('registry', [SeoPageController::class, 'registry'])
            ->middleware('throttle:seo-registry')
            ->name('seo.registry');

        Route::get('pages', [SeoPageController::class, 'index'])
            ->middleware('throttle:seo-public')
            ->name('seo.pages.index');

        Route::get('search-docs', [SeoPageController::class, 'searchDocs'])
            ->middleware('throttle:seo-registry')
            ->name('seo.search-docs');
    });
