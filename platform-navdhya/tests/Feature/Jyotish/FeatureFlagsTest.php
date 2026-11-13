<?php

namespace Tests\Feature\Jyotish;

use App\Platform\Features\FeatureFlags;
use Tests\TenantIntegrationTestCase;

/**
 * Feature flag gating tests for Jyotish module (T-JY00-12).
 * Verifies AC-T-JY00-12: Disabling jyotish.enabled causes all /api/v1/jyotish/*
 * endpoints to return 404 Not Found.
 */
final class FeatureFlagsTest extends TenantIntegrationTestCase
{
    public function test_feature_flag_constants_defined(): void
    {
        $this->assertSame('jyotish.enabled', FeatureFlags::JYOTISH_ENABLED);
        $this->assertSame('jyotish.consumer_tools', FeatureFlags::JYOTISH_CONSUMER_TOOLS);
        $this->assertSame('jyotish.practitioner_studio', FeatureFlags::JYOTISH_PRACTITIONER_STUDIO);
        $this->assertSame('jyotish.specialist_schools', FeatureFlags::JYOTISH_SPECIALIST_SCHOOLS);
        $this->assertSame('jyotish.ai_interpretation', FeatureFlags::JYOTISH_AI_INTERPRETATION);
    }

    public function test_health_endpoint_available_when_feature_enabled(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/jyotish/health', $headers)
            ->assertOk();

        $this->assertSame('operational', $response->json('status'));
        $this->assertSame('jyotish', $response->json('module'));
    }

    public function test_endpoints_return_404_when_feature_disabled(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);

        // Explicitly disable jyotish.enabled for this tenant
        /** @var FeatureFlags $flags */
        $flags = $this->app->make(FeatureFlags::class);
        $flags->setOverride(FeatureFlags::JYOTISH_ENABLED, false, $tenant->id);

        $this->actingAs($user)
            ->getJson('/api/v1/jyotish/health', $headers)
            ->assertNotFound();

        $this->actingAs($user)
            ->getJson('/api/v1/jyotish/charts/ch_sample123', $headers)
            ->assertNotFound();
    }
}
