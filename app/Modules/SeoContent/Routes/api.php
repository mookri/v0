<?php

use App\Modules\SeoContent\Http\Controllers\SeoPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SEO Content Public API (platform-global, no tenant)
|--------------------------------------------------------------------------
|
| All routes are public, anonymous, and throttled.
| Mounted by the registry at /api/v1/seo/*
|
*/

Route::prefix('seo')
    ->middleware(['throttle:60,1'])
    ->group(function () {
        Route::get('pages/{slug}', [SeoPageController::class, 'show'])->name('seo.pages.show');
        Route::get('registry', [SeoPageController::class, 'registry'])->name('seo.registry');
        Route::get('pages', [SeoPageController::class, 'index'])->name('seo.pages.index');
        Route::get('search-docs', [SeoPageController::class, 'searchDocs'])->name('seo.search-docs');
    });
