<?php

use App\Modules\Temple\Http\Controllers\TempleDashboardController;
use App\Modules\Temple\Http\Controllers\TempleManagerController;
use App\Modules\Temple\Http\Controllers\TempleProfileController;
use App\Modules\Temple\Http\Controllers\TemplePublicController;
use App\Modules\Temple\Http\Controllers\TemplePujaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Temple Module API Routes (mounted by the registry at /api/v1/temple/*
| with auth.any + tenant; routes are never loaded by the provider).
|
| Public     = browse/search directory (scope:temple:read, throttle).
| Partner    = self-serve CRUD (scope:temple:partner).
| Dashboard  = temple-scoped management (scope:temple:partner + capability).
| Admin      = capability-gated, sensitive verbs additionally step-up.
|--------------------------------------------------------------------------
*/

// Public browse directory (scope:temple:read, throttle)
Route::get('profiles', [TemplePublicController::class, 'index'])
    ->middleware(['scope:temple:read', 'throttle:60,1'])
    ->name('profiles.index');

Route::get('profiles/{id}', [TemplePublicController::class, 'show'])
    ->middleware(['scope:temple:read', 'throttle:60,1'])
    ->name('profiles.show');

Route::get('profiles/{templeId}/pujas', [TemplePublicController::class, 'pujas'])
    ->middleware(['scope:temple:read', 'throttle:60,1'])
    ->name('profiles.pujas');

Route::get('profiles/{templeId}/availability', [TemplePublicController::class, 'availability'])
    ->middleware(['scope:temple:read', 'throttle:60,1'])
    ->name('profiles.availability');

// Partner self-serve: temple CRUD + pujas + managers (scope:temple:partner)
Route::middleware(['scope:temple:partner'])->group(function () {
    Route::get('partner/temples', [TempleProfileController::class, 'partnerIndex'])
        ->name('partner.temples.index');

    Route::post('partner/temples', [TempleProfileController::class, 'partnerStore'])
        ->name('partner.temples.store');

    Route::get('partner/temples/{id}', [TempleProfileController::class, 'partnerShow'])
        ->name('partner.temples.show');

    Route::patch('partner/temples/{id}', [TempleProfileController::class, 'partnerUpdate'])
        ->name('partner.temples.update');

    // Temple pujas
    Route::get('partner/temples/{templeId}/pujas', [TemplePujaController::class, 'index'])
        ->name('partner.temples.pujas.index');

    Route::post('partner/temples/{templeId}/pujas', [TemplePujaController::class, 'store'])
        ->name('partner.temples.pujas.store');

    Route::get('partner/temples/{templeId}/pujas/{pujaId}', [TemplePujaController::class, 'show'])
        ->name('partner.temples.pujas.show');

    Route::put('partner/temples/{templeId}/pujas/{pujaId}', [TemplePujaController::class, 'update'])
        ->name('partner.temples.pujas.update');

    Route::delete('partner/temples/{templeId}/pujas/{pujaId}', [TemplePujaController::class, 'destroy'])
        ->name('partner.temples.pujas.destroy');

    // Temple managers
    Route::get('partner/temples/{templeId}/managers', [TempleManagerController::class, 'index'])
        ->name('partner.temples.managers.index');

    Route::post('partner/temples/{templeId}/managers', [TempleManagerController::class, 'store'])
        ->middleware('capability:temple:manager:manage')
        ->name('partner.temples.managers.store');

    Route::put('partner/temples/{templeId}/managers/{managerId}', [TempleManagerController::class, 'update'])
        ->middleware('capability:temple:manager:manage')
        ->name('partner.temples.managers.update');

    Route::delete('partner/temples/{templeId}/managers/{managerId}', [TempleManagerController::class, 'destroy'])
        ->middleware('capability:temple:manager:manage')
        ->name('partner.temples.managers.destroy');

    // Temple dashboard
    Route::get('partner/temples/{templeId}/dashboard/overview', [TempleDashboardController::class, 'overview'])
        ->name('partner.temples.dashboard.overview');

    Route::get('partner/temples/{templeId}/dashboard/pujas', [TempleDashboardController::class, 'pujas'])
        ->name('partner.temples.dashboard.pujas');

    Route::get('partner/temples/{templeId}/dashboard/bookings', [TempleDashboardController::class, 'bookings'])
        ->name('partner.temples.dashboard.bookings');

    Route::get('partner/temples/{templeId}/dashboard/calendar', [TempleDashboardController::class, 'calendar'])
        ->name('partner.temples.dashboard.calendar');

    Route::get('partner/temples/{templeId}/dashboard/analytics', [TempleDashboardController::class, 'analytics'])
        ->name('partner.temples.dashboard.analytics');

    Route::get('partner/temples/{templeId}/dashboard/settings', [TempleDashboardController::class, 'settings'])
        ->name('partner.temples.dashboard.settings');

    // Council Approvals (C3, WP6)
    Route::get('partner/temples/{templeId}/council/approvals', [TempleDashboardController::class, 'councilApprovals'])
        ->name('partner.temples.council.approvals.index');

    Route::post('partner/temples/{templeId}/council/approvals/{approvalId}/decide', [TempleDashboardController::class, 'decideCouncilApproval'])
        ->name('partner.temples.council.approvals.decide');
});

// Admin surface (scope:temple:admin + capability)
Route::middleware(['scope:temple:admin'])->group(function () {
    Route::get('admin/profiles', [TempleProfileController::class, 'adminIndex'])
        ->middleware('capability:temple:profile:view')
        ->name('admin.profiles.index');

    Route::post('admin/profiles/{id}:verify', [TempleProfileController::class, 'verify'])
        ->middleware(['capability:temple:profile:manage', 'stepup:2'])
        ->name('admin.profiles.verify');

    Route::post('admin/profiles/{id}:publish', [TempleProfileController::class, 'publish'])
        ->middleware(['capability:temple:profile:manage', 'stepup:2'])
        ->name('admin.profiles.publish');

    Route::post('admin/profiles/{id}:suspend', [TempleProfileController::class, 'suspend'])
        ->middleware(['capability:temple:profile:manage', 'stepup:2'])
        ->name('admin.profiles.suspend');

    Route::post('admin/profiles/{id}:reactivate', [TempleProfileController::class, 'reactivate'])
        ->middleware(['capability:temple:profile:manage', 'stepup:2'])
        ->name('admin.profiles.reactivate');
});
