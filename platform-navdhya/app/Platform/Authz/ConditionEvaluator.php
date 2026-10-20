<?php

namespace App\Platform\Authz;

use App\Platform\Authz\Contracts\Condition;

/**
 * Evaluation of the bounded policy condition grammar (SEC-021, LD-15)
 * against a request's attribute set. Fail-closed: a condition whose
 * attribute is missing, mistyped, or outside the grammar fails, so a
 * policy whose conditions are not satisfied never matches a request.
 */
final class ConditionEvaluator
{
    /**
     * @param  list<array<string, mixed>>  $conditions
     * @param  array<string, mixed>  $attributes
     * @return array{0: bool, 1: list<array{field: string, operator: string, reason: string}>}
     */
    public function evaluate(array $conditions, array $attributes): array
    {
        $failures = [];

        foreach ($conditions as $condition) {
            $field = is_string($condition['field'] ?? null) ? (string) $condition['field'] : '';
            $operator = is_string($condition['operator'] ?? null) ? (string) $condition['operator'] : '';

            if (! in_array($field, Condition::FIELDS, true)
                || ! in_array($operator, Condition::OPERATORS, true)
                || ! array_key_exists('value', $condition)) {
                $failures[] = ['field' => $field, 'operator' => $operator, 'reason' => 'malformed_condition'];

                continue;
            }

            if (! array_key_exists($field, $attributes)) {
                $failures[] = ['field' => $field, 'operator' => $operator, 'reason' => 'attribute_missing'];

                continue;
            }

            if (! $this->compare($field, $operator, $attributes[$field], $condition['value'])) {
                $failures[] = ['field' => $field, 'operator' => $operator, 'reason' => 'condition_unsatisfied'];
            }
        }

        return [$failures === [], $failures];
    }

    private function compare(string $field, string $operator, mixed $actual, mixed $expected): bool
    {
        if ($operator === 'in' || $operator === 'not_in') {
            if (! is_array($expected) || ! array_is_list($expected)) {
                return false;
            }

            $member = false;

            foreach ($expected as $item) {
                if ($this->compareScalar($field, 'eq', $actual, $item)) {
                    $member = true;

                    break;
                }
            }

            return $operator === 'in' ? $member : ! $member;
        }

        return $this->compareScalar($field, $operator, $actual, $expected);
    }

    private function compareScalar(string $field, string $operator, mixed $actual, mixed $expected): bool
    {
        if ($field === 'amount') {
            if (! $this->isNumeric($actual) || ! $this->isNumeric($expected)) {
                return false;
            }

            return $this->compareNumbers((float) $actual, $operator, (float) $expected);
        }

        if ($field === 'time') {
            if (! is_string($actual) || ! is_string($expected)) {
                return false;
            }

            $actualTs = strtotime($actual);
            $expectedTs = strtotime($expected);

            if ($actualTs === false || $expectedTs === false) {
                return false;
            }

            return $this->compareNumbers($actualTs, $operator, $expectedTs);
        }

        // currency | org | status: equality-class operators over strings;
        // ordering operators are not meaningful on these fields and fail
        // closed.
        if (! is_string($actual) || ! is_string($expected)) {
            return false;
        }

        return match ($operator) {
            'eq' => $actual === $expected,
            'neq' => $actual !== $expected,
            default => false,
        };
    }

    private function compareNumbers(float|int $actual, string $operator, float|int $expected): bool
    {
        return match ($operator) {
            'eq' => $actual == $expected,
            'neq' => $actual != $expected,
            'gt' => $actual > $expected,
            'gte' => $actual >= $expected,
            'lt' => $actual < $expected,
            'lte' => $actual <= $expected,
            default => false,
        };
    }

    private function isNumeric(mixed $value): bool
    {
        return is_int($value) || is_float($value) || (is_string($value) && is_numeric($value));
    }
}
