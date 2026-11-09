<?php

use App\Platform\Modules\Facades\ModuleRegistry;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Contract-first surface at /api/v1/{module}/{resource} (doc 06 §2).
| Business routes MUST NOT be registered here directly (doc 01 §1):
| kernel-owned platform surfaces live in routes/platform/api.php, and
| each domain module ships Routes/api.php mounted by the module registry.
|
*/

require __DIR__.'/platform/api.php';

$version = config('platform.api_version', 'v1');
$apiVersions = config('platform.api_versions', []);

foreach (ModuleRegistry::modules() as $module) {
    $routes = $module->routesPath('api');

    if (is_file($routes)) {
        // Fail-closed default: every module route requires auth + tenant
        // resolution. Public surfaces opt out explicitly per route with
        // ->withoutMiddleware(['auth', 'tenant']) and a comment citing why.
        Route::prefix($version.'/'.$module->code)
            ->middleware(['auth.any', 'tenant'])
            ->name($module->code.'.')
            ->group($routes);
    }

    $versions = $apiVersions[$module->code] ?? [];
    foreach ($versions as $extraVersion) {
        if ($extraVersion === 'v1') {
            continue;
        }

        $extraRoutes = $module->routesPath('api-'.$extraVersion);
        if (is_file($extraRoutes)) {
            Route::prefix($extraVersion.'/'.$module->code)
                ->middleware(['auth.any', 'tenant'])
                ->name($module->code.'.'.$extraVersion.'.')
                ->group($extraRoutes);
        }
    }
}
