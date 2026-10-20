<?php

namespace App\Platform\Authz\Contracts;

use App\Platform\Authz\Exceptions\InvalidPolicyPayloadException;

/**
 * Policy capability selector (SEC-021, K-009 capability_selector): a
 * module/resource/action pattern where each segment may be a concrete
 * token or the `*` wildcard. Selectors match concrete capability codes at
 * evaluation time (T-0K01-02).
 */
final class CapabilitySelector
{
    public function __construct(
        public readonly string $module,
        public readonly string $resource,
        public readonly string $action,
    ) {
    }

    /**
     * @param  array<string, mixed>  $selector
     *
     * @throws InvalidPolicyPayloadException
     */
    public static function fromArray(array $selector): self
    {
        $unknown = array_diff(array_keys($selector), ['module', 'resource', 'action']);

        if ($unknown !== []) {
            throw new InvalidPolicyPayloadException(
                'capability_selector',
                sprintf('unknown selector keys: %s (expected module, resource, action)', implode(', ', $unknown)),
            );
        }

        return new self(
            self::segment($selector, 'module'),
            self::segment($selector, 'resource'),
            self::segment($selector, 'action'),
        );
    }

    /**
     * @param  array<string, mixed>  $selector
     *
     * @throws InvalidPolicyPayloadException
     */
    private static function segment(array $selector, string $key): string
    {
        $value = $selector[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new InvalidPolicyPayloadException('capability_selector', sprintf('%s is required', $key));
        }

        if ($value === '*') {
            return '*';
        }

        if (preg_match('/^[a-z][a-z0-9_]*$/', $value) !== 1) {
            throw new InvalidPolicyPayloadException(
                'capability_selector',
                sprintf("%s must be '*' or match [a-z][a-z0-9_]*", $key),
            );
        }

        return $value;
    }

    /**
     * Does this selector match a concrete capability code's segments?
     */
    public function matches(string $module, string $resource, string $action): bool
    {
        return ($this->module === '*' || $this->module === $module)
            && ($this->resource === '*' || $this->resource === $resource)
            && ($this->action === '*' || $this->action === $action);
    }

    /**
     * @return array{module: string, resource: string, action: string}
     */
    public function toArray(): array
    {
        return [
            'module' => $this->module,
            'resource' => $this->resource,
            'action' => $this->action,
        ];
    }
}
