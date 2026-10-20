<?php

use App\Modules\SeoContent\Http\Controllers\SeoPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SEO Content Public API (platform-global, no tenant)
|--------------------------------------------------------------------------
|
| Public, anonymous, throttled, mounted at /api/v1/seo_content/seo/*.
| The web app's ISR pages read these endpoints on every render
| (SEO_CONTENT_SOURCE=api), so they must answer without a session and
| without a tenant header.
|
*/

Route::prefix('seo')
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->middleware(['throttle:60,1'])
    ->group(function () {
        Route::get('pages/{slug}', [SeoPageController::class, 'show'])->name('seo.pages.show');
        Route::get('registry', [SeoPageController::class, 'registry'])->name('seo.registry');
        Route::get('pages', [SeoPageController::class, 'index'])->name('seo.pages.index');
        Route::get('search-docs', [SeoPageController::class, 'searchDocs'])->name('seo.search-docs');
    });
