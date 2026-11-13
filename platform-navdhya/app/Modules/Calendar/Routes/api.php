<?php

use App\Modules\Calendar\Http\Controllers\PanchangController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Calendar Module API Routes
|--------------------------------------------------------------------------
|
| Mounted by ModuleRegistry at /api/v1/calendar with default ['auth.any', 'tenant']
| middleware.
|
*/

Route::get('health', static function () {
    return response()->json([
        'status' => 'operational',
        'module' => 'calendar',
        'version' => '0.1.0',
    ]);
})->name('health');

Route::get('panchang', [PanchangController::class, 'daily'])->name('panchang.daily');
Route::get('panchang/monthly', [PanchangController::class, 'monthly'])->name('panchang.monthly');
