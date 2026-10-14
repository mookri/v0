<?php

namespace Tests\Unit\Platform\Identity\Otp;

use App\Platform\Identity\Otp\Msg91Client;
use App\Platform\Identity\Otp\Msg91VerifyUnavailableException;
use App\Platform\Identity\Otp\OtpProviderRateLimitedException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class Msg91ClientTest extends TestCase
{
    private const BASE_URL = 'https://control.msg91.com';
    private const AUTH_KEY = 'test-auth-key-12345';

    private Msg91Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new Msg91Client(self::AUTH_KEY, self::BASE_URL, 5);
    }

    public function test_verifies_valid_token_and_returns_attested_identifier(): void
    {
        Http::fake([
            'https://control.msg91.com/api/v5/widget/verifyAccessToken' => Http::response([
                'data' => [
                    'type' => 'success',
                    'message' => '919876543210',
                ],
                'status' => '200',
            ], 200),
        ]);

        $result = $this->client->verify('valid.jwt.token');

        $this->assertTrue($result->verified);
        $this->assertSame('919876543210', $result->identifier);
        $this->assertNull($result->reason);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://control.msg91.com/api/v5/widget/verifyAccessToken'
                && $request->hasHeader('authkey', self::AUTH_KEY)
                && $request['access_token'] === 'valid.jwt.token';
        });
    }

    public function test_rejects_when_provider_returns_error_type(): void
    {
        Http::fake([
            'https://control.msg91.com/api/v5/widget/verifyAccessToken' => Http::response([
                'data' => [
                    'type' => 'error',
                    'message' => 'Token expired or invalid',
                ],
                'status' => '400',
            ], 400),
        ]);

        $result = $this->client->verify('expired.jwt.token');

        $this->assertFalse($result->verified);
        $this->assertNull($result->identifier);
        $this->assertSame('Token expired or invalid', $result->reason);
    }

    public function test_rejects_when_body_is_not_success(): void
    {
        Http::fake([
            'https://control.msg91.com/api/v5/widget/verifyAccessToken' => Http::response([
                'data' => [
                    'type' => 'failure',
                    'message' => 'Token already used',
                ],
                'status' => '200',
            ], 200),
        ]);

        $result = $this->client->verify('replayed.jwt.token');

        $this->assertFalse($result->verified);
        $this->assertSame('Token already used', $result->reason);
    }

    public function test_throws_rate_limited_exception_on_429(): void
    {
        Http::fake([
            'https://control.msg91.com/api/v5/widget/verifyAccessToken' => Http::response('Too Many Requests', 429),
        ]);

        $this->expectException(OtpProviderRateLimitedException::class);
        $this->client->verify('some.jwt.token');
    }

    public function test_throws_unavailable_exception_on_5xx_server_error(): void
    {
        Http::fake([
            'https://control.msg91.com/api/v5/widget/verifyAccessToken' => Http::response('Server Error', 503),
        ]);

        $this->expectException(Msg91VerifyUnavailableException::class);
        $this->client->verify('some.jwt.token');
    }

    public function test_throws_unavailable_exception_on_connection_failure(): void
    {
        Http::fake([
            'https://control.msg91.com/api/v5/widget/verifyAccessToken' => function () {
                throw new ConnectionException('Connection timed out');
            },
        ]);

        $this->expectException(Msg91VerifyUnavailableException::class);
        $this->client->verify('some.jwt.token');
    }
}
