<?php

use App\Modules\Customers\Http\Controllers\CustomerActivityController;
use App\Modules\Customers\Http\Controllers\CustomerAddressController;
use App\Modules\Customers\Http\Controllers\CustomerAuthController;
use App\Modules\Customers\Http\Controllers\CustomerDiscoverController;
use App\Modules\Customers\Http\Controllers\CustomerReferralController;
use App\Modules\Customers\Http\Controllers\CustomerSupportController;
use App\Modules\Customers\Http\Controllers\CustomerNotificationController;
use App\Modules\Customers\Http\Controllers\CustomerProfileController;
use App\Modules\Customers\Http\Controllers\CustomerSettingController;
use App\Modules\Customers\Http\Controllers\CustomerVerificationController;
use App\Modules\Customers\Http\Controllers\SavedItemController;
use App\Modules\Customers\Http\Controllers\StaffSupportController;
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
Route::get('auth/otp/config', [CustomerAuthController::class, 'otpConfig'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->middleware('throttle:30,1')
    ->name('auth.otp.config');
Route::post('auth/otp/resend', [CustomerAuthController::class, 'otpResend'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->middleware('throttle:10,1')
    ->name('auth.otp.resend');
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
Route::post('profile:delete', [CustomerProfileController::class, 'delete'])
    ->name('profile.delete');
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
Route::get('notifications/unread-count', [CustomerNotificationController::class, 'unreadCount'])
    ->name('notifications.unread-count');
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

// Referrals, attribution only (reward rules out of scope).
Route::get('referrals', [CustomerReferralController::class, 'index'])
    ->name('referrals.index');

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

// Staff console (plan slice 4, doc 06 §7): the tenant-wide queue over
// the SAME rows — capability-gated (customers:support:view/manage),
// while every customer-side route above stays ownership-scoped. Reads
// also serve the console pages over the session cookie; writes are the
// shared actions the console's forms post to.
Route::get('support/staff/tickets', [StaffSupportController::class, 'index'])
    ->middleware('capability:customers:support:view')
    ->name('support.staff.index');
Route::get('support/staff/tickets/{id}', [StaffSupportController::class, 'show'])
    ->middleware('capability:customers:support:view')
    ->name('support.staff.show');
Route::post('support/staff/tickets/{id}/messages', [StaffSupportController::class, 'reply'])
    ->middleware('capability:customers:support:manage')
    ->name('support.staff.reply');
Route::post('support/staff/tickets/{id}:close', [StaffSupportController::class, 'close'])
    ->middleware('capability:customers:support:manage')
    ->name('support.staff.close');
