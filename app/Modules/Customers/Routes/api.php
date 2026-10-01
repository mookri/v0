<?php

use App\Modules\Customers\Http\Controllers\CustomerAuthController;
use App\Modules\Customers\Http\Controllers\CustomerProfileController;
use App\Modules\Customers\Http\Controllers\CustomerVerificationController;
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

// Self-serve surfaces (group auth.any + tenant): the caller's own
// profile and contact verification. Ownership is the actor, never a
// capability — foreign rows resolve to 404 inside the controllers.
Route::get('profile', [CustomerProfileController::class, 'show'])
    ->name('profile.show');
Route::patch('profile', [CustomerProfileController::class, 'update'])
    ->name('profile.update');
Route::post('profile:deactivate', [CustomerProfileController::class, 'deactivate'])
    ->name('profile.deactivate');
// Public like login (a suspended membership cannot pass the tenant gate,
// so reactivation takes a fresh OTP proof instead of a session).
Route::post('profile:reactivate', [CustomerProfileController::class, 'reactivate'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->middleware('throttle:10,1')
    ->name('profile.reactivate');

Route::get('verifications', [CustomerVerificationController::class, 'status'])
    ->name('verifications.status');
Route::post('verifications', [CustomerVerificationController::class, 'request'])
    ->name('verifications.request');
Route::post('verifications/{challengeId}:confirm', [CustomerVerificationController::class, 'confirm'])
    ->name('verifications.confirm');
