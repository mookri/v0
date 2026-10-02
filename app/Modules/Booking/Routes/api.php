<?php

use App\Modules\Booking\Http\Controllers\BookingReviewController;
use App\Modules\Booking\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Booking Module API Routes (mounted by the registry at
| /api/v1/booking/* with auth.any + tenant, ADR-005). Reservations are
| caller-scoped (ownership gates, never capabilities); creation endpoints
| land with the /book handoff slice.
*/

Route::get('reservations', [ReservationController::class, 'index'])
    ->middleware('scope:booking:read')
    ->name('reservations.index');
Route::get('reservations/{id}', [ReservationController::class, 'show'])
    ->middleware('scope:booking:read')
    ->name('reservations.show');
Route::post('reservations/{id}:cancel', [ReservationController::class, 'cancel'])
    ->name('reservations.cancel');
Route::post('reservations/{id}:reschedule', [ReservationController::class, 'reschedule'])
    ->name('reservations.reschedule');
Route::get('reservations/{id}/history', [ReservationController::class, 'history'])
    ->middleware('scope:booking:read')
    ->name('reservations.history');

Route::get('reviews', [BookingReviewController::class, 'index'])
    ->name('reviews.index');
Route::post('reservations/{id}/reviews', [BookingReviewController::class, 'store'])
    ->name('reviews.store');
Route::delete('reviews/{id}', [BookingReviewController::class, 'destroy'])
    ->name('reviews.destroy');
