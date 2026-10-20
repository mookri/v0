<?php

namespace Tests\Unit\Platform\Identity\Otp;

use App\Platform\Identity\Otp\Msg91VerifyUnavailableException;
use App\Platform\Identity\Otp\Msg91WidgetProvider;
use App\Platform\Identity\Otp\OtpSendContext;
use App\Platform\Identity\Otp\OtpTokenResult;
use App\Platform\Identity\Otp\OtpVerifyOutcome;
use App\Platform\Identity\Otp\OtpVerifyRequest;
use App\Platform\Identity\Otp\VerifiesOtpTokens;
use Tests\TestCase;

final class Msg91WidgetProviderTest extends TestCase
{
    public function test_send_returns_marker_result_without_server_code(): void
    {
        $verifier = $this->createMock(VerifiesOtpTokens::class);
        $provider = new Msg91WidgetProvider($verifier);

        $result = $provider->send(new OtpSendContext(
            challengeId: '01ARZ3NDEKTSV4RRFFQ69G5FAV',
            identifier: '+919876543210',
            purpose: 'login',
            codeLength: 6,
            audience: 'customer',
        ));

        $this->assertNull($result->code);
        $this->assertSame('widget-client-side', $result->providerRef);
        $this->assertSame('msg91_widget', $provider->name());
    }

    public function test_verify_returns_match_when_attested_identifier_matches_canonically(): void
    {
        $verifier = $this->createMock(VerifiesOtpTokens::class);
        $verifier->expects($this->once())
            ->method('verify')
            ->with('valid.token')
            ->willReturn(OtpTokenResult::attested('919876543210'));

        $provider = new Msg91WidgetProvider($verifier);

        // Challenge stored +919876543210, MSG91 attested 919876543210 -> canonical match
        $outcome = $provider->verify(new OtpVerifyRequest(
            codeHash: null,
            providerRef: 'widget-client-side',
            code: 'valid.token',
            identifier: '+919876543210',
        ));

        $this->assertSame(OtpVerifyOutcome::Match, $outcome);
    }

    public function test_verify_returns_mismatch_when_attested_identifier_differs(): void
    {
        $verifier = $this->createMock(VerifiesOtpTokens::class);
        $verifier->expects($this->once())
            ->method('verify')
            ->with('other.user.token')
            ->willReturn(OtpTokenResult::attested('919999999999'));

        $provider = new Msg91WidgetProvider($verifier);

        $outcome = $provider->verify(new OtpVerifyRequest(
            codeHash: null,
            providerRef: 'widget-client-side',
            code: 'other.user.token',
            identifier: '+919876543210',
        ));

        $this->assertSame(OtpVerifyOutcome::Mismatch, $outcome);
    }

    public function test_verify_returns_mismatch_when_token_is_rejected(): void
    {
        $verifier = $this->createMock(VerifiesOtpTokens::class);
        $verifier->expects($this->once())
            ->method('verify')
            ->with('invalid.token')
            ->willReturn(OtpTokenResult::rejected('expired'));

        $provider = new Msg91WidgetProvider($verifier);

        $outcome = $provider->verify(new OtpVerifyRequest(
            codeHash: null,
            providerRef: 'widget-client-side',
            code: 'invalid.token',
            identifier: '+919876543210',
        ));

        $this->assertSame(OtpVerifyOutcome::Mismatch, $outcome);
    }

    public function test_verify_bubbles_unavailable_exception(): void
    {
        $verifier = $this->createMock(VerifiesOtpTokens::class);
        $verifier->expects($this->once())
            ->method('verify')
            ->willThrowException(new Msg91VerifyUnavailableException('down'));

        $provider = new Msg91WidgetProvider($verifier);

        $this->expectException(Msg91VerifyUnavailableException::class);
        $provider->verify(new OtpVerifyRequest(
            codeHash: null,
            providerRef: 'widget-client-side',
            code: 'token',
            identifier: '+919876543210',
        ));
    }
}
