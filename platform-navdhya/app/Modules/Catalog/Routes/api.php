<?php

use App\Modules\Catalog\Http\Controllers\CatalogItemController;
use App\Modules\Catalog\Http\Controllers\PriceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Catalog Module API Routes (mounted by the registry at
| /api/v1/catalog/*). Reads are open to tenant members; writes carry
| item capabilities.
*/

// Public product browse (B1): the marketing site's anonymous Quick Book
// needs ACTIVE items before any session exists — no auth, no tenant
// header (the tenant is an explicit validated query param), reads only,
// IP-throttled like the other public surfaces.
Route::get('public/items', [CatalogItemController::class, 'publicIndex'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->middleware('throttle:30,1')
    ->name('public.items');
Route::get('public/categories', [CatalogItemController::class, 'publicCategories'])
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->middleware('throttle:30,1')
    ->name('public.categories');

Route::get('items', [CatalogItemController::class, 'index'])
    ->middleware(['scope:catalog:read', 'throttle.tier'])
    ->name('items.index');
Route::get('items/{id}', [CatalogItemController::class, 'show'])
    ->middleware(['scope:catalog:read', 'throttle.tier'])
    ->name('items.show');
Route::post('items', [CatalogItemController::class, 'store'])
    ->middleware('capability:catalog:item:manage')
    ->name('items.store');
Route::post('items/{id}:publish', [CatalogItemController::class, 'publish'])
    ->middleware('capability:catalog:item:manage')
    ->name('items.publish');
Route::patch('items/{id}', [CatalogItemController::class, 'update'])
    ->middleware('capability:catalog:item:manage')
    ->name('items.update');
Route::post('items/{id}:archive', [CatalogItemController::class, 'archive'])
    ->middleware('capability:catalog:item:manage')
    ->name('items.archive');
Route::post('items/{id}/prices', [PriceController::class, 'store'])
    ->middleware('capability:catalog:item:manage')
    ->name('prices.store');
Route::post('quote', [PriceController::class, 'quote'])
    ->middleware(['scope:catalog:read', 'throttle.tier'])
    ->name('quote');
