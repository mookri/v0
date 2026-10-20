<?php

namespace Tests\Platform\Identity;

use App\Platform\Identity\Federation\FederationConfigurationException;
use App\Platform\Identity\Federation\RuntimeRole;
use Tests\KernelTestCase;

/**
 * Runtime separation (ADR-0020 D6): the same repository runs as an
 * independently configured runtime. The issuer runtime serves the
 * federation surface and never a competing login surface (D1 sole
 * issuer); an application runtime never serves the federation surface
 * — even on the auth host — and drops the legacy login once federated
 * (cutover committed). The HMAC sunset (P8): AUTH_MODE=federated
 * closes the strangler window for every runtime — no legacy login
 * surface exists anywhere in a federated deployment. All enforcement
 * is per-request, fail-closed with a uniform 404.
 */
final class RuntimeSeparationTest extends KernelTestCase
{
    private const AUTH_HOST = 'http://auth.localhost';

    private function configureFederated(): void
    {
        config([
            'federation.mode' => 'federated',
            'federation.domain' => 'auth.localhost',
            'federation.issuer' => 'http://auth.localhost',
        ]);
    }

    public function test_federation_surface_serves_on_the_issuer_runtime(): void
    {
        config(['federation.runtime' => 'authorization_server']);
        $this->configureFederated();

        $this->get(self::AUTH_HOST.'/auth/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('mode', 'federated')
            ->assertJsonPath('runtime', 'authorization_server');

        $this->get(self::AUTH_HOST.'/auth/login')->assertOk();
    }

    public function test_federation_surface_never_serves_on_an_application_runtime(): void
    {
        config(['federation.runtime' => 'application']);
        $this->configureFederated();

        // Even on the auth host: a mispointed vhost or copied env must
        // never expose the issuer surface on a business runtime.
        $this->get(self::AUTH_HOST.'/auth/health')->assertNotFound();
        $this->get(self::AUTH_HOST.'/auth/login')->assertNotFound();
        // The business host stays closed as always (no oracle).
        $this->get('http://localhost/auth/health')->assertNotFound();
    }

    public function test_legacy_login_is_absent_on_the_issuer_runtime(): void
    {
        config(['federation.runtime' => 'authorization_server']);
        $this->configureFederated();

        // The issuer is sole (D1): no credential-verification surface
        // of the old identity path exists here, uniformly 404.
        $this->postJson('/api/v1/customers/auth/otp')->assertNotFound();
        $this->postJson('/api/v1/customers/auth/login')->assertNotFound();
        $this->postJson('/api/v1/customers/profile:reactivate')->assertNotFound();
    }

    public function test_legacy_login_is_absent_on_a_federated_application_runtime(): void
    {
        config(['federation.runtime' => 'application']);
        $this->configureFederated();

        // Cutover committed: the app runtime redirects to the auth
        // subdomain instead of minting identity locally.
        $this->postJson('/api/v1/customers/auth/otp')->assertNotFound();
    }

    public function test_legacy_login_windows_open_in_direct_and_close_at_the_sunset(): void
    {
        // The strangler window (direct mode): the login route is live
        // past the gate on every non-issuer runtime — a validation
        // error (422, tenant_id missing) proves the controller was
        // reached.
        config(['federation.runtime' => 'monolith', 'federation.mode' => 'direct']);
        $this->postJson('/api/v1/customers/auth/otp', [])->assertStatus(422);

        config(['federation.runtime' => 'application', 'federation.mode' => 'direct']);
        $this->postJson('/api/v1/customers/auth/otp', [])->assertStatus(422);

        // The HMAC sunset (P8): AUTH_MODE=federated closes the window
        // for EVERY runtime — one issuer, no second identity surface
        // anywhere.
        config(['federation.runtime' => 'monolith']);
        $this->configureFederated();
        $this->postJson('/api/v1/customers/auth/otp')->assertNotFound();
    }

    public function test_unknown_runtime_fails_closed(): void
    {
        config(['federation.runtime' => 'halfway']);

        $this->expectException(FederationConfigurationException::class);
        RuntimeRole::current();
    }

    public function test_issuer_runtime_with_direct_mode_fails_closed(): void
    {
        config(['federation.runtime' => 'authorization_server', 'federation.mode' => 'direct']);

        $this->expectException(FederationConfigurationException::class);
        RuntimeRole::current();
    }
}
