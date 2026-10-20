<?php

namespace Database\Factories;

use App\Platform\Audit\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * tenant_id is filled from the ambient TenantContext by BelongsToTenant
 * at create time; the hash chain columns carry test defaults.
 *
 * @extends Factory<AuditLog>
 */
final class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor' => ['principal_id' => 'user:1', 'kind' => 'user', 'user_id' => '1'],
            'action' => 'test.action',
            'resource' => ['module' => 'platform', 'resource_type' => 'policy', 'resource_id' => 'test'],
            'before' => null,
            'after' => null,
            'request_id' => 'req_test',
            'prev_hash' => null,
            'hash' => hash('sha256', $this->faker->unique()->uuid()),
            'occurred_at' => now(),
        ];
    }
}
