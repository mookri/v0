<?php

namespace Tests\Platform\Authz;

use App\Platform\Authz\Exceptions\InvalidPolicyPayloadException;
use App\Platform\Authz\PolicyValidator;
use PHPUnit\Framework\TestCase;

/**
 * Policy grammar validation (SEC-021, LD-15, T-0K01-01.3): effect,
 * capability selector shape, scope bindings, and the bounded declarative
 * condition grammar — free-form expression languages are banned.
 */
final class PolicyValidatorTest extends TestCase
{
    private PolicyValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new PolicyValidator();
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'cap-approvals-over-10k',
            'description' => 'Deny approvals above 10k',
            'effect' => 'deny',
            'capability_selector' => ['module' => 'sales', 'resource' => 'order', 'action' => 'approve'],
            'scope_bindings' => [
                ['scope_type' => 'organization', 'scope_id' => 'org_1', 'scope_mode' => 'SUBTREE'],
            ],
            'conditions' => [
                ['field' => 'amount', 'operator' => 'gt', 'value' => 10000],
            ],
            'priority' => 10,
        ];
    }

    public function test_valid_payload_is_accepted_and_normalized(): void
    {
        $normalized = $this->validator->validateForStore($this->validPayload());

        $this->assertSame('deny', $normalized['effect']);
        $this->assertSame(
            ['module' => 'sales', 'resource' => 'order', 'action' => 'approve'],
            $normalized['capability_selector'],
        );
        $this->assertSame('SUBTREE', $normalized['scope_bindings'][0]['scope_mode']);
        $this->assertSame(10, $normalized['priority']);
    }

    public function test_wildcard_selector_segments_are_accepted(): void
    {
        $payload = $this->validPayload();
        $payload['capability_selector'] = ['module' => '*', 'resource' => 'order', 'action' => '*'];

        $normalized = $this->validator->validateForStore($payload);

        $this->assertSame('*', $normalized['capability_selector']['module']);
        $this->assertSame('*', $normalized['capability_selector']['action']);
    }

    public function test_effect_must_be_allow_or_deny(): void
    {
        $payload = $this->validPayload();
        $payload['effect'] = 'maybe';

        $this->expectFieldViolation('effect', $payload);
    }

    public function test_required_fields_are_enforced(): void
    {
        $payload = $this->validPayload();
        unset($payload['capability_selector']);

        $this->expectViolation($payload, 'capability_selector');
    }

    public function test_unknown_top_level_fields_are_rejected_strictly(): void
    {
        $payload = $this->validPayload();
        $payload['script'] = 'if (amount > 10) deny';

        $this->expectViolation($payload, 'body');
    }

    public function test_selector_rejects_bad_segment_shapes(): void
    {
        $cases = [
            [['module' => 'Sales', 'resource' => 'order', 'action' => 'view'], 'capability_selector'],
            [['module' => 'sales', 'resource' => '', 'action' => 'view'], 'capability_selector'],
            [['module' => 'sales', 'action' => 'view'], 'capability_selector'],
            [['module' => 'sales', 'resource' => 'order', 'action' => 'view', 'extra' => 1], 'capability_selector'],
        ];

        foreach ($cases as [$selector, $field]) {
            $payload = $this->validPayload();
            $payload['capability_selector'] = $selector;

            $this->expectFieldViolation($field, $payload);
        }
    }

    public function test_condition_grammar_rejects_free_form_expressions(): void
    {
        $cases = [
            // Scripting-style value object on a scalar operator.
            [['field' => 'amount', 'operator' => 'eq', 'value' => ['expr' => 'cart.total * 1.1']], 'conditions'],
            // Unknown operator (closed set only).
            [['field' => 'amount', 'operator' => 'regex', 'value' => '^[0-9]+$'], 'conditions'],
            // Undeclared field.
            [['field' => 'cart_total', 'operator' => 'gt', 'value' => 10], 'conditions'],
            // Extra keys beyond {field, operator, value}.
            [['field' => 'amount', 'operator' => 'gt', 'value' => 1, 'lambda' => true], 'conditions'],
            // Missing value.
            [['field' => 'amount', 'operator' => 'gt'], 'conditions'],
            // 'in' requires a non-empty list.
            [['field' => 'status', 'operator' => 'in', 'value' => 'open'], 'conditions'],
        ];

        foreach ($cases as [$condition, $field]) {
            $payload = $this->validPayload();
            $payload['conditions'] = [$condition];

            $this->expectFieldViolation($field, $payload);
        }
    }

    public function test_condition_value_types_are_enforced_per_field(): void
    {
        $cases = [
            ['amount', 'eq', 'not-a-number'],
            ['currency', 'eq', 'inr'],
            ['currency', 'eq', 'EURO'],
            ['time', 'lt', 'never-ish'],
            ['org', 'eq', ''],
            ['status', 'in', ['ok', ['nested']]],
        ];

        foreach ($cases as [$field, $operator, $value]) {
            $payload = $this->validPayload();
            $payload['conditions'] = [['field' => $field, 'operator' => $operator, 'value' => $value]];

            $this->expectFieldViolation('conditions', $payload);
        }
    }

    public function test_valid_condition_shapes_are_accepted(): void
    {
        $payload = $this->validPayload();
        $payload['conditions'] = [
            ['field' => 'amount', 'operator' => 'gte', 'value' => '1000.50'],
            ['field' => 'currency', 'operator' => 'eq', 'value' => 'INR'],
            ['field' => 'org', 'operator' => 'in', 'value' => ['org_1', 'org_2']],
            ['field' => 'time', 'operator' => 'lt', 'value' => '2026-12-31T23:59:59Z'],
            ['field' => 'status', 'operator' => 'not_in', 'value' => ['void']],
        ];

        $normalized = $this->validator->validateForStore($payload);

        $this->assertCount(5, $normalized['conditions']);
    }

    public function test_scope_binding_grammar_is_enforced(): void
    {
        $cases = [
            [['scope_type' => 'Organization', 'scope_id' => 'org_1']],
            [['scope_type' => 'organization']],
            [['scope_type' => 'organization', 'scope_id' => 'org_1', 'scope_mode' => 'EVERYTHING']],
            [['scope_type' => 'organization', 'scope_id' => 'org_1', 'extra' => true]],
        ];

        foreach ($cases as $binding) {
            $payload = $this->validPayload();
            $payload['scope_bindings'] = [$binding];

            $this->expectFieldViolation('scope_bindings', $payload);
        }
    }

    public function test_priority_bounds_are_enforced(): void
    {
        foreach ([1001, -1001, 'high'] as $priority) {
            $payload = $this->validPayload();
            $payload['priority'] = $priority;

            $this->expectFieldViolation('priority', $payload);
        }
    }

    public function test_update_requires_at_least_one_field(): void
    {
        $this->expectViolation([], 'body', method: 'validateForUpdate');
    }

    public function test_update_accepts_partial_payloads(): void
    {
        $normalized = $this->validator->validateForUpdate(['description' => 'new note']);

        $this->assertSame(['description' => 'new note'], $normalized);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function expectViolation(array $payload, string $expectedField, string $method = 'validateForStore'): void
    {
        try {
            $this->validator->{$method}($payload);
            $this->fail('Expected InvalidPolicyPayloadException.');
        } catch (InvalidPolicyPayloadException $exception) {
            $this->assertSame($expectedField, $exception->field);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function expectFieldViolation(string $expectedField, array $payload): void
    {
        $this->expectViolation($payload, $expectedField);
    }
}
