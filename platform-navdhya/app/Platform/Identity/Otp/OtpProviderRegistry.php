<?php

namespace App\Platform\Identity\Otp;

/**
 * Name → provider registry (Verification Fabric). Everything is
 * registered here by config wiring (AppServiceProvider) — today the
 * single msg91_widget adapter; future vendors register their NAME the
 * same way. Unknown names throw (fail closed), they never fall back.
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
