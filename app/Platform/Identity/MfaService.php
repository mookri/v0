<?php

namespace App\Platform\Identity;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use OTPHP\TOTP;

/**
 * TOTP second factor (SEC-004 §1.4): RFC 6238, 30 s step, ±1 window.
 * Secrets are application-encrypted at rest and activated only after a
 * confirming code; recovery codes are single-use SHA-256 hashes shown
 * once. All verification failures collapse (no oracle on state).
 */
final class MfaService
{
    public const RECOVERY_CODES = 10;

    public const WINDOW = 1;

    /**
     * @return array{otpauth_url: string}
     */
    public function enroll(User $user): array
    {
        $totp = TOTP::generate();
        $totp->setLabel((string) ($user->email ?? $user->getAuthIdentifier()));
        $totp->setIssuer('Navdhya');

        $user->forceFill([
            'mfa_secret' => Crypt::encryptString($totp->getSecret()),
            'mfa_enabled' => false,
            'mfa_recovery_hashes' => null,
            'mfa_enrolled_at' => null,
        ])->save();

        return ['otpauth_url' => $totp->getProvisioningUri()];
    }

    /**
     * @return list<string>|null recovery codes (plaintext once) on success
     */
    public function confirm(User $user, string $code, int $now): ?array
    {
        $totp = $this->totpFor($user);

        if ($totp === null || (bool) $user->mfa_enabled) {
            return null;
        }

        if (! $totp->verify($code, $now, self::WINDOW)) {
            return null;
        }

        $codes = [];
        for ($i = 0; $i < self::RECOVERY_CODES; $i++) {
            $codes[] = strtoupper(Str::random(8));
        }

        $user->forceFill([
            'mfa_enabled' => true,
            'mfa_recovery_hashes' => array_map(static fn (string $c): string => hash('sha256', $c), $codes),
            'mfa_enrolled_at' => gmdate('Y-m-d H:i:s', $now),
        ])->save();

        return $codes;
    }

    public function verify(User $user, string $code, int $now): bool
    {
        if (! (bool) $user->mfa_enabled) {
            return false;
        }

        $totp = $this->totpFor($user);

        if ($totp !== null && $totp->verify($code, $now, self::WINDOW)) {
            return true;
        }

        return $this->consumeRecoveryCode($user, $code);
    }

    public function revoke(User $user, string $code, int $now): bool
    {
        // Revocation needs a live TOTP code: recovery codes authenticate
        // (verify) but cannot revoke, so a stolen recovery sheet alone
        // can never kill the factor.
        $totp = $this->totpFor($user);

        if ($totp === null || ! $totp->verify($code, $now, self::WINDOW)) {
            return false;
        }

        $user->forceFill([
            'mfa_secret' => null,
            'mfa_enabled' => false,
            'mfa_recovery_hashes' => null,
            'mfa_enrolled_at' => null,
        ])->save();

        return true;
    }

    private function totpFor(User $user): ?TOTP
    {
        $encrypted = $user->mfa_secret;

        if (! is_string($encrypted) || $encrypted === '') {
            return null;
        }

        try {
            $secret = Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return null;
        }

        $totp = TOTP::createFromSecret($secret);
        $totp->setLabel((string) ($user->email ?? $user->getAuthIdentifier()));
        $totp->setIssuer('Navdhya');

        return $totp;
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $hashes = $user->mfa_recovery_hashes;

        if (! is_array($hashes) || $hashes === []) {
            return false;
        }

        $presented = hash('sha256', $code);
        $remaining = array_values(array_filter(
            $hashes,
            static fn ($hash): bool => ! is_string($hash) || ! hash_equals($hash, $presented)
        ));

        if (count($remaining) === count($hashes)) {
            return false;
        }

        $user->forceFill(['mfa_recovery_hashes' => $remaining])->save();

        return true;
    }
}
