<?php

namespace App\Platform\Identity\Otp;

/**
 * Email OTP delivery port (Verification Fabric, alongside the phone
 * port — both channels planned per S45 decision). Delivery only: email
 * code generation, hashing and verification stay exactly where they
 * are (the code is ours, unlike Firebase phone OTP where the provider
 * generates the code).
 */
interface EmailOtpDeliveryProvider
{
    public function name(): string;

    /**
     * Deliver an already-issued email OTP. Implementations must never
     * log the code (drill visibility is the OTP_LOG_CODES seam, not a
     * delivery-side log).
     */
    public function deliver(EmailOtpDelivery $delivery): void;
}
