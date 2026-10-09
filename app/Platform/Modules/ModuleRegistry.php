<?php

namespace App\Platform\Modules;

use Illuminate\Contracts\Foundation\Application;

/**
 * Kernel module registry (doc 01 §2).
 *
 * Responsibilities:
 *  1. Discover modules by scanning app/Modules/{Module}/module.json.
 *  2. Validate manifests against doc 01 §3.2/§3.3 (fail fast, boot error).
 *  3. Resolve the dependency graph; cycles are a boot error.
 *  4. Expose query APIs consumed by the kernel and tooling: modules(),
 *     permissions(), scopes(), events(), enabled(tenant) — the latter is
 *     feature-flag gated and wired in M1 once FeatureFlags lands.
 *
 * The kernel itself has no on-disk manifest; it is synthesized from
 * config/platform.php ('kernel_module') so dependency graphs may reference
 * the 'platform' module code (doc 05 §2.4).
 */
final class ModuleRegistry
{
    /**
     * @var list<ModuleManifest>
     */
    private array $modules = [];

    private bool $booted = false;

    public function __construct(
        private readonly Application $app,
        private readonly ManifestValidator $validator,
    ) {
    }

    /**
     * Discover, validate and topologically order all modules. Safe to call
     * repeatedly; discovery runs once per process.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $kernel = $this->kernelManifest();
        $discovered = $this->discover();

        $this->assertNoDuplicateCodes($kernel, $discovered);

        // Kernel first, then modules ordered by (priority, code).
        usort($discovered, function (ModuleManifest $a, ModuleManifest $b): int {
            return [$a->priority, $a->code] <=> [$b->priority, $b->code];
        });

        $this->modules = [$kernel, ...$discovered];
        $this->assertDependenciesExist();
        $this->modules = $this->resolveDependencyOrder();
        $this->registerModuleProviders();
        $this->booted = true;
    }

    /**
     * Register every discovered module's service provider with Laravel, in
     * dependency order (migrations, bindings and boot hooks flow from
     * there). Manifests without a provider class are routes-only modules
     * and are skipped; ModuleShapeTest requires real modules to ship one.
     */
    private function registerModuleProviders(): void
    {
        foreach ($this->modules as $module) {
            if ($module->kernel) {
                continue;
            }
            $provider = $module->providerClass();
            if (! class_exists($provider)) {
                continue;
            }
            $this->app->register($provider);
        }
    }

    /**
     * @return list<ModuleManifest> Dependency-ordered, kernel first.
     */
    public function modules(): array
    {
        $this->boot();

        return $this->modules;
    }

    public function find(string $code): ?ModuleManifest
    {
        $this->boot();

        foreach ($this->modules as $module) {
            if ($module->code === $code) {
                return $module;
            }
        }

        return null;
    }

    public function has(string $code): bool
    {
        return $this->find($code) !== null;
    }

    /**
     * All declared permissions across every module, keyed by code.
     *
     * @return array<string, array{code: string, name: string, sensitive: bool, module_code: string}>
     */
    public function permissions(): array
    {
        $this->boot();

        $permissions = [];

        foreach ($this->modules as $module) {
            foreach ($module->permissions as $permission) {
                $code = (string) $permission['code'];
                $permissions[$code] = [
                    'code' => $code,
                    'name' => (string) $permission['name'],
                    'sensitive' => (bool) ($permission['sensitive'] ?? false),
                    'module_code' => $module->code,
                ];
            }
        }

        ksort($permissions);

        return $permissions;
    }

    /**
     * All declared non-trivial scopes (doc 04 §6: read/write are implicit).
     *
     * @return list<string>
     */
    public function scopes(): array
    {
        $this->boot();

        $scopes = [];

        foreach ($this->modules as $module) {
            foreach ($module->scopes as $scope) {
                $scopes[] = $scope;
            }
        }

        sort($scopes);

        return array_values(array_unique($scopes));
    }

    /**
     * All declared event names across every module.
     *
     * @return list<string>
     */
    public function events(): array
    {
        $this->boot();

        $events = [];

        foreach ($this->modules as $module) {
            foreach ($module->events as $event) {
                $events[] = (string) $event['name'];
            }
        }

        sort($events);

        return array_values(array_unique($events));
    }

    /**
     * Feature-flag gate for per-tenant module enablement (doc 01 §2-5,
     * doc 07 §10). A module with no feature_flag is always enabled; a
     * flagged module consults the FeatureFlags kernel service and
     * defaults to enabled while the flag is undefined (default-safe
     * branch, doc 05 §10.2 rule 1) — an explicit seeded default or a
     * tenant override/bundle value turns it off.
     */
    public function enabled(ModuleManifest $module, mixed $tenant = null): bool
    {
        if ($module->featureFlag === null) {
            return true;
        }

        if (! $this->app->bound('platform.feature_flags')) {
            return true;
        }

        return (bool) $this->app->make('platform.feature_flags')
            ->enabled($module->featureFlag, $tenant, true);
    }

