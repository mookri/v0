<?php

namespace App\Platform\Identity\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Step-up proof row (central, plain — user-keyed, never tenant-scoped).
 *
 * @property string $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $verified_at
 * @property string|null $ip
 */
final class MfaStepUp extends Model
{
    use HasUlids;

    protected $connection = 'central';

    protected $fillable = ['user_id', 'verified_at', 'ip'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }
}
