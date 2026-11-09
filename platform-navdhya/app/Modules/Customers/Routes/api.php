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
use App\Modules\Customers\Http\Controllers\CustomerPrivacyController;
use App\Modules\Customers\Http\Controllers\CustomerSpiritualController;
use App\Modules\Customers\Http\Controllers\CustomerTelemetryController;
use App\Modules\Customers\Http\Controllers\CustomerWebhookController;
use App\Modules\Customers\Http\Controllers\SavedItemController;
use App\Modules\Customers\Http\Controllers\StaffSupportController;
use App\Platform\Identity\Http\Middleware\LegacyLoginSurfaceGate;
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
|
| Runtime separation (ADR-0020 D1/D6): the public credential surface
| also carries LegacyLoginSurfaceGate — it never exists on the issuer
| runtime (the issuer is sole) and vanishes on a federated application
| runtime (cutover committed); the monolith keeps it in both modes.
*/

Route::post('auth/otp', [CustomerAuthController::class, 'requestOtp'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->middleware(['throttle:10,1', LegacyLoginSurfaceGate::class])
    ->name('auth.otp');
Route::get('auth/otp/config', [CustomerAuthController::class, 'otpConfig'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->middleware(['throttle:30,1', LegacyLoginSurfaceGate::class])
    ->name('auth.otp.config');
Route::post('auth/otp/resend', [CustomerAuthController::class, 'otpResend'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->middleware(['throttle:10,1', LegacyLoginSurfaceGate::class])
    ->name('auth.otp.resend');
Route::post('auth/signup', [CustomerAuthController::class, 'signup'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->middleware(['throttle:10,1', LegacyLoginSurfaceGate::class])
    ->name('auth.signup');
Route::post('auth/verify', [CustomerAuthController::class, 'verifySignup'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->middleware(['throttle:10,1', LegacyLoginSurfaceGate::class])
    ->name('auth.verify');
Route::post('auth/login', [CustomerAuthController::class, 'login'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->middleware(['throttle:10,1', LegacyLoginSurfaceGate::class])
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
// so reactivation takes a fresh OTP proof instead of a session) — same
// runtime separation as the login surface above.
Route::post('profile:reactivate', [CustomerProfileController::class, 'reactivate'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->middleware(['throttle:10,1', LegacyLoginSurfaceGate::class])
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

// Notification Delivery Receipt Webhooks (E4)
Route::post('webhooks/msg91', [CustomerWebhookController::class, 'handleMsg91'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->middleware('throttle:60,1')
    ->name('webhooks.msg91');
Route::match(['get', 'post'], 'webhooks/whatsapp', [CustomerWebhookController::class, 'handleWhatsApp'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->middleware('throttle:60,1')
    ->name('webhooks.whatsapp');

// Telemetry Event Ingestion (E5)
Route::post('events', [CustomerTelemetryController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('telemetry.store');

// Devotee Privacy, Data Rights & Erasure (E6)
Route::get('me/data-export', [CustomerPrivacyController::class, 'export'])
    ->middleware('throttle:5,1')
    ->name('privacy.export');
Route::post('me/erasure', [CustomerPrivacyController::class, 'erasure'])
    ->middleware('throttle:5,1')
    ->name('privacy.erasure');

// Spiritual Profiles, Ancestral Lineage & Panchang Reminders (E1 / E2 / E3)
Route::get('spiritual', [CustomerSpiritualController::class, 'showSpiritual'])
    ->name('spiritual.show');
Route::put('spiritual', [CustomerSpiritualController::class, 'updateSpiritual'])
    ->name('spiritual.update');

Route::get('family', [CustomerSpiritualController::class, 'listFamily'])
    ->name('family.list');
Route::post('family', [CustomerSpiritualController::class, 'storeFamily'])
    ->name('family.store');
Route::delete('family/{id}', [CustomerSpiritualController::class, 'destroyFamily'])
    ->name('family.destroy');

Route::get('reminders', [CustomerSpiritualController::class, 'listReminders'])
    ->name('reminders.list');
Route::post('reminders', [CustomerSpiritualController::class, 'storeReminder'])
    ->name('reminders.store');
Route::delete('reminders/{id}', [CustomerSpiritualController::class, 'destroyReminder'])
    ->name('reminders.destroy');

Route::get('consent', [CustomerSpiritualController::class, 'getConsent'])
    ->name('consent.get');
Route::post('consent', [CustomerSpiritualController::class, 'updateConsent'])
    ->name('consent.update');

Route::get('preferences', [CustomerSpiritualController::class, 'getPreferences'])
    ->name('preferences.get');
Route::patch('preferences', [CustomerSpiritualController::class, 'updatePreferences'])
    ->name('preferences.update');
