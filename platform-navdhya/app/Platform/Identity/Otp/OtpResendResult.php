<?php

namespace App\Platform\Identity\Otp;

use Carbon\CarbonImmutable;

/**
 * A granted resend: the channel code the client should hand to the
 * widget's retryOtp, the full configured channel list, how many
 * resends remain on this challenge, and when the next one unlocks
 * (null once the schedule is exhausted — the next code must be a
 * fresh challenge, gated by the delivery policy).
 */
final readonly class OtpResendResult
{
    /**
     * @param  list<string>  $channels
     */
    public function __construct(
        public string $channel,
        public array $channels,
        public int $resendsLeft,
        public ?CarbonImmutable $nextAllowedAt,
    ) {
    }
}
