<?php

namespace App\Platform\Identity;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OTPHP\TOTP;

/**
 * TOTP second factor (SEC-004 §1.4): RFC 6238, 30 s step, ±1 window.
 * Secrets are application-encrypted at rest and activated only after a
 * confirming code; recovery codes are single-use SHA-256 hashes shown
 * once. Verification failures collapse (no oracle on state); since M4 a
 * shared per-user counter locks the factor for 15 min after 5 failures
 * (429 + Retry-After at the controllers) and the last accepted step is
 * remembered so a code can never be burned twice.
 */
final class MfaService
{
    public const RECOVERY_CODES = 10;

    public const WINDOW = 1;

    /** Failures (TOTP + recovery, shared) before the lockout trips. */
    public const LOCKOUT_ATTEMPTS = 5;

    /** Lockout duration in seconds. */
    public const LOCKOUT_SECONDS = 900;

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

        $this->guardLockout($user, $now);

        $totp = $this->totpFor($user);

        // M4 replay: the step the code burns is remembered on success —
        // the same code presented again within its ±1 window collapses
        // to an ordinary failure (and counts toward the lockout).
        if ($totp !== null && ($step = $this->matchingStep($totp, $code, $now)) !== null
            && (int) ($user->mfa_last_used_step ?? 0) !== $step) {
            return $this->recordSuccess($user, $step, $now);
        }

        if ($this->consumeRecoveryCode($user, $code)) {
            return $this->recordSuccess($user, null, $now);
        }

        $this->recordFailure($user, $now);

        return false;
    }

    /**
     * Fail closed once the lockout window is open — anything presented
     * while locked (even the correct code) waits out the window.
     */
    private function guardLockout(User $user, int $now): void
    {
        $lockedUntil = $user->mfa_locked_until;

        if ($lockedUntil === null) {
            return;
        }

        $until = $lockedUntil instanceof \DateTimeInterface
            ? $lockedUntil->getTimestamp()
            : (int) strtotime((string) $lockedUntil);

        if ($until > $now) {
            throw new MfaLockedException($until - $now);
        }
    }

    /**
     * RFC 6238 step the presented code was generated for — the ±1 s
     * leeway window of the verifier expressed as candidate steps, so a
     * replayed code is recognised by the step it burns, not by wall
     * time. Null when the code matches no live step.
     */
    private function matchingStep(TOTP $totp, string $code, int $now): ?int
    {
        $period = $totp->getPeriod();
        $epoch = $totp->getEpoch();

        $steps = [];
        foreach ([-1, 0, 1] as $delta) {
            $steps[] = intdiv(($now + $delta) - $epoch, $period);
        }

        foreach (array_unique($steps) as $step) {
            if (hash_equals($totp->at($epoch + ($step * $period)), $code)) {
                return $step;
            }
        }

        return null;
    }

    /**
     * Success writes are guarded: a user locked out between the entry
     * check and this write stays locked (fail closed), and the counters
     * reset only for an unlocked row.
     */
    private function recordSuccess(User $user, ?int $step, int $now): bool
    {
        $affected = User::query()
            ->whereKey($user->getKey())
            ->where(static function ($query) use ($now): void {
                $query->whereNull('mfa_locked_until')
                    ->orWhere('mfa_locked_until', '<=', gmdate('Y-m-d H:i:s', $now));
            })
            ->update([
                'mfa_failed_attempts' => 0,
                'mfa_last_used_step' => $step,
                'mfa_locked_until' => null,
            ]);

        if ($affected === 0) {
            throw new MfaLockedException(self::LOCKOUT_SECONDS);
        }

        $user->forceFill([
            'mfa_failed_attempts' => 0,
            'mfa_last_used_step' => $step,
            'mfa_locked_until' => null,
        ]);

        return true;
    }

    /**
     * Atomic under a row lock: concurrent failures cannot lose
     * increments, and the threshold transition (attempt 5 sets
     * locked_until) happens in the same critical section. The lockout
     * exception is thrown only after the transaction commits.
     */
    private function recordFailure(User $user, int $now): void
    {
        $locked = DB::connection('central')->transaction(function () use ($user, $now): bool {
            $row = User::query()->whereKey($user->getKey())->lockForUpdate()->first();

            if ($row === null) {
                return true;
            }

            $attempts = (int) $row->mfa_failed_attempts + 1;
            $row->forceFill(['mfa_failed_attempts' => $attempts]);

            if ($attempts >= self::LOCKOUT_ATTEMPTS) {
                $row->mfa_locked_until = gmdate('Y-m-d H:i:s', $now + self::LOCKOUT_SECONDS);
            }

            $row->save();

            return $attempts >= self::LOCKOUT_ATTEMPTS;
        });

        if ($locked) {
            throw new MfaLockedException(self::LOCKOUT_SECONDS);
        }
    }

    /**
     * Issue a fresh recovery sheet (lost sheet recovery): requires an
     * enabled factor plus a live TOTP code, replaces all previous codes.
     *
     * @return list<string>|null plaintext codes (once) on success
     */
    public function regenerateRecoveryCodes(User $user, string $code, int $now): ?array
    {
        if (! (bool) $user->mfa_enabled) {
            return null;
        }

        $totp = $this->totpFor($user);

        if ($totp === null || ! $totp->verify($code, $now, self::WINDOW)) {
            return null;
        }

        $codes = [];
        for ($i = 0; $i < self::RECOVERY_CODES; $i++) {
            $codes[] = strtoupper(Str::random(8));
        }

        $user->forceFill([
            'mfa_recovery_hashes' => array_map(static fn (string $c): string => hash('sha256', $c), $codes),
        ])->save();

        return $codes;
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
            // M4: a disabled factor carries no lockout state — a later
            // re-enrollment starts from a clean counter.
            'mfa_failed_attempts' => 0,
            'mfa_locked_until' => null,
            'mfa_last_used_step' => null,
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
