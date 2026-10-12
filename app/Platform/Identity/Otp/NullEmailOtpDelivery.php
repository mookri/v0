<?php

namespace App\Platform\Identity\Otp;

/**
 * The S46 default email delivery: deliberately silent. OTP codes still
 * surface only through the drill-only OTP_LOG_CODES log (no delivery
 * channel exists outside tests yet), so calling this is a no-op and
 * the issue() call site is wired and tested from day one — S48 swaps
 * the binding for the real channel without touching the state machine.
 */
final class NullEmailOtpDelivery implements EmailOtpDeliveryProvider
{
    public const NAME = 'null';

    public function name(): string
    {
        return self::NAME;
    }

    public function deliver(EmailOtpDelivery $delivery): void
    {
    }
}
