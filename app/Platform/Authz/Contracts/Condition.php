<?php

namespace App\Platform\Authz\Contracts;

use App\Platform\Authz\Exceptions\InvalidPolicyPayloadException;

/**
 * One condition clause of the bounded policy condition grammar (SEC-021,
 * LD-15): a closed set of comparison operators over declared fields
 * (amount, currency, org, time, status). Free-form expression languages
 * are banned — anything outside this shape is rejected at validation.
 */
final class Condition
{
    public const FIELDS = ['amount', 'currency', 'org', 'time', 'status'];

    public const OPERATORS = ['eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'in', 'not_in'];

    public function __construct(
        public readonly string $field,
        public readonly string $operator,
        public readonly mixed $value,
    ) {
    }

    /**
     * @param  array<string, mixed>  $condition
     *
     * @throws InvalidPolicyPayloadException
     */
    public static function fromArray(array $condition): self
    {
        $unknown = array_diff(array_keys($condition), ['field', 'operator', 'value']);

        if ($unknown !== []) {
            throw new InvalidPolicyPayloadException(
                'conditions',
                sprintf('unknown condition keys: %s (grammar is {field, operator, value})', implode(', ', $unknown)),
            );
        }

        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? null;

        if (! is_string($field) || ! in_array($field, self::FIELDS, true)) {
            throw new InvalidPolicyPayloadException(
                'conditions',
                sprintf('field must be one of %s', implode('|', self::FIELDS)),
            );
        }

        if (! is_string($operator) || ! in_array($operator, self::OPERATORS, true)) {
            throw new InvalidPolicyPayloadException(
                'conditions',
                sprintf('operator must be one of %s', implode('|', self::OPERATORS)),
            );
        }

        if (! array_key_exists('value', $condition)) {
            throw new InvalidPolicyPayloadException('conditions', 'value is required');
        }

        self::assertValueType($field, $operator, $condition['value']);

        return new self($field, $operator, $condition['value']);
    }

    /**
     * @throws InvalidPolicyPayloadException
     */
    private static function assertValueType(string $field, string $operator, mixed $value): void
    {
        if ($operator === 'in' || $operator === 'not_in') {
            if (! is_array($value) || $value === [] || ! array_is_list($value)) {
                throw new InvalidPolicyPayloadException('conditions', sprintf("operator '%s' requires a non-empty list value", $operator));
            }

            foreach ($value as $item) {
                self::assertScalarValue($field, $item);
            }

            return;
        }

        self::assertScalarValue($field, $value);
    }

    /**
     * @throws InvalidPolicyPayloadException
     */
    private static function assertScalarValue(string $field, mixed $value): void
    {
        switch ($field) {
            case 'amount':
                if (! is_int($value) && ! is_float($value) && ! (is_string($value) && is_numeric($value))) {
                    throw new InvalidPolicyPayloadException('conditions', 'amount must be numeric');
                }

                return;

            case 'currency':
                if (! is_string($value) || preg_match('/^[A-Z]{3}$/', $value) !== 1) {
                    throw new InvalidPolicyPayloadException('conditions', 'currency must be a 3-letter uppercase code');
                }

                return;

            case 'time':
                if (! is_string($value) || strtotime($value) === false) {
                    throw new InvalidPolicyPayloadException('conditions', 'time must be a parseable timestamp string');
                }

                return;

            case 'org':
            case 'status':
                if (! is_string($value) || $value === '' || strlen($value) > 64) {
                    throw new InvalidPolicyPayloadException('conditions', sprintf('%s must be a non-empty string (max 64 chars)', $field));
                }

                return;
        }
    }

    /**
     * @return array{field: string, operator: string, value: mixed}
     */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'operator' => $this->operator,
            'value' => $this->value,
        ];
    }
}
