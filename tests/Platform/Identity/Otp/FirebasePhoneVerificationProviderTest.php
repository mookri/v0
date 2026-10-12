<?php

namespace Tests\Platform\Identity\Otp;

use App\Platform\Identity\Otp\FirebasePhoneVerificationProvider;
use App\Platform\Identity\Otp\OtpProviderUnavailableException;
use App\Platform\Identity\Otp\OtpSendContext;
use App\Platform\Identity\Otp\OtpVerifyOutcome;
use App\Platform\Identity\Otp\OtpVerifyRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Firebase adapter port contract (S47, fully faked HTTP — the live
 * endpoints are exercised only by spike checklist L1-L6 with test
 * phone numbers). Covers send/verify shapes, the error taxonomy
 * (code family -> Mismatch, everything else -> Unavailable fail
 * closed), and the PII rule: no identifier, code or session body in
 * exception text.
 */
final class FirebasePhoneVerificationProviderTest extends TestCase
{
    private const API_KEY = 'test-api-key';

    private function provider(): FirebasePhoneVerificationProvider
    {
        return new FirebasePhoneVerificationProvider('firebase-customer', self::API_KEY, 3);
    }

    public function test_send_posts_phone_and_attestation_and_returns_session_ref(): void
    {
        Http::fake([
            'identitytoolkit.googleapis.com/*' => Http::response(['sessionInfo' => 'opaque-session-info'], 200),
        ]);

        $result = $this->provider()->send(new OtpSendContext(
            challengeId: 'c1',
            identifier: '+911234567890',
            purpose: 'login',
            codeLength: 6,
            attestation: 'recaptcha-token',
        ));

        // Remote sends expose no code — only the opaque session handle.
        $this->assertNull($result->code);
        $this->assertSame('opaque-session-info', $result->providerRef);

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), 'accounts:sendVerificationCode?key='.self::API_KEY)
                && $request['phoneNumber'] === '+911234567890'
                && $request['recaptchaToken'] === 'recaptcha-token';
        });
    }

    public function test_send_without_attestation_omits_the_recaptcha_field(): void
    {
        Http::fake([
            'identitytoolkit.googleapis.com/*' => Http::response(['sessionInfo' => 'opaque-session-info'], 200),
        ]);

        $this->provider()->send(new OtpSendContext(
            challengeId: 'c2',
            identifier: '+911234567890',
            purpose: 'login',
            codeLength: 6,
        ));

        Http::assertSent(function (Request $request): bool {
            return ! array_key_exists('recaptchaToken', $request->data());
        });
    }

    public function test_send_rejection_fails_closed_and_never_echoes_prose_or_identifier(): void
    {
        Http::fake([
            'identitytoolkit.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 429,
                    'message' => 'OPERATION_NOT_ALLOWED : SMS sending quota for +919999999999 exhausted',
                ],
            ], 429),
        ]);

        try {
            $this->provider()->send(new OtpSendContext(
                challengeId: 'c3',
                identifier: '+919999999999',
                purpose: 'login',
                codeLength: 6,
            ));
            $this->fail('expected OtpProviderUnavailableException');
        } catch (OtpProviderUnavailableException $e) {
            $this->assertStringContainsString('http 429', $e->getMessage());
            $this->assertStringContainsString('OPERATION_NOT_ALLOWED', $e->getMessage());
            $this->assertStringNotContainsString('+919999999999', $e->getMessage());
            $this->assertStringNotContainsString('exhausted', $e->getMessage());
        }
    }

    public function test_send_network_failure_fails_closed(): void
    {
        Http::fake(fn () => throw new ConnectionException('connection refused'));

        $this->expectException(OtpProviderUnavailableException::class);
        $this->provider()->send(new OtpSendContext(
            challengeId: 'c4',
            identifier: '+911234567890',
            purpose: 'login',
            codeLength: 6,
        ));
    }

    public function test_send_200_without_session_info_fails_closed(): void
    {
        Http::fake([
            'identitytoolkit.googleapis.com/*' => Http::response(['idToken' => 'unexpected'], 200),
        ]);

        $this->expectException(OtpProviderUnavailableException::class);
        $this->provider()->send(new OtpSendContext(
            challengeId: 'c5',
            identifier: '+911234567890',
            purpose: 'login',
            codeLength: 6,
        ));
    }

    public function test_verify_success_is_match(): void
    {
        Http::fake([
            'identitytoolkit.googleapis.com/*' => Http::response(['idToken' => 't', 'phoneNumber' => '+911234567890'], 200),
        ]);

        $outcome = $this->provider()->verify(new OtpVerifyRequest(
            codeHash: null,
            providerRef: 'opaque-session-info',
            code: '123456',
        ));

        $this->assertSame(OtpVerifyOutcome::Match, $outcome);
        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), 'accounts:signInWithPhoneNumber?key='.self::API_KEY)
                && $request['sessionInfo'] === 'opaque-session-info'
                && $request['code'] === '123456';
        });
    }

    public function test_verify_wrong_code_is_mismatch(): void
    {
        Http::fake([
            'identitytoolkit.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 400,
                    'message' => 'INVALID_SESSION_INFO : invalid or expired',
                    'status' => 'NOT_FOUND',
                ],
            ], 400),
        ]);

        $this->assertSame(OtpVerifyOutcome::Mismatch, $this->provider()->verify(new OtpVerifyRequest(
            codeHash: null,
            providerRef: 'opaque-session-info',
            code: '000000',
        )));
    }

    public function test_verify_expired_session_is_mismatch_like_a_dead_local_row(): void
    {
        Http::fake([
            'identitytoolkit.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 400,
                    'message' => 'SESSION_EXPIRED : session has expired',
                    'errors' => [['message' => 'x', 'reason' => 'sessionExpired']],
                ],
            ], 400),
        ]);

        $this->assertSame(OtpVerifyOutcome::Mismatch, $this->provider()->verify(new OtpVerifyRequest(
            codeHash: null,
            providerRef: 'opaque-session-info',
            code: '123456',
        )));
    }

    public function test_verify_400_outside_the_code_family_fails_closed_as_unavailable(): void
    {
        Http::fake([
            'identitytoolkit.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 400,
                    'message' => 'CAPTCHA_BLOCKED : reCAPTCHA verification required',
                    'errors' => [['message' => 'x', 'reason' => 'captchaBlocked']],
                ],
            ], 400),
        ]);

        $this->assertSame(OtpVerifyOutcome::Unavailable, $this->provider()->verify(new OtpVerifyRequest(
            codeHash: null,
            providerRef: 'opaque-session-info',
            code: '123456',
        )));
    }

    public function test_verify_quota_and_server_errors_are_unavailable(): void
    {
        Http::fakeSequence('identitytoolkit.googleapis.com/*')
            ->push(['error' => ['code' => 429, 'message' => 'RATE_LIMIT_EXCEEDED : quota']], 429)
            ->push(['error' => ['code' => 503, 'message' => 'UNAVAILABLE']], 503);

        $this->assertSame(OtpVerifyOutcome::Unavailable, $this->provider()->verify(new OtpVerifyRequest(
            codeHash: null,
            providerRef: 'a-session',
            code: '123456',
        )));
        $this->assertSame(OtpVerifyOutcome::Unavailable, $this->provider()->verify(new OtpVerifyRequest(
            codeHash: null,
            providerRef: 'b-session',
            code: '123456',
        )));
    }

    public function test_verify_network_failure_is_unavailable(): void
    {
        Http::fake(fn () => throw new ConnectionException('connection refused'));

        $this->assertSame(OtpVerifyOutcome::Unavailable, $this->provider()->verify(new OtpVerifyRequest(
            codeHash: null,
            providerRef: 'opaque-session-info',
            code: '123456',
        )));
    }

    public function test_verify_without_session_ref_short_circuits_to_mismatch(): void
    {
        Http::fake();

        $this->assertSame(OtpVerifyOutcome::Mismatch, $this->provider()->verify(new OtpVerifyRequest(
            codeHash: null,
            providerRef: null,
            code: '123456',
        )));
        Http::assertNothingSent();
    }
}
