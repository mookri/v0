<?php

namespace App\Platform\Modules\Facades;

use App\Platform\Modules\ModuleManifest;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void boot()
 * @method static list<ModuleManifest> modules()
 * @method static ModuleManifest|null find(string $code)
 * @method static bool has(string $code)
 * @method static array<string, array{code: string, name: string, sensitive: bool, module_code: string}> permissions()
 * @method static list<string> scopes()
 * @method static list<string> events()
 * @method static bool enabled(ModuleManifest $module, mixed $tenant = null)
 *
 * @see \App\Platform\Modules\ModuleRegistry
 */
final class ModuleRegistry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Platform\Modules\ModuleRegistry::class;
    }
}
