<?php

namespace App\Platform\Identity\Otp;

/**
 * MSG91 widget adapter (custom-SDK mode). The browser drives the
 * widget — sendOtp/retryOtp/verifyOtp run client-side and end in a
 * short-lived access token — so Laravel's `send` is only a marker
 * (no code is ever generated or seen server-side) and `verify` is a
 * token attestation: MSG91 names the identifier it verified, and the
 * canonical comparison decides Match. A token attesting a different
 * identifier than the challenge row is a mismatch (attempt burns) —
 * presenting someone else's token is an attack, not an outage.
 */
final class Msg91WidgetProvider implements PhoneVerificationProvider
{
    public const NAME = 'msg91_widget';

    public function __construct(private readonly VerifiesOtpTokens $tokens)
    {
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function send(OtpSendContext $context): OtpSendResult
    {
        return new OtpSendResult(providerRef: 'widget-client-side');
    }

    public function verify(OtpVerifyRequest $request): OtpVerifyOutcome
    {
        $result = $this->tokens->verify($request->code);

        if (! $result->verified || $result->identifier === null) {
            return OtpVerifyOutcome::Mismatch;
        }

        $expected = Msg91Identifier::canonical($request->identifier);
        $attested = Msg91Identifier::canonical($result->identifier);

        return $expected !== '' && $attested !== '' && $expected === $attested
            ? OtpVerifyOutcome::Match
            : OtpVerifyOutcome::Mismatch;
    }
}
