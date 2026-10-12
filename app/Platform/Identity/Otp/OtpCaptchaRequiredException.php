<?php

namespace App\Platform\Identity\Otp;

/**
 * Fail-closed marker: a remote (non-local) phone send was requested
 * for an audience whose policy demands a reCAPTCHA attestation, but no
 * token arrived. Controllers map this to a 422 — the send is rejected
 * before any provider call or rate-cap consumption, never downgraded
 * to a channel that skips attestation.
 */
final class OtpCaptchaRequiredException extends \RuntimeException
{
}
