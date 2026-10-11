<?php

use App\Modules\Search\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Search Public API (platform-global + tenant-scoped)
|--------------------------------------------------------------------------
|
| All routes are anonymous, throttled, and mounted at /api/v1/search/*
|
*/

Route::prefix('search')
    ->middleware(['throttle:60,1'])
    ->group(function () {
        Route::get('/', [SearchController::class, 'search'])->name('search.search');
        Route::get('suggest', [SearchController::class, 'suggest'])->name('search.suggest');
        Route::get('index-docs', [SearchController::class, 'indexDocs'])->name('search.index-docs');
        Route::get('entities/{type}/{slug}', [SearchController::class, 'entity'])->name('search.entity');
        Route::post('analytics', [SearchController::class, 'analytics'])->name('search.analytics');
        Route::get('trending', [SearchController::class, 'trending'])->name('search.trending');
    });
