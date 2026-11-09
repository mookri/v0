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
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class LedgerJournal extends Model
{
    use HasUlids;

    public const STATUS_POSTED = 'POSTED';
    public const STATUS_VOID = 'VOID';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'journal_number',
        'reference_type',
        'reference_id',
        'description',
        'posted_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'posted_at' => 'datetime',
        ];
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
