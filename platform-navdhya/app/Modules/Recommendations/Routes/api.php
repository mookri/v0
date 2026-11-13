<?php

use App\Modules\Recommendations\Http\Controllers\DiscoveryController;
use Illuminate\Support\Facades\Route;

Route::withoutMiddleware(['auth.any', 'tenant'])->group(function () {
    Route::middleware(['throttle:discovery-public'])->group(function () {
        Route::post('/', [DiscoveryController::class, 'feed'])->name('discovery.feed');
        Route::post('impressions', [DiscoveryController::class, 'impressions'])->name('discovery.impressions');
        Route::post('interactions', [DiscoveryController::class, 'interactions'])->name('discovery.interactions');
    });
});

