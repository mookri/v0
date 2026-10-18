<?php

use App\Modules\Search\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Search Public API (platform-global + tenant-scoped)
|--------------------------------------------------------------------------
|
| Public, anonymous. Each surface has its OWN named rate limiter (see
| ModuleServiceProvider) so a suggest storm cannot exhaust the search
| budget and vice versa. Mounted at /api/v1/search/*.
|
*/

// Privileged plane (auth.any + tenant inherited from module mount).
Route::middleware(['capability:search:privileged:view', 'throttle:search-privileged'])
    ->group(function () {
        Route::get('privileged', [\App\Modules\Search\Http\Controllers\PrivilegedSearchController::class, 'search'])
            ->name('search.privileged');
    });

Route::withoutMiddleware(['auth.any', 'tenant'])->group(function () {
    Route::middleware(['throttle:search-public'])->group(function () {
        Route::get('/', [SearchController::class, 'search'])->name('search.search');
        Route::get('index-docs', [SearchController::class, 'indexDocs'])->name('search.index-docs');
        Route::get('entities/{type}/{slug}', [SearchController::class, 'entity'])->name('search.entity');
        Route::get('trending', [SearchController::class, 'trending'])->name('search.trending');
    });

    Route::middleware(['throttle:search-suggest'])->group(function () {
        Route::get('suggest', [SearchController::class, 'suggest'])->name('search.suggest');
    });

    Route::middleware(['throttle:search-analytics'])->group(function () {
        Route::post('analytics', [SearchController::class, 'analytics'])->name('search.analytics');
    });
});