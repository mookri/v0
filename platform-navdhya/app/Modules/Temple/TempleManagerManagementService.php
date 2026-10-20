<?php

namespace App\Modules\Temple;

use App\Modules\Temple\Contracts\ManagesTempleManagers;
use App\Modules\Temple\Models\Temple;
use App\Modules\Temple\Models\TempleManager;/**
 * Temple manager management service (deptrac-blessed ManagesTempleManagers implementation).
 * Ambient tenant context scopes every call.
 */

final class TempleManagerManagementService implements ManagesTempleManagers
{
    /**
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException
     */
    public function addManager(string $templeId, array $data): array
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();
        $identityId = (string) app(\App\Platform\Tenancy\TenantContext::class)->membershipId();

        $temple = \App\Modules\Temple\Models\Temple::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $data['temple_id'] ?? $templeId)
            ->firstOrFail();

        // Verify ownership
        $isOwner = \App\Modules\Temple\Models\TempleManager::query()
            ->where('tenant_id', $tenantId)
            ->where('temple_id', $templeId)
            ->where('user_id', $identityId)
            ->where('role', \App\Modules\Temple\Models\TempleManager::ROLE_OWNER)
            ->exists();

        if (!$isOwner) {
            throw new \InvalidArgumentException('Only owners can add managers.');
        }

        $exists = \App\Modules\Temple\Models\TempleManager::query()
            ->where('tenant_id', $tenantId)
            ->where('temple_id', $templeId)
            ->where('user_id', $data['user_id'])
            ->exists();

        if ($exists) {
            throw new \InvalidArgumentException('User is already a manager of this temple.');
        }

        $manager = new TempleManager();
        $manager->forceFill([
            'tenant_id' => $tenantId,
            'temple_id' => $templeId,
            'user_id' => $data['user_id'],
            'role' => $data['role'] ?? \App\Modules\Temple\Models\TempleManager::ROLE_MANAGER,
            'invited_at' => now()->toISOString(),
            'accepted_at' => null,
        ]);
        $manager->save();

        // Audit would go here

        return $this->toManager($manager);
    }

    /**
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException
     */
    public function removeManager(string $templeId, string $managerId): void
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();
        $identityId = (string) app(\App\Platform\Tenancy\TenantContext::class)->membershipId();

        $temple = \App\Modules\Temple\Models\Temple::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $templeId)
            ->firstOrFail();

        $isOwner = \App\Modules\Temple\Models\TempleManager::query()
            ->where('tenant_id', $tenantId)
            ->where('temple_id', $templeId)
            ->where('user_id', $identityId)
            ->where('role', \App\Modules\Temple\Models\TempleManager::ROLE_OWNER)
            ->exists();

        if (!$isOwner) {
            throw new \InvalidArgumentException('Only owners can remove managers.');
        }

        $manager = \App\Modules\Temple\Models\TempleManager::query()
            ->where('tenant_id', $tenantId)
            ->where('temple_id', $templeId)
            ->where('id', $managerId)
            ->firstOrFail();

        // Prevent removing the owner
        if ($manager->role === \App\Modules\Temple\Models\TempleManager::ROLE_OWNER) {
            throw new \InvalidArgumentException('Cannot remove the owner.');
        }

        $manager->delete();

        // Audit would go here
    }

    /**
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException
     *
     * @return array{id: string, temple_id: string, user_id: string, role: string, invited_at: string, accepted_at: string|null}
     */
    public function updateManagerRole(string $templeId, string $managerId, string $role): array
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();
        $identityId = (string) app(\App\Platform\Tenancy\TenantContext::class)->membershipId();

        $temple = \App\Modules\Temple\Models\Temple::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $templeId)
            ->firstOrFail();

        $isOwner = \App\Modules\Temple\Models\TempleManager::query()
            ->where('tenant_id', $tenantId)
            ->where('temple_id', $templeId)
            ->where('user_id', $identityId)
            ->where('role', \App\Modules\Temple\Models\TempleManager::ROLE_OWNER)
            ->exists();

        if (!$isOwner) {
            throw new \InvalidArgumentException('Only owners can update manager roles.');
        }

        $manager = \App\Modules\Temple\Models\TempleManager::query()
            ->where('tenant_id', (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId())
            ->where('temple_id', $templeId)
            ->where('id', $managerId)
            ->firstOrFail();

        // Prevent changing owner role
        if ($manager->role === \App\Modules\Temple\Models\TempleManager::ROLE_OWNER) {
            throw new \InvalidArgumentException('Cannot change owner role.');
        }

        $manager->forceFill(['role' => $role])->save();

        return $this->toManager($manager);
    }

    /**
     * @return array{items: list<array{id: string, temple_id: string, user_id: string, role: string, invited_at: string, accepted_at: string|null}>}
     */
    public function listManagers(string $templeId): array
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();

        $managers = \App\Modules\Temple\Models\TempleManager::query()
            ->where('tenant_id', $tenantId)
            ->where('temple_id', $templeId)
            ->with('user')
            ->get();

        return ['items' => $managers->map(fn ($m) => $this->toManager($m))->values()->all()];
    }

    /**
     * @return array{id: string, temple_id: string, user_id: string, role: string, invited_at: string, accepted_at: string|null}
     */
    private function toManager(\App\Modules\Temple\Models\TempleManager $m): array
    {
        return [
            'id' => (string) $m->getKey(),
            'temple_id' => (string) $m->temple_id,
            'user_id' => (string) $m->user_id,
            'role' => $m->role,
            'invited_at' => $m->invited_at?->toISOString(),
            'accepted_at' => $m->accepted_at?->toISOString(),
        ];
    }
}
