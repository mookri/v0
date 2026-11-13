<?php

use App\Modules\Feedback\Http\Controllers\FeedbackController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Feedback Module API Routes (/api/v1/feedback/*)
|--------------------------------------------------------------------------
*/

Route::get('health', static fn () => response()->json(['data' => ['module' => 'feedback', 'status' => 'ok']]))
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->name('health');

Route::get('methodology', [FeedbackController::class, 'methodology'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->name('methodology');

Route::post('eligible/{bookingId}', [FeedbackController::class, 'checkEligibility'])
    ->name('eligible');
Route::post('submissions', [FeedbackController::class, 'store'])
    ->name('submissions.store');
Route::post('submissions/{id}/response', [FeedbackController::class, 'respond'])
    ->name('submissions.respond');
Route::post('submissions/{id}/appeal', [FeedbackController::class, 'appeal'])
    ->name('submissions.appeal');

Route::post('admin/submissions/{id}/moderate', [FeedbackController::class, 'moderate'])
    ->middleware('capability:feedback:publication:moderate')
    ->name('admin.moderate');
Route::post('admin/appeals/{appealId}/decide', [FeedbackController::class, 'decideAppeal'])
    ->middleware('capability:feedback:appeal:decide')
    ->name('admin.appeals.decide');

Route::get('providers/{subjectType}/{subjectId}/quality', [FeedbackController::class, 'providerQuality'])
    ->name('providers.quality');
Route::get('providers/{subjectType}/{subjectId}/reviews', [FeedbackController::class, 'providerReviews'])
    ->name('providers.reviews');
