<?php

namespace App\Platform\Identity\Otp;

/**
 * One purpose's policy row (Verification Fabric S48): which audience
 * issues challenges for the purpose (audience picks the provider via
 * config/otp-providers.php) and which risk tier the purpose carries.
 * The table lives in config/otp-policy.php; purposes the table does
 * not name fail closed — no default provider is ever picked for them.
 */
final readonly class OtpPurposePolicy
{
    public function __construct(
        public string $purpose,
        public string $audience,
        public OtpRiskTier $tier,
    ) {
    }

    /**
     * @throws OtpPurposeUnknownException when the purpose has no policy row
     */
    public static function resolve(string $purpose): self
    {
        $row = config('otp-policy.purposes.'.$purpose);

        if (! is_array($row)
            || ! is_string($row['audience'] ?? null) || $row['audience'] === ''
            || ! is_string($row['tier'] ?? null)) {
            throw new OtpPurposeUnknownException(sprintf('otp purpose [%s] has no policy', $purpose));
        }

        return new self($purpose, $row['audience'], OtpRiskTier::from($row['tier']));
    }
}
