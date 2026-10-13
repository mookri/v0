<?php

namespace Tests\Platform\Identity\Otp;

use App\Platform\Identity\Otp\FirebasePhoneVerificationProvider;
use App\Platform\Identity\Otp\OtpProviderRateLimitedException;
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

    public function test_colonless_error_message_stays_out_of_the_fault_text(): void
    {
        // Google's shape for some errors is prose with no symbolic head
        // at all — the whole message would otherwise be logged as a code.
        Http::fake([
            'identitytoolkit.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 400,
                    'message' => 'API key not valid. Please pass a valid API key to +919999999999.',
                    'status' => 'INVALID_ARGUMENT',
                ],
            ], 400),
        ]);

        try {
            $this->provider()->send(new OtpSendContext(
                challengeId: 'c7',
                identifier: '+919999999999',
                purpose: 'login',
                codeLength: 6,
            ));
            $this->fail('expected a typed provider fault');
        } catch (OtpProviderUnavailableException $e) {
            $this->assertStringContainsString('http 400', $e->getMessage());
            $this->assertStringContainsString('INVALID_ARGUMENT', $e->getMessage());
            $this->assertStringNotContainsString('not valid', $e->getMessage());
            $this->assertStringNotContainsString('+919999999999', $e->getMessage());
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

    public function test_send_rate_limit_head_is_the_retryable_typed_fault(): void
    {
        // Live 2026-10-13 shape: a hammered real number answers 400
        // TOO_MANY_ATTEMPTS_TRY_LATER, which re-arms on every retry — so
        // the caller must see "back off", not "the channel is down".
        Http::fake([
            'identitytoolkit.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 400,
                    'message' => 'TOO_MANY_ATTEMPTS_TRY_LATER : Access to this method has been throttled',
                    'status' => 'TOO_MANY_REQUESTS',
                ],
            ], 400),
        ]);

        try {
            $this->provider()->send(new OtpSendContext(
                challengeId: 'c6',
                identifier: '+919999999999',
                purpose: 'login',
                codeLength: 6,
            ));
            $this->fail('expected the retryable provider fault');
        } catch (OtpProviderRateLimitedException $e) {
            $this->assertStringContainsString('http 400', $e->getMessage());
            $this->assertStringContainsString('TOO_MANY_ATTEMPTS_TRY_LATER', $e->getMessage());
            $this->assertStringNotContainsString('+919999999999', $e->getMessage());
            $this->assertStringNotContainsString('throttled', $e->getMessage());
            // Fail-closed inheritance: any caller catching the dependency
            // fault keeps catching this one.
            $this->assertInstanceOf(OtpProviderUnavailableException::class, $e);
        }
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

    public function test_verify_400_outside_the_code_family_fails_closed_as_a_typed_fault(): void
    {
        Http::fake([
            'identitytoolkit.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 400,
                    'message' => 'CAPTCHA_CHECK_FAILED : The reCAPTCHA was invalid. Try this again',
                    'errors' => [['message' => 'x', 'reason' => 'captchaCheckFailed']],
                ],
            ], 400),
        ]);

        try {
            $this->provider()->verify(new OtpVerifyRequest(
                codeHash: null,
                providerRef: 'opaque-session-info',
                code: '123456',
            ));
            $this->fail('expected a typed provider fault');
        } catch (OtpProviderRateLimitedException $e) {
            $this->fail('a captcha refusal is a dependency fault, not a back-off');
        } catch (OtpProviderUnavailableException $e) {
            // The whole point of the typed fault: the head that explains
            // the failure (here — the 2026-10-13 RCA captcha wall)
            // reaches report(), while the redemption still never looks
            // like a wrong code.
            $this->assertStringContainsString('http 400', $e->getMessage());
            $this->assertStringContainsString('CAPTCHA_CHECK_FAILED', $e->getMessage());
            $this->assertStringNotContainsString('123456', $e->getMessage());
            $this->assertStringNotContainsString('opaque-session-info', $e->getMessage());
            $this->assertStringNotContainsString('was invalid', $e->getMessage());
        }
    }

    public function test_verify_quota_and_server_errors_fail_closed(): void
    {
        Http::fakeSequence('identitytoolkit.googleapis.com/*')
            ->push(['error' => ['code' => 429, 'message' => 'RATE_LIMIT_EXCEEDED : quota', 'status' => 'RESOURCE_EXHAUSTED']], 429)
            ->push(['error' => ['code' => 503, 'message' => 'UNAVAILABLE : backend']], 503);

        // A provider-side back-off is retryable, so it keeps the subclass
        // shape...
        try {
            $this->provider()->verify(new OtpVerifyRequest(
                codeHash: null,
                providerRef: 'a-session',
                code: '123456',
            ));
            $this->fail('expected a retryable provider fault');
        } catch (OtpProviderRateLimitedException $e) {
            $this->assertStringContainsString('RATE_LIMIT_EXCEEDED', $e->getMessage());
        }

        // ...and a backend fault does not.
        try {
            $this->provider()->verify(new OtpVerifyRequest(
                codeHash: null,
                providerRef: 'b-session',
                code: '123456',
            ));
            $this->fail('expected a typed provider fault');
        } catch (OtpProviderRateLimitedException $e) {
            $this->fail('a 503 backend fault is not a back-off');
        } catch (OtpProviderUnavailableException $e) {
            $this->assertStringContainsString('http 503', $e->getMessage());
        }
    }

    public function test_verify_network_failure_fails_closed(): void
    {
        Http::fake(fn () => throw new ConnectionException('connection refused'));

        $this->expectException(OtpProviderUnavailableException::class);
        $this->provider()->verify(new OtpVerifyRequest(
            codeHash: null,
            providerRef: 'opaque-session-info',
            code: '123456',
        ));
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
