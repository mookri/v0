<?php

use App\Modules\Incident\Http\Controllers\IncidentController;
use App\Modules\Incident\Http\Controllers\ReplacementRecoveryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Incident Module API Routes (/api/v1/incident/*)
|--------------------------------------------------------------------------
*/
Route::get('health', static fn () => response()->json(['data' => ['module' => 'incident', 'status' => 'ok']]))
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->name('health');

Route::get('incidents', [IncidentController::class, 'index'])
    ->middleware('capability:incident:record:command')
    ->name('incidents.index');
Route::post('incidents', [IncidentController::class, 'store'])
    ->middleware('capability:incident:record:create')
    ->name('incidents.store');
Route::post('incidents/temple-denial', [IncidentController::class, 'templeDenial'])
    ->middleware('capability:incident:record:create')
    ->name('incidents.temple_denial');
Route::post('incidents/{id}/roles', [IncidentController::class, 'assignRole'])
    ->middleware('capability:incident:record:command')
    ->name('incidents.roles');
Route::post('incidents/{id}/severity', [IncidentController::class, 'changeSeverity'])
    ->middleware('capability:incident:record:command')
    ->name('incidents.severity');
Route::post('incidents/{id}/impact-outcome', [IncidentController::class, 'setOutcome'])
    ->middleware('capability:incident:record:command')
    ->name('incidents.impact_outcome');
Route::post('incidents/{id}/postmortem', [IncidentController::class, 'postmortem'])
    ->middleware('capability:incident:record:command')
    ->name('incidents.postmortem');
Route::post('incidents/{id}/transition', [IncidentController::class, 'transition'])
    ->middleware('capability:incident:record:close')
    ->name('incidents.transition');
Route::post('correlations/{correlationId}/decide', [IncidentController::class, 'decideCorrelation'])
    ->middleware('capability:incident:record:command')
    ->name('correlations.decide');

Route::post('emergency-unavailability', [ReplacementRecoveryController::class, 'reportEmergency'])
    ->name('recovery.emergency');
Route::post('incidents/{id}/replacements/propose', [ReplacementRecoveryController::class, 'propose'])
    ->middleware('capability:incident:recovery:recover')
    ->name('recovery.propose');
Route::post('replacements/{replacementId}/reserve', [ReplacementRecoveryController::class, 'reserve'])
    ->middleware('capability:incident:recovery:recover')
    ->name('recovery.reserve');
Route::post('replacements/{replacementId}/confirm', [ReplacementRecoveryController::class, 'confirm'])
    ->middleware('capability:incident:recovery:recover')
    ->name('recovery.confirm');

Route::post('restrictions', [IncidentController::class, 'imposeRestriction'])
    ->middleware(['capability:incident:restriction:approve', 'capability:support:safety_case:access'])
    ->name('restrictions.impose');
Route::post('restrictions/{restrictionId}/lift', [IncidentController::class, 'liftRestriction'])
    ->middleware(['capability:incident:restriction:approve', 'capability:support:safety_case:access'])
    ->name('restrictions.lift');

