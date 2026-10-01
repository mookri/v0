<?php

use App\Modules\Customers\Http\Controllers\CustomerAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customers Module API Routes (mounted by the registry at
| /api/v1/customers/* with auth.any + tenant).
|
| Auth OTP/signup/login are public by design (credential = the OTP
| challenge itself, like invite acceptance): they opt out of the group
| middleware explicitly and carry driver-agnostic throttle caps
| (REDIS-001 interim posture). Logout keeps auth.any + tenant.
*/

Route::post('auth/otp', [CustomerAuthController::class, 'requestOtp'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->middleware('throttle:10,1')
    ->name('auth.otp');
Route::post('auth/signup', [CustomerAuthController::class, 'signup'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->middleware('throttle:10,1')
    ->name('auth.signup');
Route::post('auth/verify', [CustomerAuthController::class, 'verifySignup'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->middleware('throttle:10,1')
    ->name('auth.verify');
Route::post('auth/login', [CustomerAuthController::class, 'login'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->middleware('throttle:10,1')
    ->name('auth.login');

Route::post('auth/logout', [CustomerAuthController::class, 'logout'])
    ->name('auth.logout');
