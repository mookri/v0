<?php

namespace App\Platform\Authz;

use App\Platform\Authz\Contracts\Capability;
use App\Platform\Authz\Exceptions\CapabilityParseException;

/**
 * Capability parser (SEC-020, T-0K01-01.3): turns a
 * <module>:<resource>:<action> code into structured metadata. Parsing is
 * strict and fail-closed — malformed codes throw CapabilityParseException
 * so sync aborts with a named error code rather than registering garbage.
 *
 * The action vocabulary is open per module (SEC-020): the parser does NOT
 * police the action against a vocabulary here — manifest validation
 * (ManifestValidator) enforces the closed kernel floor + declared
 * extensions at discovery. The parser only enforces shape.
 */
final class CapabilityParser
{
    public const SEGMENT_PATTERN = '/^[a-z][a-z0-9_]*$/';

    /**
     * Parse a concrete capability code (no wildcards).
     *
     * @throws CapabilityParseException
     */
    public function parse(string $code): Capability
    {
        $segments = explode(':', $code);

        if (count($segments) !== 3) {
            throw new CapabilityParseException($code, 'must be <module>:<resource>:<action>');
        }

        [$module, $resource, $action] = $segments;

        $this->assertSegment($code, 'module', $module);
        $this->assertSegment($code, 'resource', $resource);
        $this->assertSegment($code, 'action', $action);

        return new Capability(
            code: $code,
            module: $module,
            resource: $resource,
            action: $action,
        );
    }

    /**
     * @throws CapabilityParseException
     */
    private function assertSegment(string $code, string $name, string $value): void
    {
        if (preg_match(self::SEGMENT_PATTERN, $value) !== 1) {
            throw new CapabilityParseException($code, sprintf('%s segment %s must match [a-z][a-z0-9_]*', $name, "'".$value."'"));
        }
    }
}
