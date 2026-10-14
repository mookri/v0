<?php

namespace App\Platform\Identity\Otp;

/**
 * Verify verdict + the challenge row it applied to (post attempt
 * bump; post hold-marking for Held; still present for Match — the
 * manager deleted the row but returns what was consumed).
 */
final readonly class OtpVerifyResult
{
    private function __construct(
        public OtpVerifyStatus $status,
        public ?StoredOtpChallenge $challenge,
    ) {
    }

    public static function notFound(): self
    {
        return new self(OtpVerifyStatus::NotFound, null);
    }

    public static function mismatch(StoredOtpChallenge $challenge): self
    {
        return new self(OtpVerifyStatus::Mismatch, $challenge);
    }

    public static function held(StoredOtpChallenge $challenge): self
    {
        return new self(OtpVerifyStatus::Held, $challenge);
    }

    public static function match(StoredOtpChallenge $challenge): self
    {
        return new self(OtpVerifyStatus::Match, $challenge);
    }
}
