<?php

namespace Tests\Platform\Identity;

use App\Platform\Identity\Federation\AuthEventRecorder;
use App\Platform\Identity\Federation\Models\AuthEvent;
use App\Platform\Identity\OAuth\TokenReuseDetected;
use Tests\TenantIntegrationTestCase;

/**
 * ADR-0020 D5: auth_events are append-only, hash-chained, correlated
 * and structurally non-secret. D4: refresh-token reuse always emits
 * the audit event, even when the security response (lineage kill)
 * already happened.
 */
final class AuthEventChainTest extends TenantIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['identity.hmac_secret' => 'test-hmac-secret-for-chain']);
    }

    public function test_recorded_events_form_a_verifiable_chain(): void
    {
        $recorder = app(AuthEventRecorder::class);

        $recorder->record([
            'event_type' => 'oauth.token_issued',
            'decision' => 'allow',
            'subject_id' => 'user-1',
            'session_id' => 'sess-1',
            'token_jti' => 'jti-1',
            'grant_type' => 'authorization_code',
        ]);
        $recorder->record([
            'event_type' => 'oauth.token_exchange',
            'decision' => 'allow',
            'subject_id' => 'user-1',
            'grant_type' => 'token_exchange',
            'source_audience' => 'customer-api',
            'target_audience' => 'partner-api',
            'requested_scopes' => ['records:read'],
            'granted_scopes' => ['records:read'],
        ]);

        $this->artisan('auth:verify-chain')
            ->expectsOutputToContain('Chain intact: 2 events verified.')
            ->assertExitCode(0);
    }

    public function test_tampered_row_breaks_the_chain(): void
    {
        $recorder = app(AuthEventRecorder::class);
        $recorder->record([
            'event_type' => 'oauth.token_issued',
            'decision' => 'allow',
            'subject_id' => 'user-1',
        ]);
        $second = $recorder->record([
            'event_type' => 'session.revoke',
            'decision' => 'allow',
            'subject_id' => 'user-1',
        ]);

        // Simulate a privileged attacker flipping a decision after write.
        AuthEvent::query()->withoutGlobalScopes()
            ->where('id', $second->id)
            ->update(['decision' => 'deny']);

        $this->artisan('auth:verify-chain')
            ->expectsOutputToContain('seal mismatch')
            ->assertExitCode(1);
    }

    public function test_deleted_row_breaks_the_chain(): void
    {
        $recorder = app(AuthEventRecorder::class);
        $first = $recorder->record([
            'event_type' => 'oauth.token_issued',
            'decision' => 'allow',
        ]);
        $recorder->record([
            'event_type' => 'session.revoke',
            'decision' => 'allow',
        ]);

        AuthEvent::query()->withoutGlobalScopes()->where('id', $first->id)->delete();

        $this->artisan('auth:verify-chain')
            ->expectsOutputToContain('prev_hash discontinuity')
            ->assertExitCode(1);
    }

    public function test_identifiers_are_hmac_pseudonymous_never_raw(): void
    {
        app(AuthEventRecorder::class)->record([
            'event_type' => 'oauth.token_issued',
            'decision' => 'allow',
            'subject_id' => 'user-raw-123',
            'session_id' => 'sess-raw-456',
            'token_jti' => 'jti-raw-789',
        ]);

        $row = AuthEvent::query()->withoutGlobalScopes()->firstOrFail();

        $this->assertStringStartsWith('sub:', (string) $row->subject_ref);
        $this->assertStringStartsWith('sess:', (string) $row->session_ref);
        $this->assertStringStartsWith('jti:', (string) $row->token_jti_ref);

        $dump = (string) json_encode($row->getAttributes());
        $this->assertStringNotContainsString('user-raw-123', $dump);
        $this->assertStringNotContainsString('sess-raw-456', $dump);
        $this->assertStringNotContainsString('jti-raw-789', $dump);
    }

    public function test_recorder_fails_closed_without_pseudonymization_secret(): void
    {
        config(['identity.hmac_secret' => '']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HMAC-pseudonymous');

        app(AuthEventRecorder::class)->record([
            'event_type' => 'oauth.token_issued',
            'decision' => 'allow',
            'subject_id' => 'user-1',
        ]);
    }

    public function test_refresh_reuse_emits_the_audit_event(): void
    {
        // No fake: the listener runs synchronously and must write the
        // chain row — D4 (family kill) and D5 (audit) always travel
        // together.
        event(new TokenReuseDetected('user-42', 'client-7'));

        $row = AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'session.reuse_detected')
            ->firstOrFail();

        $this->assertSame('deny', $row->decision);
        $this->assertSame('refresh_token_reuse', $row->reason_code);
        $this->assertSame('client-7', $row->actor_client_id);

        $this->artisan('auth:verify-chain')->assertExitCode(0);
    }
}
