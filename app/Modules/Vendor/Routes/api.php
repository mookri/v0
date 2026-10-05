<?php

use App\Modules\Vendor\Http\Controllers\VendorInventoryController;
use App\Modules\Vendor\Http\Controllers\VendorOrderController;
use App\Modules\Vendor\Http\Controllers\VendorPayoutController;
use App\Modules\Vendor\Http\Controllers\VendorProductController;
use App\Modules\Vendor\Http\Controllers\VendorProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Vendor Module API Routes (mounted by the registry at /api/v1/vendor/*
| with auth.any + tenant; routes are never loaded by the provider).
|
| Partner = ownership-gated self-serve (scope:vendor:partner); creation
|           additionally requires an APPROVED partner-vendor application.
| Admin   = capability-gated directory, sensitive verbs step-up (2).
|--------------------------------------------------------------------------
*/

Route::middleware(['scope:vendor:partner'])->group(function (): void {
    Route::get('partner/vendors', [VendorProfileController::class, 'partnerIndex'])
        ->name('partner.vendors.index');
    Route::post('partner/vendors', [VendorProfileController::class, 'partnerStore'])
        ->name('partner.vendors.store');
    Route::get('partner/vendors/{id}', [VendorProfileController::class, 'partnerShow'])
        ->name('partner.vendors.show');
    Route::patch('partner/vendors/{id}', [VendorProfileController::class, 'partnerUpdate'])
        ->name('partner.vendors.update');

    Route::get('partner/vendors/{id}/products', [VendorProductController::class, 'index'])
        ->name('partner.vendors.products.index');
    Route::post('partner/vendors/{id}/products', [VendorProductController::class, 'store'])
        ->name('partner.vendors.products.store');
    Route::patch('partner/vendors/{id}/products/{itemId}', [VendorProductController::class, 'update'])
        ->name('partner.vendors.products.update');

    Route::get('partner/vendors/{id}/inventory', [VendorInventoryController::class, 'index'])
        ->name('partner.vendors.inventory.index');
    Route::post('partner/vendors/{id}/inventory:adjust', [VendorInventoryController::class, 'adjust'])
        ->name('partner.vendors.inventory.adjust');

    Route::get('partner/vendors/{id}/orders', [VendorOrderController::class, 'index'])
        ->name('partner.vendors.orders.index');
    Route::post('partner/vendors/{id}/orders', [VendorOrderController::class, 'store'])
        ->name('partner.vendors.orders.store');
    Route::get('partner/vendors/{id}/orders/{orderId}', [VendorOrderController::class, 'show'])
        ->name('partner.vendors.orders.show');
    Route::post('partner/vendors/{id}/orders/{orderId}:advance', [VendorOrderController::class, 'advance'])
        ->name('partner.vendors.orders.advance');
    Route::post('partner/vendors/{id}/orders/{orderId}:cancel', [VendorOrderController::class, 'cancel'])
        ->name('partner.vendors.orders.cancel');

    Route::get('partner/vendors/{id}/payouts', [VendorPayoutController::class, 'index'])
        ->name('partner.vendors.payouts.index');
});

Route::middleware(['scope:vendor:admin'])->group(function (): void {
    Route::get('admin/vendors', [VendorProfileController::class, 'adminIndex'])
        ->middleware('capability:vendor:profile:view')
        ->name('admin.vendors.index');
    Route::post('admin/vendors/{id}:verify', [VendorProfileController::class, 'verify'])
        ->middleware(['capability:vendor:profile:manage', 'stepup:2'])
        ->name('admin.vendors.verify');
    Route::post('admin/vendors/{id}:suspend', [VendorProfileController::class, 'suspend'])
        ->middleware(['capability:vendor:profile:manage', 'stepup:2'])
        ->name('admin.vendors.suspend');
});
