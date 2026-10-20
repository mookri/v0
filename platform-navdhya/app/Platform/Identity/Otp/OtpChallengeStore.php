<?php

namespace App\Platform\Identity\Otp;

use Carbon\CarbonImmutable;

/**
 * Persistence port for OTP challenges (the "anywhere" seam of the OTP
 * subsystem): any flow that can answer these eight operations can host
 * challenges, and the policy machine above it (OtpChallengeManager)
 * never learns which table or connection it is talking to.
 *
 * Implementations own their serialization story: runLocked() is the one
 * place where concurrent issue/resend/verify for the same tenant line up.
 */
interface OtpChallengeStore
{
    /**
     * Run $work inside the store's serialized transaction. Implementations
     * decide what locking that means (tenant-row lock, advisory lock, or
     * a plain transaction) — callers get linearizable challenge state.
     *
     * @template T
     *
     * @param  \Closure(): T  $work
     * @return T
     */
    public function runLocked(string $tenantId, \Closure $work): mixed;

    public function insert(StoredOtpChallenge $challenge): void;

    /**
     * Locked read of one non-superseded challenge, optionally filtered to
     * a status. Returns null for foreign tenants, wrong purposes, and
     * superseded rows alike (no oracle).
     *
     * @param list<string> $purposes
     */
    public function findLiveForUpdate(string $tenantId, array $purposes, string $challengeId, ?string $status = null): ?StoredOtpChallenge;

    /** Newest non-superseded challenge for this identifier+purpose. */
    public function findLatestLive(string $tenantId, string $purpose, string $identifier): ?StoredOtpChallenge;

    /** Issuances inside the rate-limit window (superseded rows count). */
    public function countCreatedSince(string $tenantId, string $purpose, string $identifier, CarbonImmutable $since): int;

    /** Mark every live challenge of this identity dead (mark, not delete). */
    public function supersedeLive(string $tenantId, string $purpose, string $identifier, CarbonImmutable $now): void;

    /** Persist a mutated challenge (attempts, resend bookkeeping, status). */
    public function update(StoredOtpChallenge $challenge): void;

    public function delete(string $challengeId): void;
}
