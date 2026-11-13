<?php

namespace App\Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only cryptographic loyalty ledger entry model (T-JYRW-01).
 *
 * @property int $id
 * @property string $entry_id
 * @property string $account_id
 * @property string $event_type
 * @property int $points_delta
 * @property string $previous_hash
 * @property string $entry_hash
 * @property array<string, mixed> $metadata
 */
final class LoyaltyEntry extends Model
{
    protected $table = 'loyalty_entries';

    protected $connection = 'central';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'points_delta' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<LoyaltyAccount, LoyaltyEntry>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(LoyaltyAccount::class, 'account_id', 'account_id');
    }
}

