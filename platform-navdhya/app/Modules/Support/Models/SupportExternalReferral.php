<?php

declare(strict_types=1);

namespace App\Modules\Support\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class SupportExternalReferral extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';
    protected $table = 'support_external_referrals';

    protected $fillable = [
        'tenant_id', 'case_id', 'channel', 'external_reference',
        'adapter_mode', 'referred_at', 'last_reconciled_at',
        'external_status', 'payload_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'referred_at' => 'datetime',
            'last_reconciled_at' => 'datetime',
            'payload_snapshot' => 'array',
        ];
    }

    public function getConnectionName(): ?string
    {
        try {
            return app(\App\Platform\Tenancy\TenantContext::class)->connectionName() ?? $this->connection;
        } catch (\Throwable) {
            return $this->connection;
        }
    }
}

