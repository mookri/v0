<?php

namespace Tests\Platform\Identity\Otp;

use App\Platform\Identity\Otp\FirebaseServiceAccount;
use App\Platform\Identity\Otp\OtpProviderConfigurationException;
use PHPUnit\Framework\TestCase;

/**
 * Admin credential resolution (spike L6 wiring): unset is the supported
 * state, a configured account is accepted inline or from an absolute path,
 * and every rejection names the problem without ever echoing file
 * contents — a private key must not reach a log through an exception.
 */
final class FirebaseServiceAccountTest extends TestCase
{
    private const SECRET_MATERIAL = '-----BEGIN FAKE PRIVATE KEY-----MII_SECRET_NEVER_LOGGED';

    /**
     * @var list<string>
     */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }
        $this->tempFiles = [];

        parent::tearDown();
    }

    /**
     * @return array<string, string>
     */
    private function accountJson(string $projectId = 'navdhya-customer-1'): array
    {
        return [
            'type' => 'service_account',
            'project_id' => $projectId,
            'private_key_id' => 'fake-key-id',
            'private_key' => self::SECRET_MATERIAL,
            'client_email' => "fake@{$projectId}.iam.gserviceaccount.com",
            'client_id' => '123',
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ];
    }

    private function writeFile(string $contents): string
    {
        $path = sys_get_temp_dir().'\navdhya-sa-'.uniqid().'.json';
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }

    public function test_unset_or_blank_configures_no_service_account(): void
    {
        $this->assertNull(FirebaseServiceAccount::resolve(null, 'customer'));
        $this->assertNull(FirebaseServiceAccount::resolve('', 'customer'));
        $this->assertNull(FirebaseServiceAccount::resolve("   \n", 'customer'));
    }

    public function test_inline_json_resolves_to_credentials(): void
    {
        $account = FirebaseServiceAccount::resolve(
            json_encode($this->accountJson(), JSON_THROW_ON_ERROR),
            'customer',
        );

        $this->assertNotNull($account);
        $this->assertSame('navdhya-customer-1', $account->projectId);
        $this->assertSame('fake@navdhya-customer-1.iam.gserviceaccount.com', $account->clientEmail);
        $this->assertSame(self::SECRET_MATERIAL, $account->credentials()['private_key']);
    }

    public function test_absolute_path_is_read_from_disk(): void
    {
        $path = $this->writeFile(json_encode($this->accountJson('navdhya-vendor-1'), JSON_THROW_ON_ERROR));

        $account = FirebaseServiceAccount::resolve($path, 'vendor');

        $this->assertNotNull($account);
        $this->assertSame('navdhya-vendor-1', $account->projectId);
    }

    public function test_missing_file_fault_names_the_path(): void
    {
        $path = sys_get_temp_dir().'\navdhya-sa-does-not-exist-'.uniqid().'.json';

        try {
            FirebaseServiceAccount::resolve($path, 'customer');
            $this->fail('expected a configuration fault');
        } catch (OtpProviderConfigurationException $e) {
            $this->assertStringContainsString('missing or unreadable', $e->getMessage());
            $this->assertStringContainsString($path, $e->getMessage());
        }
    }

    public function test_undecodable_contents_fault_never_echoes_them(): void
    {
        $path = $this->writeFile('{not json at all '.self::SECRET_MATERIAL);

        try {
            FirebaseServiceAccount::resolve($path, 'customer');
            $this->fail('expected a configuration fault');
        } catch (OtpProviderConfigurationException $e) {
            $this->assertStringContainsString('not valid JSON', $e->getMessage());
            $this->assertStringNotContainsString('not json at all', $e->getMessage());
        }
    }

    public function test_missing_required_field_is_named_without_leaking_the_key(): void
    {
        $json = $this->accountJson();
        unset($json['client_email']);

        try {
            FirebaseServiceAccount::resolve(json_encode($json, JSON_THROW_ON_ERROR), 'customer');
            $this->fail('expected a configuration fault');
        } catch (OtpProviderConfigurationException $e) {
            $this->assertStringContainsString('missing required field [client_email]', $e->getMessage());
            $this->assertStringNotContainsString(self::SECRET_MATERIAL, $e->getMessage());
        }
    }

    public function test_blank_required_field_is_rejected(): void
    {
        $json = $this->accountJson();
        $json['private_key'] = '  ';

        try {
            FirebaseServiceAccount::resolve(json_encode($json, JSON_THROW_ON_ERROR), 'customer');
            $this->fail('expected a configuration fault');
        } catch (OtpProviderConfigurationException $e) {
            $this->assertStringContainsString('missing required field [private_key]', $e->getMessage());
        }
    }
}
