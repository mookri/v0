<?php

namespace App\Platform\Identity;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Step-up OTP service (Kernel, I2): handles the OTP challenge flow for
 * MFA enrollment step-up (I2). This is a Kernel-level service so the
 * Identity module (MFA controller) can enforce step-up without
 * importing the Customer module (architecture §7: Kernel never
 * imports Modules).
 *
 * Uses the central connection and a dedicated table for step-up
 * challenges (reuses the customer_otp_challenges table with a
 * distinct purpose prefix to avoid collisions).
 */
final class StepUpOtpService
{
    private const CODE_LENGTH = 6;

    public const TTL_SECONDS = 600;

    public const MAX_ATTEMPTS = 3;

    public const MAX_PER_HOUR = 5;

    /** @var array<string, string> */
    private static array $testCodes = [];

    /**
     * @param array<string, mixed> $payload
     */
    public function issue(
        string $tenantId,
        string $purpose,
        string $identifier,
        CarbonImmutable $now,
        array $payload = [],
    ): array {
        // Same transaction + locking pattern as CustomerOtpService (C2/G1)
        return DB::connection('central')->transaction(function () use ($tenantId, $purpose, $identifier, $now, $payload): array {
            DB::connection('central')->table('tenants')
                ->where('id', $tenantId)
                ->lockForUpdate()
                ->first();

            $recent = DB::connection('central')->table('customer_otp_challenges')
                ->where('tenant_id', $tenantId)
                ->where('purpose', $purpose)
                ->where('identifier', $identifier)
                ->where('created_at', '>=', $now->subHour()->toDateTimeString())
                ->count();

            if ($recent >= self::MAX_PER_HOUR) {
                throw new \RuntimeException('Step-up rate limit exceeded');
            }

            DB::connection('central')->table('customer_otp_challenges')
                ->where('tenant_id', $tenantId)
                ->where('purpose', $purpose)
                ->where('identifier', $identifier)
                ->whereNull('superseded_at')
                ->update(['superseded_at' => $now->toDateTimeString()]);

            $code = $this->generateCode(self::CODE_LENGTH);
            $codeHash = hash('sha256', $code);

            $challengeId = 'chl_'.strtolower((string) Str::ulid());

            DB::connection('central')->table('customer_otp_challenges')->insert([
                'id' => $challengeId,
                'tenant_id' => $tenantId,
                'purpose' => $purpose,
                'identifier' => $identifier,
                'code_hash' => $codeHash,
                'payload' => $payload === [] ? null : json_encode($payload),
                'expires_at' => $now->addSeconds(self::TTL_SECONDS)->toDateTimeString(),
                'attempts' => 0,
                'max_attempts' => self::MAX_ATTEMPTS,
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);

            // Store code for test retrieval (drill-only)
            self::$testCodes[$challengeId] = $code;

            return [
                'challenge_id' => $challengeId,
                'code' => config('customers.log_otp_codes') === true ? $code : null,
                'expires_at' => $now->addSeconds(self::TTL_SECONDS)->toDateTimeString(),
            ];
        });
    }

    public function verify(
        string $tenantId,
        string $purpose,
        string $challengeId,
        string $code,
        CarbonImmutable $now,
    ): ?array {
        return DB::connection('central')->transaction(function () use ($tenantId, $purpose, $challengeId, $code, $now): ?array {
            $challenge = DB::connection('central')->table('customer_otp_challenges')
                ->where('id', $challengeId)
                ->where('tenant_id', $tenantId)
                ->where('purpose', $purpose)
                ->whereNull('superseded_at')
                ->lockForUpdate()
                ->first();

            if ($challenge === null) {
                return null;
            }

            if ($challenge->expires_at <= $now->toDateTimeString() || $challenge->attempts >= $challenge->max_attempts) {
                DB::connection('central')->table('customer_otp_challenges')
                    ->where('id', $challengeId)
                    ->delete();

                return null;
            }

            DB::connection('central')->table('customer_otp_challenges')
                ->where('id', $challengeId)
                ->increment('attempts');

            if (! hash_equals($challenge->code_hash, hash('sha256', $code))) {
                return null;
            }

            DB::connection('central')->table('customer_otp_challenges')
                ->where('id', $challengeId)
                ->delete();

            $payload = null;
            if (! empty($challenge->payload)) {
                $payload = json_decode($challenge->payload, true);
            }

            return [
                'id' => $challenge->id,
                'payload' => $payload,
            ];
        });
    }

    private function generateCode(int $length): string
    {
        return (string) Str::random($length);
    }

    /**
     * Test-only: retrieve the OTP code for a challenge (drill-only).
     */
    public static function getTestCode(string $challengeId): ?string
    {
        return self::$testCodes[$challengeId] ?? null;
    }
}