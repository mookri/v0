<?php

namespace Tests\Platform\Identity;

use Tests\KernelTestCase;

/**
 * ADR-0020 Phase 0: the federation route scope is host-gated and fails
 * closed. With no AUTH_DOMAIN configured (direct-mode default) the auth
 * surface does not exist; with a domain configured it answers only on
 * that exact host and never on the business host.
 */
final class FederationHostGateTest extends KernelTestCase
{
    public function test_federation_probe_is_absent_when_no_domain_configured(): void
    {
        config(['federation.domain' => '']);

        $this->get('http://auth.localhost/auth/health')->assertNotFound();
        $this->get('http://localhost/auth/health')->assertNotFound();
    }

    public function test_federation_probe_answers_on_configured_domain_only(): void
    {
        config([
            'federation.domain' => 'auth.localhost',
            'federation.issuer' => 'http://auth.localhost',
        ]);

        $this->get('http://auth.localhost/auth/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('issuer', 'http://auth.localhost')
            ->assertJsonPath('domain', 'auth.localhost');

        // Same path on the business host stays invisible (no oracle).
        $this->get('http://localhost/auth/health')->assertNotFound();
    }

    public function test_federation_probe_never_leaks_secrets(): void
    {
        config([
            'federation.domain' => 'auth.localhost',
            'federation.issuer' => 'http://auth.localhost',
        ]);

        $json = $this->get('http://auth.localhost/auth/health')
            ->assertOk()
            ->json();

        $this->assertSame(
            ['status', 'issuer', 'domain', 'mode', 'runtime', 'revocation_status_ttl_seconds', 'refresh_retry_window_seconds', 'access_token_ttl_seconds'],
            array_keys($json),
            'Federation probe payload must stay a fixed non-secret shape.'
        );
    }

    public function test_suffix_spoofed_host_stays_closed(): void
    {
        config([
            'federation.domain' => 'auth.localhost',
            'federation.issuer' => 'http://auth.localhost',
        ]);

        // The gate must exact-match the configured domain: hosts that
        // merely contain or suffix it (auth.localhost.evil.test) never
        // open the federation surface. Host-header routing itself is the
        // transport boundary — in prod the auth vhost is a separate
        // listener/TLS route, so the business origin never receives it.
        $this->get('http://auth.localhost.evil.test/auth/health')
            ->assertNotFound();
    }
}
