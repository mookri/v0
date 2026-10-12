<?php

namespace App\Platform\Identity\Otp;

use App\Platform\Identity\Otp\Mail\OtpCodeEmail;
use Illuminate\Support\Facades\Mail;

/**
 * Mailer-backed email OTP delivery (Verification Fabric S48) — the
 * real channel behind the S46 port, selected by
 * OTP_EMAIL_DELIVERY=mailer (default stays the silent null provider,
 * behavior-identical). Delivery only: code generation, hashing and
 * verification never move here.
 */
final class MailerEmailOtpDelivery implements EmailOtpDeliveryProvider
{
    public const NAME = 'mailer';

    public function name(): string
    {
        return self::NAME;
    }

    public function deliver(EmailOtpDelivery $delivery): void
    {
        // The code in the mail body IS the delivery channel — the
        // "never log the code" rule is about logs, not the channel
        // itself. Nothing here writes to the log.
        Mail::to($delivery->to)->send(new OtpCodeEmail(
            code: $delivery->code,
            minutes: max(1, (int) ceil((strtotime($delivery->expiresAt) - time()) / 60)),
        ));
    }
}
