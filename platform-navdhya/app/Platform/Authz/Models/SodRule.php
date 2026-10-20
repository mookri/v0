<?php

namespace App\Platform\Authz\Models;

use Database\Factories\SodRuleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Segregation-of-duties rule (SEC-011 §7.1, central connection).
 *
 * Deliberately NOT using BelongsToTenant: platform-default rows carry NULL
 * tenant_id and every read combines them with tenant overrides explicitly
 * (`whereNull tenant_id OR tenant_id = ?`). A tenant global scope would
 * hide exactly the rows that must always apply. Tenant overrides win by
 * code (downgrade block→warn); rule-admin CRUD with operator-consent
 * checks lands separately — until then seeds are the only rows.
 *
 * @property string $id
 * @property string|null $tenant_id
 * @property string $code
 * @property string $name
 * @property string $kind
 * @property array<string>|null $role_set
 * @property array<string>|null $permission_set
 * @property string $severity
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $operator_consented_at
 */
final class SodRule extends Model
{
    /** @use HasFactory<SodRuleFactory> */
    use HasFactory;
    use HasUlids;

    public const KIND_CONFLICT = 'conflict';

    public const KIND_APPROVAL_CHAIN = 'approval_chain';

    public const SEVERITY_BLOCK = 'block';

    public const SEVERITY_WARN = 'warn';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DISABLED = 'disabled';

    protected $connection = 'central';

    protected $fillable = [
        'tenant_id', 'code', 'name', 'kind', 'role_set', 'permission_set',
        'severity', 'status', 'operator_consented_at',
    ];

    protected function casts(): array
    {
        return [
            'role_set' => 'array',
            'permission_set' => 'array',
            'operator_consented_at' => 'datetime',
        ];
    }

    /**
     * @return Factory<SodRule>
     */
    protected static function newFactory(): Factory
    {
        return SodRuleFactory::new();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
