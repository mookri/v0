<?php

use App\Modules\Booking\Http\Controllers\BookingArrivalController;
use App\Modules\Booking\Http\Controllers\BookingReviewController;
use App\Modules\Booking\Http\Controllers\InstantSearchController;
use App\Modules\Booking\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Booking Module API Routes (mounted by the registry at
| /api/v1/booking/* with auth.any + tenant, ADR-005). Reservations are
| caller-scoped (ownership gates, never capabilities); creation endpoint
| is DRAFT-only (no payment), idempotent on Idempotency-Key. :confirm
| begins checkout (B3) — throttled like creation (money-moving write).
|--------------------------------------------------------------------------
*/

Route::post('reservations', [ReservationController::class, 'store'])
    ->middleware(['throttle.tier'])
    ->name('reservations.store');

Route::get('reservations', [ReservationController::class, 'index'])
    ->middleware(['scope:booking:read', 'throttle.tier'])
    ->name('reservations.index');

// B2 instant-book search: date + slot (+ optional puja) → assignable
// pandits + temple slots. Directory read, not caller data — human and
// scoped machine principals alike (scope gates machines only).
Route::get('instant-search', [InstantSearchController::class, 'index'])
    ->middleware(['scope:booking:read', 'throttle:60,1'])
    ->name('instant-search');
Route::get('reservations/{id}', [ReservationController::class, 'show'])
    ->middleware(['scope:booking:read', 'throttle.tier'])
    ->name('reservations.show');
Route::post('reservations/{id}:cancel', [ReservationController::class, 'cancel'])
    ->name('reservations.cancel');
Route::post('reservations/{id}:confirm', [ReservationController::class, 'confirm'])
    ->middleware(['throttle.tier'])
    ->name('reservations.confirm');
Route::post('reservations/{id}:reschedule', [ReservationController::class, 'reschedule'])
    ->name('reservations.reschedule');
Route::get('reservations/{id}/history', [ReservationController::class, 'history'])
    ->middleware(['scope:booking:read', 'throttle.tier'])
    ->name('reservations.history');

Route::get('reviews', [BookingReviewController::class, 'index'])
    ->name('reviews.index');
Route::post('reservations/{id}/reviews', [BookingReviewController::class, 'store'])
    ->name('reviews.store');
Route::delete('reviews/{id}', [BookingReviewController::class, 'destroy'])
    ->name('reviews.destroy');

// Arrival evidence & human-confirmed NO_SHOW (TS-01 / TS-02, ADR-0060).
Route::post('reservations/{id}/arrival', [BookingArrivalController::class, 'recordArrival'])
    ->name('reservations.arrival');
Route::post('reservations/{id}/confirm-arrival', [BookingArrivalController::class, 'corroborateArrival'])
    ->name('reservations.confirm-arrival');
Route::post('admin/reservations/{id}:confirm-no-show', [BookingArrivalController::class, 'confirmNoShow'])
    ->middleware('capability:booking:noshow:confirm')
    ->name('admin.reservations.confirm-no-show');
