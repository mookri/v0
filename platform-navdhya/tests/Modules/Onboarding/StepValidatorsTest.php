<?php

namespace Tests\Modules\Onboarding;

use App\Modules\Onboarding\StepValidators;
use Tests\TenantIntegrationTestCase;

/**
 * Per-category evidence validators (field catalog → code): shape, format
 * and business rules per step, field-keyed reasons, unknown steps rejected.
 */
final class StepValidatorsTest extends TenantIntegrationTestCase
{
    public function test_identity_rules(): void
    {
        $this->assertSame([], StepValidators::validate('identity', ['name' => 'Ravi Shankar', 'phone' => '+919876543210']));
        $this->assertSame(
            ['name' => ['name_must_be_2_100_chars'], 'phone' => ['phone_must_be_e164']],
            StepValidators::validate('identity', ['name' => 'x', 'phone' => '98765'])
        );
        $this->assertSame(['email' => ['email_invalid']], StepValidators::validate(
            'identity',
            ['name' => 'Ravi Shankar', 'phone' => '+919876543210', 'email' => 'nope']
        ));
        $this->assertSame(['identity' => ['must_be_object']], StepValidators::validate('identity', 'x'));
    }

    public function test_lists_business_capacity_agreement_rules(): void
    {
        $this->assertSame([], StepValidators::validate('services', ['a', 'b']));
        $this->assertSame(['services' => ['must_be_non_empty_list']], StepValidators::validate('services', []));
        $this->assertSame(
            ['gstin' => ['gstin_must_be_15_chars'], 'bank_account' => ['bank_account_required']],
            StepValidators::validate('business', ['gstin' => 'short', 'bankAccount' => ''])
        );
        $this->assertSame(
            ['gstin' => ['INVALID_EMBEDDED_PAN', 'INVALID_CHECK_DIGIT']],
            StepValidators::validate('business', ['gstin' => '29ABCDE1234F1Z5', 'bank_account' => '1234567890'])
        );
        $this->assertSame([], StepValidators::validate('business', ['gstin' => '29AABCT1234F1ZM', 'bank_account' => '1234567890']));
        $this->assertSame([], StepValidators::validate('business', ['gst_status' => 'exempt_below_threshold', 'bank_account' => '1234567890']));
        $this->assertSame([], StepValidators::validate('capacity', ['weeklySlots' => 10]));
        $this->assertSame(
            ['weekly_slots' => ['weekly_slots_must_be_positive_integer']],
            StepValidators::validate('capacity', ['weeklySlots' => 0])
        );
        $this->assertSame([], StepValidators::validate('agreement', ['accepted' => true]));
        $this->assertSame(['agreement' => ['must_be_accepted']], StepValidators::validate('agreement', ['accepted' => false]));
        $this->assertSame([], StepValidators::validate('review', []));
        $this->assertSame(['step' => ['unknown_step']], StepValidators::validate('nope', []));
    }

    public function test_step_put_rejects_bad_shapes_over_http(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);
        $started = $this->actingAs($user)->postJson('/api/v1/onboarding/applications', ['track' => 'pandit'], $headers);
        $started->assertCreated();
        $id = $started->json('data.id');

        $this->actingAs($user)->putJson("/api/v1/onboarding/applications/{$id}/step/identity", ['value' => ['name' => 'x']], $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['details' => ['fields' => ['name', 'phone']]]]);

        $this->actingAs($user)->putJson(
            "/api/v1/onboarding/applications/{$id}/step/identity",
            ['value' => ['name' => 'Ravi Shankar', 'phone' => '+919876543210']],
            $headers
        )->assertOk();
    }
}
