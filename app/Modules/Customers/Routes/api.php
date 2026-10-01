<?php

use App\Modules\Customers\Http\Controllers\CustomerActivityController;
use App\Modules\Customers\Http\Controllers\CustomerAddressController;
use App\Modules\Customers\Http\Controllers\CustomerAuthController;
use App\Modules\Customers\Http\Controllers\CustomerDiscoverController;
use App\Modules\Customers\Http\Controllers\CustomerSupportController;
use App\Modules\Customers\Http\Controllers\CustomerNotificationController;
use App\Modules\Customers\Http\Controllers\CustomerProfileController;
use App\Modules\Customers\Http\Controllers\CustomerSettingController;
use App\Modules\Customers\Http\Controllers\CustomerVerificationController;
use App\Modules\Customers\Http\Controllers\SavedItemController;
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

// Address book (B4): caller-scoped CRUD with default promotion.
Route::get('addresses', [CustomerAddressController::class, 'index'])
    ->name('addresses.index');
Route::post('addresses', [CustomerAddressController::class, 'store'])
    ->name('addresses.store');
Route::patch('addresses/{id}', [CustomerAddressController::class, 'update'])
    ->name('addresses.update');
Route::delete('addresses/{id}', [CustomerAddressController::class, 'destroy'])
    ->name('addresses.destroy');
Route::post('addresses/{id}:default', [CustomerAddressController::class, 'makeDefault'])
    ->name('addresses.default');

// Saved bookmarks (B4): opaque refs, enriched by readers.
Route::get('saved', [SavedItemController::class, 'index'])
    ->name('saved.index');
Route::post('saved', [SavedItemController::class, 'store'])
    ->name('saved.store');
Route::delete('saved/{id}', [SavedItemController::class, 'destroy'])
    ->name('saved.destroy');

// Notifications (B4): producer-written, customer read + mark-read.
Route::get('notifications', [CustomerNotificationController::class, 'index'])
    ->name('notifications.index');
Route::post('notifications/{id}:read', [CustomerNotificationController::class, 'read'])
    ->name('notifications.read');
Route::post('notifications:read-all', [CustomerNotificationController::class, 'readAll'])
    ->name('notifications.read-all');

// Settings (B4): closed key set over defaults.
Route::get('settings', [CustomerSettingController::class, 'index'])
    ->name('settings.index');
Route::patch('settings', [CustomerSettingController::class, 'update'])
    ->name('settings.update');

// Discover feed (B6): deterministic composition via Contracts ports.
Route::get('discover', [CustomerDiscoverController::class, 'feed'])
    ->name('discover.feed');

// Activity feed (B6): customer-safe read model, never audit rows.
Route::get('activity', [CustomerActivityController::class, 'index'])
    ->name('activity.index');

// Support tickets, customer side (B7): staff console is separate.
Route::post('support/tickets', [CustomerSupportController::class, 'store'])
    ->name('support.store');
Route::get('support/tickets', [CustomerSupportController::class, 'index'])
    ->name('support.index');
Route::get('support/tickets/{id}', [CustomerSupportController::class, 'show'])
    ->name('support.show');
Route::post('support/tickets/{id}/messages', [CustomerSupportController::class, 'reply'])
    ->name('support.reply');
Route::post('support/tickets/{id}:close', [CustomerSupportController::class, 'close'])
    ->name('support.close');
