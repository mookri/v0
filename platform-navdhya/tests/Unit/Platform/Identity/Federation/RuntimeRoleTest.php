<?php

namespace Tests\Unit\Platform\Identity\Federation;

use App\Platform\Identity\Federation\FederationConfigurationException;
use App\Platform\Identity\Federation\RuntimeRole;
use PHPUnit\Framework\TestCase;

/**
 * AUTH_RUNTIME (ADR-0020 D6) fails closed: only monolith,
 * authorization_server and application exist; the issuer runtime must
 * pair with federated mode and signing-key custody. The serves*
 * helpers are the per-request surface rules the gates enforce.
 */
final class RuntimeRoleTest extends TestCase
{
    public function test_monolith_is_valid_standalone(): void
    {
        RuntimeRole::assertValid('monolith', 'direct');
        RuntimeRole::assertValid('monolith', 'federated');

        $this->addToAssertionCount(2);
    }

    public function test_application_is_valid_in_both_modes(): void
    {
        RuntimeRole::assertValid('application', 'direct');
        RuntimeRole::assertValid('application', 'federated');

        $this->addToAssertionCount(2);
    }

    public function test_authorization_server_is_valid_federated_with_a_signing_key(): void
    {
        RuntimeRole::assertValid('authorization_server', 'federated', true);

        $this->addToAssertionCount(1);
    }

    public function test_any_other_role_is_rejected(): void
    {
        $this->expectException(FederationConfigurationException::class);

        RuntimeRole::assertValid('edge', 'direct');
    }

    public function test_issuer_runtime_requires_federated_mode(): void
    {
        $this->expectException(FederationConfigurationException::class);

        RuntimeRole::assertValid('authorization_server', 'direct');
    }

    public function test_issuer_runtime_requires_signing_key_custody(): void
    {
        $this->expectException(FederationConfigurationException::class);

        RuntimeRole::assertValid('authorization_server', 'federated', false);
    }

    public function test_only_application_runtimes_drop_the_federation_surface(): void
    {
        $this->assertTrue(RuntimeRole::servesFederationSurface('monolith'));
        $this->assertTrue(RuntimeRole::servesFederationSurface('authorization_server'));
        $this->assertFalse(RuntimeRole::servesFederationSurface('application'));
    }

    public function test_legacy_login_surface_matrix(): void
    {
        // The strangler window (direct mode): the monolith and a
        // direct-mode application keep the tenant login.
        $this->assertTrue(RuntimeRole::servesLegacyLoginSurface('monolith', 'direct'));
        $this->assertTrue(RuntimeRole::servesLegacyLoginSurface('application', 'direct'));

        // The HMAC sunset (P8): AUTH_MODE=federated closes the window
        // for EVERY runtime — the issuer is sole (D1).
        $this->assertFalse(RuntimeRole::servesLegacyLoginSurface('monolith', 'federated'));
        $this->assertFalse(RuntimeRole::servesLegacyLoginSurface('application', 'federated'));
        $this->assertFalse(RuntimeRole::servesLegacyLoginSurface('authorization_server', 'federated'));

        // The issuer runtime never serves it in any mode.
        $this->assertFalse(RuntimeRole::servesLegacyLoginSurface('authorization_server', 'direct'));
    }
}
