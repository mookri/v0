<?php

namespace Tests\Modules\Search;

use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

class TypingTelemetryTest extends TenantIntegrationTestCase
{
    public function test_typing_telemetry_records_settled_session(): void
    {
        // Force 100% sample rate for test
        config(['search.telemetry.typing_sample_rate' => 1.0]);

        $payload = [
            'session_id' => (string) Str::ulid(),
            'trace_id' => (string) Str::ulid(),
            'query_text' => 'Rudrabhishek',
            'keystroke_count' => 12,
            'backspace_count' => 2,
            'ime_composition' => false,
            'time_to_first_impression_ms' => 180,
            'total_typing_ms' => 1250,
        ];

        $response = $this->postJson('/api/v1/search/typing-telemetry', $payload);
        $response->assertOk();
        $this->assertSame('recorded', $response->json('status'));

        $this->assertDatabaseHas('search_typed_sessions', [
            'session_id' => $payload['session_id'],
            'query_text' => 'Rudrabhishek',
            'keystroke_count' => 12,
            'backspace_count' => 2,
        ], 'central');
    }
}
