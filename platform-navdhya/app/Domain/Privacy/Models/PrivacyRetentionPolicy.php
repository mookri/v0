<?php

namespace App\Domain\Privacy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class PrivacyRetentionPolicy extends Model
{
    use HasUlids;

    protected $connection = 'central';

    protected $table = 'privacy_retention_policies';

    protected $fillable = [
        'dataset_key',
        'retention_days',
        'legal_hold',
        'action',
    ];

    protected $casts = [
        'retention_days' => 'integer',
        'legal_hold' => 'boolean',
    ];
}

