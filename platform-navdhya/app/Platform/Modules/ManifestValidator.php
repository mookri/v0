<?php

namespace App\Platform\Modules;

use Illuminate\Support\Str;

/**
 * Decision-complete manifest validation per doc 01 §3.2/§3.3.
 * Returns the full violation list so boot fails fast with actionable
 * diagnostics instead of one error at a time.
 */
final class ManifestValidator
{
    /**
     * Closed action vocabulary (doc 01 §3.3); manifests may extend it via
     * the top-level "actions" field (module-specific verbs).
     */
    public const CORE_ACTIONS = [
        'view', 'list', 'create', 'update', 'delete',
        'approve', 'reject', 'export', 'configure',
    ];

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<string>
     */
    public function validate(array $manifest, string $directoryName): array
    {
        $violations = [];

        foreach (['code', 'name', 'version'] as $required) {
            if (! isset($manifest[$required]) || ! is_string($manifest[$required]) || trim($manifest[$required]) === '') {
                $violations[] = "missing required string field '{$required}'";
            }
        }

        if ($violations !== []) {
            return $violations;
        }

        $code = (string) $manifest['code'];

        if (preg_match('/^[a-z][a-z0-9_]*$/', $code) !== 1) {
            $violations[] = "code '{$code}' must be lowercase snake_case";
        }

        if (Str::studly($code) !== $directoryName) {
            $violations[] = "code '{$code}' does not match directory name '{$directoryName}' (expected '".Str::studly($code)."')";
        }

        if (preg_match('/^\d+\.\d+\.\d+(-[0-9A-Za-z.-]+)?$/', (string) $manifest['version']) !== 1) {
            $violations[] = "version '{$manifest['version']}' is not semantic (x.y.z)";
        }

        if (isset($manifest['phase']) && ! is_int($manifest['phase'])) {
            $violations[] = 'phase must be an integer';
        }

        if (isset($manifest['priority']) && ! is_int($manifest['priority'])) {
            $violations[] = 'priority must be an integer';
        }

        $violations = [
            ...$violations,
            ...$this->validateDependencies($manifest),
            ...$this->validatePermissions($manifest, $code),
            ...$this->validateScopes($manifest, $code),
            ...$this->validateEvents($manifest, $code),
            ...$this->validateNavigation($manifest, $code),
        ];

        return $violations;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<string>
     */
    private function validateDependencies(array $manifest): array
    {
        $violations = [];

        foreach ($manifest['dependencies'] ?? [] as $dependency) {
            if (! is_string($dependency) || preg_match('/^[a-z][a-z0-9_]*$/', $dependency) !== 1) {
                $violations[] = 'dependencies must be lowercase module codes';
            }
        }

        return $violations;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<string>
     */
    private function validatePermissions(array $manifest, string $code): array
    {
        $violations = [];
        $actions = [...self::CORE_ACTIONS, ...array_map('strval', $manifest['actions'] ?? [])];

        foreach ($manifest['permissions'] ?? [] as $permission) {
            if (! is_array($permission) || ! isset($permission['code'], $permission['name'])) {
                $violations[] = 'each permission requires code and name';

                continue;
            }

            $permissionCode = (string) $permission['code'];

            if (! str_starts_with($permissionCode, $code.':')) {
                $violations[] = "permission '{$permissionCode}' must start with module prefix '{$code}:'";
            }

            $segments = explode(':', $permissionCode);

            if (count($segments) !== 3) {
                $violations[] = "permission '{$permissionCode}' must be <module>:<resource>:<action>";

                continue;
            }

            [, $resource, $action] = $segments;

            if (preg_match('/^[a-z0-9_]+$/', $resource) !== 1) {
                $violations[] = "permission '{$permissionCode}' resource must match [a-z0-9_]+";
            }

            if (! in_array($action, $actions, true)) {
                $violations[] = "permission '{$permissionCode}' action '{$action}' is outside the closed vocabulary";
            }
        }

        return $violations;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<string>
     */
    private function validateScopes(array $manifest, string $code): array
    {
        $violations = [];

        foreach ($manifest['scopes'] ?? [] as $scope) {
            if (! is_string($scope) || ! str_starts_with($scope, $code.':')) {
                $violations[] = is_string($scope)
                    ? "scope '{$scope}' must start with module prefix '{$code}:'"
                    : 'scopes must be strings';
            }
        }

        return $violations;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<string>
     */
    private function validateEvents(array $manifest, string $code): array
    {
        $violations = [];

        foreach ($manifest['events'] ?? [] as $event) {
            if (! is_array($event) || ! isset($event['name'])) {
                $violations[] = 'each event requires a name';

                continue;
            }

            $name = (string) $event['name'];

            if (preg_match('/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/', $name) !== 1) {
                $violations[] = "event '{$name}' must be <module>.<entity>.<past_verb>";

                continue;
            }

            if (! str_starts_with($name, $code.'.')) {
                $violations[] = "event '{$name}' must belong to module '{$code}'";
            }

            if (! isset($event['schema']) || ! is_string($event['schema'])) {
                $violations[] = "event '{$name}' requires a schema file reference";
            }
        }

        return $violations;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<string>
     */
    private function validateNavigation(array $manifest, string $code): array
    {
        $violations = [];
        $declared = array_map(
            static fn (array $permission): string => (string) ($permission['code'] ?? ''),
            array_filter($manifest['permissions'] ?? [], 'is_array'),
        );

        foreach ($manifest['navigation'] ?? [] as $entry) {
            if (! is_array($entry) || ! isset($entry['label'], $entry['route'], $entry['permission'])) {
                $violations[] = 'each navigation entry requires label, route and permission';

                continue;
            }

            if (! in_array((string) $entry['permission'], $declared, true)) {
                $violations[] = "navigation permission '{$entry['permission']}' is not declared in the manifest";
            }
        }

        return $violations;
    }
}
