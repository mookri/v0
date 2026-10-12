<?php

namespace App\Platform\Identity\Otp;

/**
 * Name → provider registry for non-local providers (Verification
 * Fabric). Local is special-cased by each service (it wraps that
 * service's own CodeSource, so the test seam stays per-service);
 * everything else — firebase-per-audience, future Twilio/MSG91 — is
 * registered here by config wiring (S47+). Unknown names throw
 * (fail closed), they never fall back.
 */
final class OtpProviderRegistry
{
    /**
     * @param array<string, PhoneVerificationProvider> $providers
     */
    public function __construct(private readonly array $providers = [])
    {
    }

    public function has(string $name): bool
    {
        return isset($this->providers[$name]);
    }

    /**
     * @throws OtpProviderUnavailableException when the name is unknown
     */
    public function get(string $name): PhoneVerificationProvider
    {
        return $this->providers[$name]
            ?? throw new OtpProviderUnavailableException(sprintf('otp provider [%s] is not registered', $name));
    }
}
