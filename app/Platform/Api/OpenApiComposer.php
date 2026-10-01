<?php

namespace App\Platform\Api;

use Symfony\Component\Yaml\Yaml;

/**
 * OpenAPI composition (api-platform §9.1): merges kernel shared components
 * with module fragments into one published document.
 *
 * Pure merge + validation over parsed arrays (filesystem stays in the
 * command): path collisions fail, duplicate operationIds fail, and every
 * x-permission code must exist in the declared set. Additive evolution is
 * a review rule on top of these mechanical gates.
 */
final class OpenApiComposer
{
    /**
     * Gate taxonomy: every operation declares how it is guarded.
     * x-permission = capability middleware; x-gate ownership = resolved
     * actor must own the resource (checked in code); x-gate public =
     * credential-bearing public flow (invite accept) or auth-only
     * self-service. An operation with neither fails composition.
     */
    private const PUBLIC_GATES = ['ownership', 'public'];
    /**
     * @param array<string, mixed> $kernel  Parsed kernel components file.
     * @param list<array{source: string, document: array<string, mixed>}> $fragments
     * @param list<string> $declaredPermissions
     * @return array{document: array<string, mixed>|null, errors: list<string>}
     */
    public static function compose(array $kernel, array $fragments, array $declaredPermissions): array
    {
        $errors = [];
        $paths = [];
        $operationIds = [];
        $permissions = [];

        foreach ($fragments as $fragment) {
            $source = $fragment['source'];
            $document = $fragment['document'];
            if (! is_array($document) || ! isset($document['paths']) || ! is_array($document['paths'])) {
                $errors[] = "{$source}: missing paths map";

                continue;
            }
            foreach ($document['paths'] as $path => $operations) {
                if (isset($paths[$path])) {
                    $errors[] = "{$source}: path collision on {$path}";

                    continue;
                }
                $paths[$path] = $operations;
                if (is_array($operations)) {
                    foreach ($operations as $method => $operation) {
                        if (! is_array($operation)) {
                            continue;
                        }
                        $operationId = $operation['operationId'] ?? null;
                        if (is_string($operationId)) {
                            if (isset($operationIds[$operationId])) {
                                $errors[] = "{$source}: duplicate operationId {$operationId}";
                            } else {
                                $operationIds[$operationId] = $path;
                            }
                        }
                        $required = $operation['x-permission'] ?? null;
                        if (is_string($required)) {
                            $permissions[$required] = $source;
                        }
                    }
                }
            }
        }

        foreach ($permissions as $code => $source) {
            if (! in_array($code, $declaredPermissions, true)) {
                $errors[] = "{$source}: undeclared x-permission {$code}";
            }
        }

        foreach ($paths as $path => $operations) {
            if (! is_array($operations)) {
                continue;
            }
            foreach ($operations as $method => $operation) {
                if (! is_array($operation) || strtolower((string) $method) === 'parameters') {
                    continue;
                }
                if (! isset($operation['x-permission']) && ! in_array($operation['x-gate'] ?? null, self::PUBLIC_GATES, true)) {
                    $errors[] = "operation {$operation['operationId']} ({$path}) declares no x-permission or x-gate";
                }
            }
        }

        if ($errors !== []) {
            return ['document' => null, 'errors' => $errors];
        }

        ksort($paths);

        return [
            'document' => [
                'openapi' => '3.1.0',
                'info' => [
                    'title' => 'Navdhya Platform API',
                    'version' => '1.0.0',
                ],
                'paths' => $paths,
                'components' => $kernel['components'] ?? [],
            ],
            'errors' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function parseFile(string $path): array
    {
        $parsed = Yaml::parseFile($path);

        return is_array($parsed) ? $parsed : [];
    }
}
