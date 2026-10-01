<?php

use App\Http\Controllers\RoutingController;
use App\Platform\Modules\Facades\ModuleRegistry;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Business routes MUST NOT be registered here directly (doc 01 §1): each
| domain module ships Routes/web.php and the module registry mounts it.
| Kernel-owned platform web surfaces load first (doc 06 §11), and the
| Velzon Blade admin shell catch-all stays LAST so platform + module
| routes win matching.
|
*/

require __DIR__.'/auth.php';

// Kernel-owned platform web surfaces: /ready readiness probe
// (doc 05 §11.2). Mounted before the admin shell so the catch-all
// can never shadow them.
require __DIR__.'/platform/web.php';

// Domain module admin-console routes, registry-driven (doc 01 §1).
foreach (ModuleRegistry::modules() as $module) {
    $routes = $module->routesPath('web');

    if (is_file($routes)) {
        require $routes;
    }
}

// Reserved top-level segments owned by the kernel (docs 03 §2.2,
// 06 §11): the admin shell must never shadow them.
$kernelSegment = '(?!(?:api|up|ready|oauth|scim|\.well-known)(?=/|$))[^/]+';

Route::group(['prefix' => '/', 'middleware' => 'auth'], function () use ($kernelSegment) {
    Route::get('', [RoutingController::class, 'index'])->name('root');
    Route::get('{first}/{second}/{third}', [RoutingController::class, 'thirdLevel'])
        ->where('first', $kernelSegment)
        ->name('third');
    Route::get('{first}/{second}', [RoutingController::class, 'secondLevel'])
        ->where('first', $kernelSegment)
        ->name('second');
    Route::get('{any}', [RoutingController::class, 'root'])
        ->where('any', $kernelSegment)
        ->name('any');
});
