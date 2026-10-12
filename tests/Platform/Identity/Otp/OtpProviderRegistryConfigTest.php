<?php

namespace Tests\Platform\Identity\Otp;

use App\Platform\Identity\Otp\FirebasePhoneVerificationProvider;
use App\Platform\Identity\Otp\OtpProviderRegistry;
use App\Platform\Identity\Otp\OtpProviderUnavailableException;
use Tests\TestCase;

/**
 * S47 config wiring: config/otp-providers.php -> OtpProviderRegistry.
 * Defaults keep every audience local (behavior-identical); a firebase
 * audience registers firebase-<audience> only with a complete
 * credential set, and an incomplete one registers nothing so dispatch
 * fails closed — never a downgrade.
 */
final class OtpProviderRegistryConfigTest extends TestCase
{
    private function registry(): OtpProviderRegistry
    {
        $this->app->forgetInstance(OtpProviderRegistry::class);

        return $this->app->make(OtpProviderRegistry::class);
    }

    public function test_default_config_registers_no_providers(): void
    {
        $this->assertSame('local', config('otp-providers.audiences.customer.provider'));
        $this->assertSame('local', config('otp-providers.audiences.vendor.provider'));
        $this->assertSame('local', config('otp-providers.audiences.platform.provider'));

        $registry = $this->registry();
        $this->assertFalse($registry->has('firebase-customer'));
        $this->assertFalse($registry->has('firebase-vendor'));
        $this->expectException(OtpProviderUnavailableException::class);
        $registry->get('firebase-customer');
    }

    public function test_firebase_audience_with_credentials_registers_named_provider(): void
    {
        config([
            'otp-providers.audiences.customer' => [
                'provider' => 'firebase',
                'firebase' => [
                    'project' => 'navdhya-customer',
                    'api_key' => 'k1',
                    'timeout_seconds' => 7,
                ],
            ],
        ]);

        $registry = $this->registry();
        $this->assertTrue($registry->has('firebase-customer'));
        $this->assertFalse($registry->has('firebase-vendor'));
        $this->assertInstanceOf(FirebasePhoneVerificationProvider::class, $registry->get('firebase-customer'));
        $this->assertSame('firebase-customer', $registry->get('firebase-customer')->name());
    }

    public function test_both_audiences_can_register_on_separate_projects(): void
    {
        config([
            'otp-providers.audiences.customer' => [
                'provider' => 'firebase',
                'firebase' => ['project' => 'p-customer', 'api_key' => 'kc'],
            ],
            'otp-providers.audiences.vendor' => [
                'provider' => 'firebase',
                'firebase' => ['project' => 'p-vendor', 'api_key' => 'kv'],
            ],
        ]);

        $registry = $this->registry();
        $this->assertTrue($registry->has('firebase-customer'));
        $this->assertTrue($registry->has('firebase-vendor'));
    }

    public function test_incomplete_credentials_register_nothing_fail_closed(): void
    {
        config([
            'otp-providers.audiences.customer' => [
                'provider' => 'firebase',
                'firebase' => ['project' => 'navdhya-customer', 'api_key' => ''],
            ],
        ]);

        $registry = $this->registry();
        $this->assertFalse($registry->has('firebase-customer'));
        $this->expectException(OtpProviderUnavailableException::class);
        $registry->get('firebase-customer');
    }
}
