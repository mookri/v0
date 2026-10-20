<?php

namespace Tests\Platform\Observability;

use Tests\KernelTestCase;

/**
 * Health & readiness endpoints (doc 05 §11.2, doc 06 §11).
 */
final class HealthTest extends KernelTestCase
{
    public function test_up_reports_process_alive(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_ready_passes_when_all_dependencies_are_healthy(): void
    {
        $response = $this->getJson('/ready');

        $response->assertOk();
        $response->assertJsonPath('status', 'ready');
        $response->assertJsonPath('checks.central_database', 'ok');
        $response->assertJsonPath('checks.tenant_database', 'ok');
        $response->assertJsonPath('checks.cache', 'ok');
        $response->assertJsonPath('checks.queue', 'ok');
        $response->assertJsonPath('checks.outbox_relay', 'ok');
    }
}
