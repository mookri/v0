<?php

declare(strict_types=1);

namespace Tests\Modules\Incident;

use Tests\TenantIntegrationTestCase;

final class IncidentModuleSmokeTest extends TenantIntegrationTestCase
{
    public function test_incident_module_health_and_discovery(): void
    {
        $this->getJson('/api/v1/incident/health')
            ->assertOk()
            ->assertJsonPath('data.module', 'incident');
    }
}

