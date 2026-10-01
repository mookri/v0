<?php

namespace App\Platform\Modules;

use Illuminate\Support\ServiceProvider;

/**
 * Base class every domain module extends (doc 01 §2-4).
 *
 * Modules override the template methods below; the kernel calls them in
 * dependency order during boot. Modules MUST NOT re-register kernel services
 * and MUST route all cross-module communication through contracts + events
 * (doc 01 §5).
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * The module's manifest code; resolved from the class namespace by
     * default (App\Modules\Sales -> sales). Override to customize.
     */
    public function code(): string
    {
        $segments = explode('\\', static::class);

        return \Illuminate\Support\Str::snake($segments[2] ?? 'module');
    }

    /**
     * Register module-scoped container bindings. Called during register().
     * Bind contract interfaces to implementations here (doc 01 §3.1).
     */
    public function registerModule(): void
    {
    }

    /**
     * Boot module routes, migrations, policies, listeners and event
     * schemas. Called during boot() in dependency order.
     */
    public function bootModule(): void
    {
    }

    public function register(): void
    {
        $this->registerModule();
    }

    public function boot(): void
    {
        $this->bootModule();
    }

    /**
     * Load this module's migrations from its Database/Migrations directory
     * (doc 01 §3.1, doc 02 §8: per-module migrations).
     */
    protected function loadModuleMigrations(): void
    {
        $path = $this->modulePath('Database'.DIRECTORY_SEPARATOR.'Migrations');

        if (is_dir($path)) {
            $this->loadMigrationsFrom($path);
        }
    }

    /**
     * Register this module's Eloquent policies from Policies/ (doc 01 §3.3:
     * <Entity>Policy). M1 wires the Policy discovery gate.
     */
    protected function registerModulePolicies(): void
    {
    }

    protected function modulePath(string $relative = ''): string
    {
        $reflection = new \ReflectionClass(static::class);
        $base = dirname((string) $reflection->getFileName());

        return $relative === '' ? $base : $base.DIRECTORY_SEPARATOR.$relative;
    }
}
