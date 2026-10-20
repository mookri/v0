<?php

namespace App\Modules\SeoContent;

use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Modules\SeoContent\Console\SeoImportCommand::class,
            ]);
        }
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();
    }
}
