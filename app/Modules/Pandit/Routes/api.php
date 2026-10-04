<?php

use App\Modules\Pandit\Http\Controllers\PanditApplicationController;
use App\Modules\Pandit\Http\Controllers\PanditAssignmentController;
use App\Modules\Pandit\Http\Controllers\PanditAvailabilityController;
use App\Modules\Pandit\Http\Controllers\PanditEarningsController;
use App\Modules\Pandit\Http\Controllers\PanditProfileController;
use App\Modules\Pandit\Http\Controllers\PanditReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pandit Module API Routes (mounted by the registry at /api/v1/pandit/*
| with auth.any + tenant; routes are never loaded by the provider).
|
| Browse = the directory: capability-free, still authenticated (the
|          kernel binds a tenant only for a principal) + verified rows.
| Self    = ownership is the actor (customers-module posture).
| Admin   = capability-gated, sensitive verbs additionally step-up.
|--------------------------------------------------------------------------
*/

// Browse directory: open to any tenant principal (no capability, unlike the
// admin directory) but never anonymous — ResolveTenantContext is fail-closed
// and refuses to bind a tenant without an authenticated principal, so the
// registry's auth.any + tenant stay on. Machine keys need `pandit:read`.
Route::get('profiles', [PanditProfileController::class, 'index'])
    ->middleware(['scope:pandit:read', 'throttle:60,1'])
    ->name('profiles.index');
Route::get('profiles/{id}', [PanditProfileController::class, 'show'])
    ->middleware(['scope:pandit:read', 'throttle:60,1'])
    ->name('profiles.show');
Route::get('profiles/{id}/availability', [PanditAvailabilityController::class, 'publicIndex'])
    ->middleware(['scope:pandit:read', 'throttle:60,1'])
    ->name('profiles.availability');

// Pandit self-serve: auth.any + tenant (registry default) + machine scope.
Route::middleware(['scope:pandit:self'])->group(function () {
    Route::post('apply', [PanditApplicationController::class, 'store'])
        ->name('apply');
    Route::get('application', [PanditApplicationController::class, 'own'])
        ->name('application.own');
    Route::get('profile', [PanditProfileController::class, 'own'])
        ->name('profile.own');
    Route::patch('profile', [PanditProfileController::class, 'updateOwn'])
        ->name('profile.update-own');
    Route::get('availability', [PanditAvailabilityController::class, 'ownIndex'])
        ->name('availability.own-index');
    Route::put('availability', [PanditAvailabilityController::class, 'replaceOwn'])
        ->name('availability.replace-own');
    Route::get('assignments', [PanditAssignmentController::class, 'ownIndex'])
        ->name('assignments.own-index');
    Route::post('assignments/{id}:confirm', [PanditAssignmentController::class, 'confirm'])
        ->name('assignments.confirm');
    Route::post('assignments/{id}:decline', [PanditAssignmentController::class, 'decline'])
        ->name('assignments.decline');
    Route::get('earnings', [PanditEarningsController::class, 'own'])
        ->name('earnings.own');
    Route::get('reviews', [PanditReviewController::class, 'ownIndex'])
        ->name('reviews.own-index');
});

// Admin surface. The application lifecycle (steps, OTP submit, review
// queue) lives on /api/v1/onboarding — this module never re-decides it.
Route::middleware(['scope:pandit:admin'])->group(function () {
    Route::get('admin/profiles', [PanditProfileController::class, 'adminIndex'])
        ->middleware('capability:pandit:profile:view')
        ->name('admin.profiles.index');
    Route::post('admin/profiles/{id}:verify', [PanditProfileController::class, 'verify'])
        ->middleware(['capability:pandit:profile:manage', 'stepup:2'])
        ->name('admin.profiles.verify');
    Route::post('admin/profiles/{id}:suspend', [PanditProfileController::class, 'suspend'])
        ->middleware(['capability:pandit:profile:manage', 'stepup:2'])
        ->name('admin.profiles.suspend');
    Route::post('admin/profiles/{id}:reactivate', [PanditProfileController::class, 'reactivate'])
        ->middleware(['capability:pandit:profile:manage', 'stepup:2'])
        ->name('admin.profiles.reactivate');
    Route::post('admin/assignments', [PanditAssignmentController::class, 'adminStore'])
        ->middleware('capability:pandit:assignment:manage')
        ->name('admin.assignments.store');
    Route::delete('admin/assignments/{id}', [PanditAssignmentController::class, 'adminDestroy'])
        ->middleware('capability:pandit:assignment:manage')
        ->name('admin.assignments.destroy');
});
