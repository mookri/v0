<?php

use App\Modules\Booking\Http\Controllers\ReservationV2Controller;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Booking Module V2 API Routes
| Mounted under /api/v2/booking/* with auth.any + tenant.
|--------------------------------------------------------------------------
*/

Route::post('reservations', [ReservationV2Controller::class, 'store'])
    ->name('reservations.store');

Route::get('reservations/{bookingId}', [ReservationV2Controller::class, 'show'])
    ->name('reservations.show');

Route::get('reservations/{bookingId}/shipment', [ReservationV2Controller::class, 'shipment'])
    ->name('reservations.shipment');

Route::post('reservations/{bookingId}/checkout', [ReservationV2Controller::class, 'checkout'])
    ->name('reservations.checkout');

Route::post('reservations/{bookingId}/confirm', [ReservationV2Controller::class, 'confirm'])
    ->name('reservations.confirm');

Route::post('reservations/{bookingId}/cancellation-preview', [ReservationV2Controller::class, 'previewCancellation'])
    ->name('reservations.cancellation-preview');

Route::post('reservations/{bookingId}/cancel', [ReservationV2Controller::class, 'cancel'])
    ->name('reservations.cancel');

Route::post('reservations/{bookingId}/reschedule-preview', [ReservationV2Controller::class, 'previewReschedule'])
    ->name('reservations.reschedule-preview');

Route::post('reservations/{bookingId}/reschedule', [ReservationV2Controller::class, 'reschedule'])
    ->name('reservations.reschedule');

Route::post('reservations/{bookingId}/rebook', [ReservationV2Controller::class, 'rebook'])
    ->name('reservations.rebook');

