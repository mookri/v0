<?php

namespace App\Platform\Modules;

use Illuminate\Support\Str;

/**
 * Immutable value object describing one domain module (doc 01 §3.2) or the
 * synthesized kernel descriptor (doc 05 §2.4 — the kernel has no on-disk
 * module.json; the registry builds it from config/platform.php).
 */
final class ModuleManifest
{
    /**
     * @param  list<string>  $dependencies
     * @param  list<array{code: string, name: string, sensitive?: bool}>  $permissions
     * @param  list<string>  $scopes
     * @param  list<array{name: string, schema: string}>  $events
     * @param  list<array<string, mixed>>  $navigation
     * @param  list<string>  $extraActions
     * @param  array{openapi?: string, basePath?: string}|null  $api
     */
    private function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly string $version,
        public readonly int $phase,
        public readonly int $priority,
        public readonly ?string $featureFlag,
        public readonly array $dependencies,
        public readonly array $permissions,
        public readonly array $scopes,
        public readonly array $events,
        public readonly array $navigation,
        public readonly array $extraActions,
        public readonly ?array $api,
        public readonly string $path,
        public readonly bool $kernel,
    ) {
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    public static function fromArray(array $manifest, string $path, bool $kernel = false): self
    {
        return new self(
            code: (string) $manifest['code'],
            name: (string) ($manifest['name'] ?? $manifest['code']),
            version: (string) ($manifest['version'] ?? '0.0.0'),
            phase: (int) ($manifest['phase'] ?? 0),
            priority: (int) ($manifest['priority'] ?? 500),
            featureFlag: isset($manifest['feature_flag']) ? (string) $manifest['feature_flag'] : null,
            dependencies: array_values(array_map('strval', $manifest['dependencies'] ?? [])),
            permissions: array_values($manifest['permissions'] ?? []),
            scopes: array_values(array_map('strval', $manifest['scopes'] ?? [])),
            events: array_values($manifest['events'] ?? []),
            navigation: array_values($manifest['navigation'] ?? []),
            extraActions: array_values(array_map('strval', $manifest['actions'] ?? [])),
            api: $manifest['api'] ?? null,
            path: $path,
            kernel: $kernel,
        );
    }

    public function providerClass(): string
    {
        return 'App\\Modules\\'.Str::studly($this->code).'\\ModuleServiceProvider';
    }

    /**
     * Absolute path of the module's route file for the given channel
     * ("web" | "api"). Kernel module and modules without the file simply
     * return a non-existent path; callers check with is_file() (doc 01 §1).
     */
    public function routesPath(string $type): string
    {
        return $this->path.DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.$type.'.php';
    }
}
