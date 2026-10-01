<?php

namespace App\Platform\Authz\Contracts;

use App\Platform\Authz\Exceptions\InvalidPolicyPayloadException;

/**
 * Policy / role-assignment scope binding (SEC-021, K-009 scope_bindings).
 * scope_type/scope_id resolve against hierarchy nodes (the hierarchy
 * wiring lands in T-0K02-01); scope_mode is the optional HIER-005
 * mechanism.
 */
final class ScopeBinding
{
    public const SCOPE_MODES = ['SELF', 'NODE', 'DIRECT_CHILDREN', 'SUBTREE'];

    public function __construct(
        public readonly string $scopeType,
        public readonly string $scopeId,
        public readonly ?string $scopeMode = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $binding
     *
     * @throws InvalidPolicyPayloadException
     */
    public static function fromArray(array $binding): self
    {
        $unknown = array_diff(array_keys($binding), ['scope_type', 'scope_id', 'scope_mode']);

        if ($unknown !== []) {
            throw new InvalidPolicyPayloadException(
                'scope_bindings',
                sprintf('unknown scope binding keys: %s', implode(', ', $unknown)),
            );
        }

        $scopeType = $binding['scope_type'] ?? null;
        $scopeId = $binding['scope_id'] ?? null;
        $scopeMode = $binding['scope_mode'] ?? null;

        if (! is_string($scopeType) || preg_match('/^[a-z][a-z0-9_]*$/', $scopeType) !== 1) {
            throw new InvalidPolicyPayloadException('scope_bindings', 'scope_type must match [a-z][a-z0-9_]*');
        }

        if (! is_string($scopeId) || $scopeId === '' || strlen($scopeId) > 64) {
            throw new InvalidPolicyPayloadException('scope_bindings', 'scope_id must be a non-empty string (max 64 chars)');
        }

        if ($scopeMode !== null && (! is_string($scopeMode) || ! in_array($scopeMode, self::SCOPE_MODES, true))) {
            throw new InvalidPolicyPayloadException(
                'scope_bindings',
                sprintf('scope_mode must be one of %s', implode('|', self::SCOPE_MODES)),
            );
        }

        return new self($scopeType, $scopeId, $scopeMode);
    }

    /**
     * @return array{scope_type: string, scope_id: string, scope_mode?: string}
     */
    public function toArray(): array
    {
        $payload = [
            'scope_type' => $this->scopeType,
            'scope_id' => $this->scopeId,
        ];

        if ($this->scopeMode !== null) {
            $payload['scope_mode'] = $this->scopeMode;
        }

        return $payload;
    }
}
