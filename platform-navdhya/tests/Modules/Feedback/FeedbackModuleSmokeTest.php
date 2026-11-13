<?php

declare(strict_types=1);

namespace Tests\Modules\Feedback;

use Tests\TenantIntegrationTestCase;

final class FeedbackModuleSmokeTest extends TenantIntegrationTestCase
{
    public function test_feedback_module_health_and_discovery(): void
    {
        $this->getJson('/api/v1/feedback/health')
            ->assertOk()
            ->assertJsonPath('data.module', 'feedback');
    }
}