    /**
     * @return list<ModuleManifest>
     */
    private function discover(): array
    {
        $modulesPath = config('platform.modules_path');

        if (! is_dir($modulesPath)) {
            return [];
        }

        $discovered = [];

        foreach (glob($modulesPath.DIRECTORY_SEPARATOR.'*') ?: [] as $directory) {
            if (! is_dir($directory)) {
                continue;
            }

            $manifestPath = $directory.DIRECTORY_SEPARATOR.'module.json';

            if (! is_file($manifestPath)) {
                continue;
            }

            $manifest = json_decode((string) file_get_contents($manifestPath), true);

            if (! is_array($manifest)) {
                throw new \RuntimeException(sprintf(
                    'Module manifest %s is not valid JSON.',
                    $manifestPath,
                ));
            }

            $violations = $this->validator->validate($manifest, basename($directory));

            if ($violations !== []) {
                throw new \RuntimeException(sprintf(
                    "Module manifest %s is invalid:\n - %s",
                    $manifestPath,
                    implode("\n - ", $violations),
                ));
            }

            $discovered[] = ModuleManifest::fromArray($manifest, $directory);
        }

        return $discovered;
    }

    private function kernelManifest(): ModuleManifest
    {
        $kernel = config('platform.kernel_module', []);

        return ModuleManifest::fromArray(
            manifest: [
                'code' => $kernel['code'] ?? 'platform',
                'name' => $kernel['name'] ?? 'Platform Kernel',
                'version' => $kernel['version'] ?? '1.0.0',
                'phase' => $kernel['phase'] ?? 0,
                'priority' => 0,
                'actions' => $kernel['actions'] ?? [],
                'permissions' => $kernel['permissions'] ?? [],
                'scopes' => $kernel['scopes'] ?? [],
                'events' => $kernel['events'] ?? [],
                'navigation' => $kernel['navigation'] ?? [],
            ],
            path: app_path('Platform'),
            kernel: true,
        );
    }

    /**
     * @param  list<ModuleManifest>  $discovered
     */
    private function assertNoDuplicateCodes(ModuleManifest $kernel, array $discovered): void
    {
        $seen = [$kernel->code => true];

        foreach ($discovered as $module) {
            if (isset($seen[$module->code])) {
                throw new \RuntimeException(sprintf(
                    "Duplicate module code '%s' detected.",
                    $module->code,
                ));
            }

            $seen[$module->code] = true;
        }
    }

    private function assertDependenciesExist(): void
    {
        $codes = [];

        foreach ($this->modules as $module) {
            $codes[$module->code] = true;
        }

        foreach ($this->modules as $module) {
            foreach ($module->dependencies as $dependency) {
                if (! isset($codes[$dependency])) {
                    throw new \RuntimeException(sprintf(
                        "Module '%s' depends on unknown module '%s'.",
                        $module->code,
                        $dependency,
                    ));
                }
            }
        }
    }

    /**
     * Kahn's algorithm for a stable topological order (dependencies first).
     * Cycles are a boot error (doc 01 §2-3).
     *
     * @return list<ModuleManifest>
     */
    private function resolveDependencyOrder(): array
    {
        $byCode = [];

        foreach ($this->modules as $module) {
            $byCode[$module->code] = $module;
        }

        $inDegree = [];
        $dependents = [];

        foreach ($this->modules as $module) {
            $inDegree[$module->code] = 0;
        }

        foreach ($this->modules as $module) {
            foreach ($module->dependencies as $dependency) {
                $inDegree[$module->code]++;
                $dependents[$dependency][] = $module->code;
            }
        }

        // Seed with zero-dependency modules, preserving priority order.
        $queue = [];

        foreach ($this->modules as $module) {
            if ($inDegree[$module->code] === 0) {
                $queue[] = $module->code;
            }
        }

        $ordered = [];

        while ($queue !== []) {
            $code = array_shift($queue);
            $ordered[] = $byCode[$code];

            foreach ($dependents[$code] ?? [] as $dependent) {
                if (--$inDegree[$dependent] === 0) {
                    $queue[] = $dependent;
                }
            }
        }

        if (count($ordered) !== count($this->modules)) {
            $remaining = array_diff(array_keys($byCode), array_map(
                static fn (ModuleManifest $m): string => $m->code,
                $ordered,
            ));

            throw new \RuntimeException(sprintf(
                'Module dependency cycle detected involving: %s.',
                implode(', ', $remaining),
            ));
        }

        return $ordered;
    }
}
