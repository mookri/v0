<?php

namespace App\Platform\Identity\Otp;

/**
 * The audience's OTP configuration is present but unusable — a service
 * account that cannot be read, decoded, or matched to its project.
 *
 * Extends the unavailable marker so a misconfiguration behaves like a
 * provider fault (typed 503, no dispatch, no downgrade to a channel that
 * cannot deliver SMS) rather than a silent partial setup: an operator who
 * asked for a credential cross-check never gets one that quietly stopped
 * existing.
 */
final class OtpProviderConfigurationException extends OtpProviderUnavailableException
{
}
