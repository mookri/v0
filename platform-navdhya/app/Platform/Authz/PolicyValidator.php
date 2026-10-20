<?php

namespace App\Platform\Authz;

use App\Platform\Authz\Contracts\Condition;
use App\Platform\Authz\Contracts\Effect;
use App\Platform\Authz\Contracts\ScopeBinding;
use App\Platform\Authz\Contracts\CapabilitySelector;
use App\Platform\Authz\Exceptions\InvalidPolicyPayloadException;

/**
 * Policy payload validation (SEC-021, LD-15, T-0K01-01.3).
 *
 * Enforces the declarative policy grammar: effect allow|deny, a
 * module/resource/action capability selector (each segment concrete or
 * `*`), scope bindings, and the bounded condition grammar (closed operator
 * set over declared fields). Free-form expression languages are banned —
 * anything outside the grammar throws InvalidPolicyPayloadException, which
 * the API layer renders as 422 `validation_failed`.
 */
final class PolicyValidator
{
    private const STORE_KEYS = [
        'name', 'description', 'effect', 'capability_selector',
        'scope_bindings', 'conditions', 'priority',
    ];

    private const UPDATE_KEYS = [
        'name', 'description', 'effect', 'capability_selector',
        'scope_bindings', 'conditions', 'priority',
    ];

    /**
     * Validate a create payload; returns the normalized, storable array.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws InvalidPolicyPayloadException
     */
    public function validateForStore(array $input): array
    {
        $this->assertKnownKeys($input, self::STORE_KEYS);

        foreach (['name', 'effect', 'capability_selector'] as $required) {
            if (! array_key_exists($required, $input)) {
                throw new InvalidPolicyPayloadException($required, sprintf('%s is required', $required));
            }
        }

        return $this->normalize($input);
    }

    /**
     * Validate a partial update payload (PATCH: absent = unchanged).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws InvalidPolicyPayloadException
     */
    public function validateForUpdate(array $input): array
    {
        if ($input === []) {
            throw new InvalidPolicyPayloadException('body', 'at least one updatable field is required');
        }

        $this->assertKnownKeys($input, self::UPDATE_KEYS);

        return $this->normalize($input);
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<string>  $allowed
     *
     * @throws InvalidPolicyPayloadException
     */
    private function assertKnownKeys(array $input, array $allowed): void
    {
        $unknown = array_diff(array_keys($input), $allowed);

        if ($unknown !== []) {
            throw new InvalidPolicyPayloadException(
                'body',
                sprintf('unknown fields: %s (strict validation rejects undeclared fields)', implode(', ', $unknown)),
            );
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws InvalidPolicyPayloadException
     */
    private function normalize(array $input): array
    {
        $normalized = [];

        if (array_key_exists('name', $input)) {
            $name = $input['name'];

            if (! is_string($name) || trim($name) === '' || strlen($name) > 120) {
                throw new InvalidPolicyPayloadException('name', 'name must be a non-empty string (max 120 chars)');
            }

            $normalized['name'] = $name;
        }

        if (array_key_exists('description', $input)) {
            $description = $input['description'];

            if ($description !== null && (! is_string($description) || strlen($description) > 2000)) {
                throw new InvalidPolicyPayloadException('description', 'description must be null or a string (max 2000 chars)');
            }

            $normalized['description'] = $description;
        }

        if (array_key_exists('effect', $input)) {
            $effect = Effect::tryFrom(is_string($input['effect']) ? $input['effect'] : '');

            if ($effect === null) {
                throw new InvalidPolicyPayloadException('effect', 'effect must be allow or deny');
            }

            $normalized['effect'] = $effect->value;
        }

        if (array_key_exists('capability_selector', $input)) {
            if (! is_array($input['capability_selector'])) {
                throw new InvalidPolicyPayloadException('capability_selector', 'capability_selector must be an object');
            }

            $normalized['capability_selector'] = CapabilitySelector::fromArray($input['capability_selector'])->toArray();
        }

        if (array_key_exists('scope_bindings', $input)) {
            $normalized['scope_bindings'] = $this->bindingList($input['scope_bindings']);
        }

        if (array_key_exists('conditions', $input)) {
            $normalized['conditions'] = $this->conditionList($input['conditions']);
        }

        if (array_key_exists('priority', $input)) {
            $priority = $input['priority'];

            if (! is_int($priority) || $priority < -1000 || $priority > 1000) {
                throw new InvalidPolicyPayloadException('priority', 'priority must be an integer between -1000 and 1000');
            }

            $normalized['priority'] = $priority;
        }

        return $normalized;
    }

    /**
     * @return list<array<string, mixed>>
     *
     * @throws InvalidPolicyPayloadException
     */
    private function bindingList(mixed $bindings): array
    {
        if ($bindings === null) {
            return [];
        }

        if (! is_array($bindings) || ! array_is_list($bindings)) {
            throw new InvalidPolicyPayloadException('scope_bindings', 'scope_bindings must be a list');
        }

        if (count($bindings) > 20) {
            throw new InvalidPolicyPayloadException('scope_bindings', 'scope_bindings is limited to 20 entries');
        }

        $normalized = [];

        foreach ($bindings as $binding) {
            if (! is_array($binding)) {
                throw new InvalidPolicyPayloadException('scope_bindings', 'each scope binding must be an object');
            }

            $normalized[] = ScopeBinding::fromArray($binding)->toArray();
        }

        return $normalized;
    }

    /**
     * @return list<array<string, mixed>>
     *
     * @throws InvalidPolicyPayloadException
     */
    private function conditionList(mixed $conditions): array
    {
        if ($conditions === null) {
            return [];
        }

        if (! is_array($conditions) || ! array_is_list($conditions)) {
            throw new InvalidPolicyPayloadException('conditions', 'conditions must be a list');
        }

        if (count($conditions) > 20) {
            throw new InvalidPolicyPayloadException('conditions', 'conditions is limited to 20 entries (bounded grammar, LD-15)');
        }

        $normalized = [];

        foreach ($conditions as $condition) {
            if (! is_array($condition)) {
                throw new InvalidPolicyPayloadException('conditions', 'each condition must be an object');
            }

            $normalized[] = Condition::fromArray($condition)->toArray();
        }

        return $normalized;
    }
}
