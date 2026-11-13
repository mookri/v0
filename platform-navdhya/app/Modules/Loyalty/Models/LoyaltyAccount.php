<?php

namespace App\Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Devotee loyalty account model (T-JYRW-01).
 *
 * @property int $id
 * @property string $account_id
 * @property string $user_id
 * @property string $tier_code
 * @property int $points_balance
 * @property int $lifetime_points_accrued
 */
final class LoyaltyAccount extends Model
{
    protected $table = 'loyalty_accounts';

    protected $connection = 'central';

    protected $guarded = [];

    /**
     * @return HasMany<LoyaltyEntry>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(LoyaltyEntry::class, 'account_id', 'account_id');
    }
}

