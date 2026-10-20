<?php

namespace App\Platform\Authz\Contracts;

use App\Platform\Authz\CapabilityParser;

/**
 * K-004 Capability contract value object.
 *
 * Capabilities are parsed, not opaque strings (SEC-020): every code
 * <module>:<resource>:<action> resolves to structured metadata — module,
 * resource, action — plus version and the sensitive flag. Scopes and
 * conditions are separate dimensions and are never encoded into the
 * identifier.
 */
final class Capability
{
    public function __construct(
        public readonly string $code,
        public readonly string $module,
        public readonly string $resource,
        public readonly string $action,
        public readonly int $version = 1,
        public readonly bool $sensitive = false,
        public readonly string $description = '',
    ) {
    }

    public static function fromCode(string $code): self
    {
        return app(CapabilityParser::class)->parse($code);
    }

    /**
     * @return array{code: string, module: string, resource: string, action: string, version: int, sensitive: bool, description: string}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'module' => $this->module,
            'resource' => $this->resource,
            'action' => $this->action,
            'version' => $this->version,
            'sensitive' => $this->sensitive,
            'description' => $this->description,
        ];
    }
}
