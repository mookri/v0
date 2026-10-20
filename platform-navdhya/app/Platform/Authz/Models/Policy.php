<?php

namespace App\Platform\Authz\Models;

use App\Platform\Authz\Contracts\PolicyStatus;
use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\IncludesPlatformDefaults;
use Database\Factories\PolicyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy record (SEC-021, K-009) on the central authorization group
 * (TEN-003 §2.1). tenant_id NULL marks a platform-default policy:
 * overridable but not silently removable (LD-15) — platform defaults are
 * readable by every tenant (IncludesPlatformDefaults) while mutations go
 * through platform tooling only.
 *
 * tenant_id is mass-assignable so kernel maintenance paths can write
 * explicit tenant scoping and platform-default rows (tenant_id NULL);
 * tenant admin surfaces never accept it — the policy grammar validator
 * rejects undeclared fields (strict validation, doc 06 §3.1).
 *
 * @property string $id
 * @property string|null $tenant_id
 * @property string $name
 * @property string|null $description
 * @property string $effect
 * @property array<string, mixed> $capability_selector
 * @property list<array<string, mixed>>|null $scope_bindings
 * @property list<array<string, mixed>>|null $conditions
 * @property int $priority
 * @property string $status
 * @property int $version
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class Policy extends Model implements IncludesPlatformDefaults
{
    use BelongsToTenant;
    /** @use HasFactory<PolicyFactory> */
    use HasFactory;
    use HasUlids;

    protected $connection = 'central';

    protected $fillable = [
        'tenant_id', 'name', 'description', 'effect', 'capability_selector',
        'scope_bindings', 'conditions', 'priority', 'status', 'version',
    ];

    protected function casts(): array
    {
        return [
            'capability_selector' => 'array',
            'scope_bindings' => 'array',
            'conditions' => 'array',
            'priority' => 'integer',
            'version' => 'integer',
        ];
    }

    /**
     * @return Factory<Policy>
     */
    protected static function newFactory(): Factory
    {
        return PolicyFactory::new();
    }

    public function includesPlatformDefaults(): bool
    {
        return true;
    }

    public function isPlatformDefault(): bool
    {
        return $this->tenant_id === null;
    }

    public function statusEnum(): PolicyStatus
    {
        return PolicyStatus::from((string) $this->status);
    }

    /**
     * Full policy payload shared by the platform.policy.* events
     * (AC-T-0K01-01-3: events carry the full selector payload).
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'policy_id' => (string) $this->getKey(),
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'description' => $this->description,
            'effect' => $this->effect,
            'capability_selector' => $this->capability_selector,
            'scope_bindings' => $this->scope_bindings ?? [],
            'conditions' => $this->conditions ?? [],
            'priority' => (int) $this->priority,
            'status' => $this->status,
            'version' => (int) $this->version,
        ];
    }
}
