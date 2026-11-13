<?php

namespace App\Domain\Privacy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class VerificationProviderRegistration extends Model
{
    use HasUlids;

    protected $connection = 'central';

    protected $table = 'verification_provider_registry';

    protected $fillable = [
        'provider_code',
        'jurisdiction',
        'dpa_executed',
        'dpa_ref',
        'subprocessor_approved',
        'residency_region',
        'enabled',
    ];

    protected $casts = [
        'dpa_executed' => 'boolean',
        'subprocessor_approved' => 'boolean',
        'enabled' => 'boolean',
    ];
}

