<?php

namespace App\Platform\Identity\Otp;

/**
 * Admin-SDK service-account credentials for one Firebase audience,
 * resolved from FIREBASE_{CUSTOMER,VENDOR}_SERVICE_ACCOUNT.
 *
 * The send/verify REST calls authenticate with the web API key alone;
 * these credentials exist for the optional idToken cross-check (spike
 * item L6 in docs/04-security/firebase-phone-otp-spike.md). What is
 * checked here and now is provenance: credentials that belong to a
 * different Firebase project than the audience claims are rejected at
 * boot, because that mismatch — not the SMS path — is what silently
 * misroutes phone auth to the wrong project.
 *
 * The config value may be an absolute path to the key file or the JSON
 * itself, so mounted secrets and secret-manager injection both work.
 * Failure messages name the problem and the path, never file contents:
 * a private key must not reach a log through an exception message.
 */
final class FirebaseServiceAccount
{
    private const REQUIRED_FIELDS = ['project_id', 'private_key', 'client_email', 'token_uri'];

    /**
     * @param  array<string, string>  $credentials
     */
    private function __construct(
        public readonly string $projectId,
        public readonly string $clientEmail,
        private readonly array $credentials,
    ) {
    }

    /**
     * @return self|null null when the audience configured no service account,
     *                   which is the supported state while the cross-check is open
     *
     * @throws OtpProviderConfigurationException when a configured account cannot be used
     */
    public static function resolve(?string $configured, string $audience): ?self
    {
        $raw = trim((string) $configured);
        if ($raw === '') {
            return null;
        }

        $label = sprintf('firebase otp provider [%s] service account', $audience);

        if (! str_starts_with($raw, '{')) {
            if (! is_file($raw) || ! is_readable($raw)) {
                throw new OtpProviderConfigurationException("{$label} file is missing or unreadable: {$raw}");
            }

            $raw = (string) file_get_contents($raw);
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            throw new OtpProviderConfigurationException("{$label} is not valid JSON (contents withheld)");
        }

        $credentials = [];
        foreach (self::REQUIRED_FIELDS as $field) {
            $value = $decoded[$field] ?? null;
            if (! is_string($value) || trim($value) === '') {
                throw new OtpProviderConfigurationException("{$label} is missing required field [{$field}]");
            }
            $credentials[$field] = $value;
        }

        return new self(
            projectId: $credentials['project_id'],
            clientEmail: $credentials['client_email'],
            credentials: $credentials,
        );
    }

    /**
     * @return array<string, string>
     */
    public function credentials(): array
    {
        return $this->credentials;
    }
}
