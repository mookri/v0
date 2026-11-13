<?php

use App\Modules\Jyotish\Http\Controllers\BirthProfileController;
use App\Modules\Jyotish\Http\Controllers\ChartController;
use App\Modules\Jyotish\Http\JyotishProblemResponder;
use App\Modules\Jyotish\Http\Middleware\EnsureJyotishFeatureEnabled;
use App\Modules\Jyotish\Http\Presenters\JyotishResultEnvelope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Jyotish Module API Routes
|--------------------------------------------------------------------------
|
| Mounted by ModuleRegistry at /api/v1/jyotish with default ['auth.any', 'tenant']
| middleware. Fail-closed feature flag and capability gates applied per endpoint.
|
*/

Route::middleware([EnsureJyotishFeatureEnabled::class])->group(function () {
    Route::get('health', static function () {
        return response()->json([
            'status' => 'operational',
            'module' => 'jyotish',
            'version' => '0.1.0',
        ]);
    })->name('health');

    Route::middleware(['capability:jyotish:chart:read'])->group(function () {
        Route::get('birth-profiles', [BirthProfileController::class, 'index'])->name('birth_profiles.index');
        Route::get('birth-profiles/{id}', [BirthProfileController::class, 'show'])->name('birth_profiles.show');

        Route::get('charts/{id}', [ChartController::class, 'show'])->name('charts.show');
    });

    Route::middleware(['capability:jyotish:chart:write'])->group(function () {
        Route::post('birth-profiles', [BirthProfileController::class, 'store'])->name('birth_profiles.store');
        Route::post('charts/d1', [ChartController::class, 'calculateD1'])->name('charts.d1');

        Route::post('charts', [ChartController::class, 'calculateD1'])->name('charts.store');
    });

    Route::middleware(['capability:jyotish:calculation:execute'])->group(function () {
        Route::post('calculate', static function (Request $request) {
            return response()->json(
                JyotishResultEnvelope::wrap([
                    'calculation_status' => 'computed',
                ], 'calculation_output_v1')
            );
        })->name('calculate');
    });

    Route::middleware(['capability:jyotish:ruleset:manage'])->group(function () {
        Route::post('rulesets', static function (Request $request) {
            return response()->json(
                JyotishResultEnvelope::wrap([
                    'ruleset_code' => $request->input('ruleset_code', 'parashara_standard'),
                    'version' => '1.0.0',
                ], 'ruleset_created_v1'),
                201
            );
        })->name('rulesets.store');
    });
});
