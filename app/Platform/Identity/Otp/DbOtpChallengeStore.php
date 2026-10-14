<?php

namespace App\Platform\Identity\Otp;

use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * SQL-backed OtpChallengeStore over one (connection, table) pair. Raw
 * query builder on purpose: Platform code must not import module models
 * (architecture §7), and both served shapes — central
 * `customer_otp_challenges` and tenant `otp_challenges` — carry the same
 * columns after their migrations.
 */
final class DbOtpChallengeStore implements OtpChallengeStore
{
    /**
     * @param  \Closure(): string  $connectionResolver
     */
    public function __construct(
        private readonly \Closure $connectionResolver,
        private readonly string $table,
        private readonly bool $lockTenantRow,
    ) {
    }

    public static function customer(): self
    {
        return new self(
            connectionResolver: static fn (): string => 'central',
            table: 'customer_otp_challenges',
            lockTenantRow: true,
        );
    }

    public static function onboarding(): self
    {
        return new self(
            connectionResolver: static function (): string {
                $context = app(TenantContext::class);

                return $context->isResolved() ? (string) $context->connectionName() : 'tenant_shared';
            },
            table: 'otp_challenges',
            lockTenantRow: false,
        );
    }

    public function runLocked(string $tenantId, \Closure $work): mixed
    {
        $connection = ($this->connectionResolver)();

        return DB::connection($connection)->transaction(function () use ($tenantId, $connection, $work): mixed {
            if ($this->lockTenantRow) {
                // M2/M3: serialise concurrent issue/resend/verify per
                // tenant — the loser waits, then sees committed state.
                DB::connection($connection)->table('tenants')
                    ->where('id', $tenantId)
                    ->lockForUpdate()
                    ->first();
            }

            return $work();
        });
    }

    public function insert(StoredOtpChallenge $challenge): void
    {
        $connection = ($this->connectionResolver)();

        DB::connection($connection)->table($this->table)->insert([
            'id' => $challenge->id,
            'tenant_id' => $challenge->tenantId,
            'purpose' => $challenge->purpose,
            'identifier' => $challenge->identifier,
            'code_hash' => $challenge->codeHash,
            'provider' => $challenge->provider,
            'provider_ref' => $challenge->providerRef,
            'status' => $challenge->status,
            'payload' => $challenge->payload === [] ? null : json_encode($challenge->payload),
            'expires_at' => $challenge->expiresAt->toDateTimeString(),
            'attempts' => $challenge->attempts,
            'max_attempts' => $challenge->maxAttempts,
            'resend_count' => $challenge->resendCount,
            'last_sent_at' => $challenge->lastSentAt?->toDateTimeString(),
            'created_at' => $challenge->createdAt->toDateTimeString(),
            'updated_at' => $challenge->createdAt->toDateTimeString(),
        ]);
    }

    public function findLiveForUpdate(string $tenantId, array $purposes, string $challengeId, ?string $status = null): ?StoredOtpChallenge
    {
        $connection = ($this->connectionResolver)();

        $query = DB::connection($connection)->table($this->table)
            ->where('id', $challengeId)
            ->where('tenant_id', $tenantId)
            ->whereIn('purpose', $purposes)
            ->whereNull('superseded_at')
            ->lockForUpdate();

        if ($status !== null) {
            $query->where('status', $status);
        }

        $row = $query->first();

        return $row === null ? null : $this->hydrate($row);
    }

    public function findLatestLive(string $tenantId, string $purpose, string $identifier): ?StoredOtpChallenge
    {
        $connection = ($this->connectionResolver)();

        $row = DB::connection($connection)->table($this->table)
            ->where('tenant_id', $tenantId)
            ->where('purpose', $purpose)
            ->where('identifier', $identifier)
            ->whereNull('superseded_at')
            ->orderByDesc('created_at')
            ->first();

        return $row === null ? null : $this->hydrate($row);
    }

    public function countCreatedSince(string $tenantId, string $purpose, string $identifier, CarbonImmutable $since): int
    {
        $connection = ($this->connectionResolver)();

        return DB::connection($connection)->table($this->table)
            ->where('tenant_id', $tenantId)
            ->where('purpose', $purpose)
            ->where('identifier', $identifier)
            ->where('created_at', '>=', $since->toDateTimeString())
            ->count();
    }

    public function supersedeLive(string $tenantId, string $purpose, string $identifier, CarbonImmutable $now): void
    {
        $connection = ($this->connectionResolver)();

        DB::connection($connection)->table($this->table)
            ->where('tenant_id', $tenantId)
            ->where('purpose', $purpose)
            ->where('identifier', $identifier)
            ->whereNull('superseded_at')
            ->update(['superseded_at' => $now->toDateTimeString()]);
    }

    public function update(StoredOtpChallenge $challenge): void
    {
        $connection = ($this->connectionResolver)();

        DB::connection($connection)->table($this->table)
            ->where('id', $challenge->id)
            ->update([
                'status' => $challenge->status,
                'attempts' => $challenge->attempts,
                'resend_count' => $challenge->resendCount,
                'last_sent_at' => $challenge->lastSentAt?->toDateTimeString(),
                'updated_at' => CarbonImmutable::now()->toDateTimeString(),
            ]);
    }

    public function delete(string $challengeId): void
    {
        $connection = ($this->connectionResolver)();

        DB::connection($connection)->table($this->table)
            ->where('id', $challengeId)
            ->delete();
    }

    private function hydrate(object $row): StoredOtpChallenge
    {
        $payload = [];
        if (! empty($row->payload)) {
            $decoded = json_decode((string) $row->payload, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        return new StoredOtpChallenge(
            id: (string) $row->id,
            tenantId: (string) $row->tenant_id,
            purpose: (string) $row->purpose,
            identifier: (string) $row->identifier,
            codeHash: $row->code_hash !== null ? (string) $row->code_hash : null,
            provider: isset($row->provider) ? (string) $row->provider : null,
            providerRef: isset($row->provider_ref) ? (string) $row->provider_ref : null,
            status: (string) ($row->status ?? 'sent'),
            payload: $payload,
            createdAt: CarbonImmutable::parse($row->created_at),
            expiresAt: CarbonImmutable::parse($row->expires_at),
            attempts: (int) ($row->attempts ?? 0),
            maxAttempts: (int) ($row->max_attempts ?? 3),
            resendCount: (int) ($row->resend_count ?? 0),
            lastSentAt: isset($row->last_sent_at) ? CarbonImmutable::parse($row->last_sent_at) : null,
        );
    }
}
