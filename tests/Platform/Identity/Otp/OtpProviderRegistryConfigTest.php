<?php

namespace Tests\Platform\Identity\Otp;

use App\Platform\Identity\Otp\FirebasePhoneVerificationProvider;
use App\Platform\Identity\Otp\OtpProviderConfigurationException;
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

    public function test_admin_credentials_are_optional_and_absent_stays_ok(): void
    {
        config([
            'otp-providers.audiences.customer' => [
                'provider' => 'firebase',
                'firebase' => [
                    'project' => 'navdhya-customer-1',
                    'api_key' => 'k1',
                    'service_account' => null,
                ],
            ],
        ]);

        $this->assertTrue($this->registry()->has('firebase-customer'));
    }

    public function test_service_account_matching_the_audience_project_registers(): void
    {
        config([
            'otp-providers.audiences.customer' => [
                'provider' => 'firebase',
                'firebase' => [
                    'project' => 'navdhya-customer-1',
                    'api_key' => 'k1',
                    'service_account' => json_encode([
                        'project_id' => 'navdhya-customer-1',
                        'private_key' => '-----BEGIN FAKE KEY-----',
                        'client_email' => 'svc@navdhya-customer-1.iam.gserviceaccount.com',
                        'token_uri' => 'https://oauth2.googleapis.com/token',
                    ]),
                ],
            ],
        ]);

        $this->assertTrue($this->registry()->has('firebase-customer'));
    }

    public function test_service_account_from_another_project_fails_closed(): void
    {
        // The exact class of mistake the RCA records: credentials and site
        // key borrowed from a different project than the audience uses.
        config([
            'otp-providers.audiences.customer' => [
                'provider' => 'firebase',
                'firebase' => [
                    'project' => 'navdhya-customer-1',
                    'api_key' => 'k1',
                    'service_account' => json_encode([
                        'project_id' => 'some-other-project',
                        'private_key' => '-----BEGIN FAKE KEY-----',
                        'client_email' => 'svc@some-other-project.iam.gserviceaccount.com',
                        'token_uri' => 'https://oauth2.googleapis.com/token',
                    ]),
                ],
            ],
        ]);

        $this->expectException(OtpProviderConfigurationException::class);
        $this->expectExceptionMessage('belongs to project [some-other-project]');
        $this->registry();
    }

    public function test_unreadable_service_account_path_fails_closed(): void
    {
        config([
            'otp-providers.audiences.customer' => [
                'provider' => 'firebase',
                'firebase' => [
                    'project' => 'navdhya-customer-1',
                    'api_key' => 'k1',
                    'service_account' => storage_path('app/private/not-installed.json'),
                ],
            ],
        ]);

        $this->expectException(OtpProviderConfigurationException::class);
        $this->expectExceptionMessage('missing or unreadable');
        $this->registry();
    }
}
