<?php

use App\Modules\Onboarding\Http\Controllers\ApplicationController;
use App\Modules\Onboarding\Http\Controllers\DraftController;
use App\Modules\Onboarding\Http\Controllers\ReviewController;
use App\Modules\Onboarding\Http\Controllers\SubmitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Onboarding Module API Routes (mounted by the registry at
| /api/v1/onboarding/*). Tenant resolution + auth apply; step/submit/
| review/decision surfaces land next with the capability middleware.
*/

Route::post('applications', [ApplicationController::class, 'start'])->name('applications.start');
Route::get('applications/{id}', [ApplicationController::class, 'show'])->name('applications.show');
Route::post('applications/{id}/submit', [SubmitController::class, 'begin'])->name('applications.submit');
Route::post('applications/{id}/submit/resend', [SubmitController::class, 'resend'])->name('applications.submit.resend');
Route::post('applications/{id}/submit/confirm', [SubmitController::class, 'confirm'])->name('applications.confirm');
Route::get('review/queue', [ReviewController::class, 'queue'])
    ->middleware('capability:onboarding:review:view')
    ->name('review.queue');
Route::post('review/{id}:approve', [ReviewController::class, 'approve'])
    ->middleware(['capability:onboarding:review:approve', 'stepup:2'])
    ->name('review.approve');
Route::post('review/{id}:reject', [ReviewController::class, 'reject'])
    ->middleware(['capability:onboarding:review:reject', 'stepup:2'])
    ->name('review.reject');
// request-info is a WRITE (it moves the application back to DRAFT,
// overwrites reviewed_at/reviewer_id and writes a review record), so it
// is gated on a write capability and the same step-up as its sibling
// decision verbs. It used to be gated on the READ capability
// `onboarding:review:view`, which let any queue viewer mutate every
// application in the tenant.
Route::post('review/{id}:request-info', [ReviewController::class, 'requestInfo'])
    ->middleware(['capability:onboarding:review:approve', 'stepup:2'])
    ->name('review.request-info');
Route::put('applications/{id}/step/{step}', [DraftController::class, 'putStep'])->name('applications.step');
Route::get('applications/{id}/status', [DraftController::class, 'status'])->name('applications.status');
Route::post('applications/{id}/documents', [DraftController::class, 'uploadDocument'])->name('applications.documents');
