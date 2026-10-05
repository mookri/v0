<?php

namespace App\Modules\Temple;

use App\Modules\Temple\Contracts\ManagesTemplePujas;
use App\Modules\Temple\Models\Temple;
use App\Modules\Temple\Models\TemplePuja;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Temple puja management service (deptrac-blessed ManagesTemplePujas implementation).
 * Ambient tenant context scopes every call.
 */
final class TemplePujaManagementService implements ManagesTemplePujas
{
    public function assignPuja(string $templeId, array $data): array
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();

        try {
            $temple = Temple::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $templeId)
                ->firstOrFail();
        } catch (ModelNotFoundException) {
            throw new TempleNotFoundException('Temple not found.');
        }

        $pujaAssignment = new TemplePuja();
        $pujaAssignment->forceFill([
            'tenant_id' => $tenantId,
            'temple_id' => $templeId,
            'puja_id' => $data['puja_id'],
            'price_override_minor' => $data['price_override_minor'] ?? null,
            'capacity' => $data['capacity'] ?? null,
            'schedule_json' => $data['schedule_json'] ?? null,
            'status' => TemplePuja::STATUS_ACTIVE,
        ]);
        $pujaAssignment->save();

        // Audit would go here

        return $this->toAssignment($pujaAssignment);
    }

    public function updatePuja(string $templeId, string $pujaId, array $data): array
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();

        try {
            $puja = TemplePuja::query()
                ->where('tenant_id', $tenantId)
                ->where('temple_id', $templeId)
                ->where('id', $pujaId)
                ->firstOrFail();
        } catch (ModelNotFoundException) {
            throw new TemplePujaNotFoundException('Puja assignment not found.');
        }

        $allowed = ['price_override_minor', 'capacity', 'schedule_json', 'status'];
        $puja->forceFill(array_intersect_key($data, array_flip($allowed)))->save();

        return $this->toAssignment(
            TemplePuja::query()->where('id', $pujaId)->firstOrFail()
        );
    }

    public function removePuja(string $templeId, string $pujaId): void
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();

        try {
            $puja = TemplePuja::query()
                ->where('tenant_id', $tenantId)
                ->where('temple_id', $templeId)
                ->where('id', $pujaId)
                ->firstOrFail();
        } catch (ModelNotFoundException) {
            throw new TemplePujaNotFoundException('Puja assignment not found.');
        }

        $puja->delete();

        // Audit would go here
    }

    public function listPujas(string $templeId, array $filters = [], int $limit = 20): array
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();

        $query = \App\Modules\Temple\Models\TemplePuja::query()
            ->where('tenant_id', (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId())
            ->where('temple_id', $templeId);

        if (isset($filters['status']) && is_string($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $request = new \Illuminate\Http\Request(['limit' => $limit]);
        $paginator = \App\Platform\Api\CursorPagination::paginate($query, $request, $limit);

        return [
            'items' => array_map(fn ($p) => $this->toAssignment($p), $paginator[0]),
            'page' => $paginator[1],
        ];
    }

    /**
     * @return array{
     *   id: string,
     *   temple_id: string,
     *   puja_id: string,
     *   price_override_minor: int|null,
     *   capacity: int|null,
     *   schedule_json: string|null,
     *   status: string,
     *   created_at: string
     * }
     */
    private function toAssignment(\App\Modules\Temple\Models\TemplePuja $p): array
    {
        return [
            'id' => (string) $p->getKey(),
            'temple_id' => (string) $p->temple_id,
            'puja_id' => (string) $p->puja_id,
            'price_override_minor' => $p->price_override_minor,
            'capacity' => $p->capacity,
            'schedule_json' => $p->schedule_json,
            'status' => $p->status,
            'created_at' => $p->created_at?->toISOString(),
        ];
    }
}
