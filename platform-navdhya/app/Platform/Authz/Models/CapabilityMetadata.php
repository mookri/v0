<?php

namespace App\Platform\Authz\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\CapabilityMetadataFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Capability registry row (SEC-020): the permission registry with
 * structured parse columns. Every <module>:<resource>:<action> code
 * resolves to module/resource/action plus version + sensitive flag.
 * Materialized per tenant by platform:sync-permissions (CON-005) — the
 * sync writes explicit tenant_id values (kernel maintenance path), hence
 * tenant_id is mass-assignable on this model only.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $code
 * @property string $name
 * @property string $module
 * @property string $resource
 * @property string $action
 * @property int $version
 * @property bool $sensitive
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class CapabilityMetadata extends Model
{
    use BelongsToTenant;
    /** @use HasFactory<CapabilityMetadataFactory> */
    use HasFactory;
    use HasUlids;

    protected $connection = 'central';

    protected $fillable = [
        'tenant_id', 'code', 'name', 'module', 'resource', 'action',
        'version', 'sensitive', 'description',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'sensitive' => 'boolean',
        ];
    }

    /**
     * @return Factory<CapabilityMetadata>
     */
    protected static function newFactory(): Factory
    {
        return CapabilityMetadataFactory::new();
    }
}
