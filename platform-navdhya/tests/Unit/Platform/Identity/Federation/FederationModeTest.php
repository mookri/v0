<?php

namespace Tests\Unit\Platform\Identity\Federation;

use App\Platform\Identity\Federation\FederationConfigurationException;
use App\Platform\Identity\Federation\FederationMode;
use PHPUnit\Framework\TestCase;

/**
 * AUTH_MODE (ADR-0020) fails closed: only `direct` and `federated`
 * exist, and `federated` without a domain+issuer is a broken
 * deployment (every app redirect would dead-end at the host gate).
 */
final class FederationModeTest extends TestCase
{
    public function test_direct_mode_is_valid_standalone(): void
    {
        FederationMode::assertValid('direct');
        FederationMode::assertValid('direct', '', '');

        $this->addToAssertionCount(2);
    }

    public function test_federated_mode_is_valid_with_domain_and_issuer(): void
    {
        FederationMode::assertValid('federated', 'auth.localhost', 'http://auth.localhost');

        $this->addToAssertionCount(1);
    }

    public function test_any_other_mode_value_is_rejected(): void
    {
        $this->expectException(FederationConfigurationException::class);

        FederationMode::assertValid('halfway');
    }

    public function test_federated_without_a_domain_is_rejected(): void
    {
        $this->expectException(FederationConfigurationException::class);

        FederationMode::assertValid('federated', '', 'http://auth.localhost');
    }

    public function test_federated_without_an_issuer_is_rejected(): void
    {
        $this->expectException(FederationConfigurationException::class);

        FederationMode::assertValid('federated', 'auth.localhost', '');
    }
}
