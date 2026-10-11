<?php

namespace App\Modules\SeoContent;

use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        \Log::info('[SeoContent] registerModule called');
        if ($this->app->runningInConsole()) {
            \Log::info('[SeoContent] registering seo:import command');
            $this->commands([
                \App\Modules\SeoContent\Console\SeoImportCommand::class,
            ]);
        }
    }

    public function bootModule(): void
    {
        \Log::info('[SeoContent] bootModule called');
        $this->loadModuleMigrations();
    }
}
