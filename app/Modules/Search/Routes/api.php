<?php

use App\Modules\Search\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Search Public API (platform-global + tenant-scoped)
|--------------------------------------------------------------------------
|
| Public, anonymous, throttled, mounted at /api/v1/search/*. The module
| mount already contributes the `search` segment, so no inner prefix.
| The web BFF's federated search and autocomplete call these on every
| keystroke without a session; analytics is a public ingestion sink
| (sanitised + batched).
|
*/

Route::middleware(['throttle:60,1'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->group(function () {
        Route::get('/', [SearchController::class, 'search'])->name('search.search');
        Route::get('suggest', [SearchController::class, 'suggest'])->name('search.suggest');
        Route::get('index-docs', [SearchController::class, 'indexDocs'])->name('search.index-docs');
        Route::get('entities/{type}/{slug}', [SearchController::class, 'entity'])->name('search.entity');
        Route::post('analytics', [SearchController::class, 'analytics'])->name('search.analytics');
        Route::get('trending', [SearchController::class, 'trending'])->name('search.trending');
    });
