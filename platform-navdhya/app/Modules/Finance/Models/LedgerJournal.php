<?php

namespace App\Modules\Finance\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Double-Entry Ledger Journal (Phase 5, Task 5.1.4).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $journal_number
 * @property string $reference_type
 * @property string $reference_id
 * @property string $description
 * @property \Illuminate\Support\Carbon $posted_at
 * @property string|null $accounting_period
 * @property string $status
 * @property string|null $reverses_journal_id
 * @property string|null $reversed_by_journal_id
 * @property \Illuminate\Support\Carbon|null $reversed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class LedgerJournal extends Model
{
    use HasUlids;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_POSTED = 'POSTED';
    public const STATUS_REVERSED = 'REVERSED';
    public const STATUS_VOID = 'VOID';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'journal_number',
        'reference_type',
        'reference_id',
        'description',
        'posted_at',
        'accounting_period',
        'status',
        'reverses_journal_id',
        'reversed_by_journal_id',
        'reversed_at',
    ];

    protected function casts(): array
    {
        return [
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (LedgerJournal $journal): void {
            $originalStatus = (string) $journal->getOriginal('status');
            if ($originalStatus === self::STATUS_PENDING || $originalStatus === '') {
                return;
            }

            // Posted or reversed journals may ONLY transition POSTED -> REVERSED to link their contra journal.
            $allowedTransition = $originalStatus === self::STATUS_POSTED
                && $journal->status === self::STATUS_REVERSED
                && $journal->getOriginal('reversed_by_journal_id') === null
                && $journal->reversed_by_journal_id !== null;

            $immutableDirty = array_intersect(
                array_keys($journal->getDirty()),
                ['tenant_id', 'journal_number', 'reference_type', 'reference_id', 'description', 'posted_at', 'accounting_period', 'reverses_journal_id'],
            );

            if (! $allowedTransition || $immutableDirty !== []) {
                throw new \RuntimeException('Posted ledger journals are append-only; use a contra reversal journal instead of mutating.');
            }
        });

        static::deleting(function (LedgerJournal $journal): void {
            if ($journal->status !== self::STATUS_PENDING) {
                throw new \RuntimeException('Posted or reversed ledger journals cannot be deleted.');
            }
        });
    }

    public function getConnectionName(): ?string
    {
        try {
            $context = app(TenantContext::class);
        } catch (\Throwable) {
            return $this->connection;
        }

        return $context->connectionName() ?? $this->connection;
    }

    /**
     * @return HasMany<LedgerEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'journal_id');
    }
}
