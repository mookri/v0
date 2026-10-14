<?php

namespace Tests\Modules\Customers;

use Tests\TestCase;

final class OtpConfigEndpointTest extends TestCase
{
    public function test_otp_config_returns_public_widget_credentials(): void
    {
        config()->set('services.msg91.widget_id', 'test_widget_123');
        config()->set('services.msg91.widget_token_auth', 'test_token_auth_456');
        config()->set('services.msg91.auth_key', 'server_secret_key');
        config()->set('services.msg91.resend_channels', ['11', '4', '3']);

        $response = $this->getJson('/api/v1/customers/auth/otp/config');

        $response->assertOk()
            ->assertJsonPath('data.object', 'otp_config')
            ->assertJsonPath('data.widget_id', 'test_widget_123')
            ->assertJsonPath('data.token_auth', 'test_token_auth_456')
            ->assertJsonPath('data.provider', 'msg91_widget')
            ->assertJsonPath('data.resend.channels', ['11', '4', '3']);
    }

    public function test_otp_config_indicates_empty_when_credentials_missing(): void
    {
        config()->set('services.msg91.widget_id', '');
        config()->set('services.msg91.widget_token_auth', '');
        config()->set('services.msg91.auth_key', '');

        $response = $this->getJson('/api/v1/customers/auth/otp/config');

        $response->assertOk()
            ->assertJsonPath('data.object', 'otp_config')
            ->assertJsonPath('data.widget_id', '')
            ->assertJsonPath('data.token_auth', '');
    }
}
