<?php

namespace App\Modules\Temple;

use App\Modules\Temple\Models\Temple;
use App\Modules\Temple\Models\TempleManager;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Trait providing consistent manager/owner authorization helpers for the Temple domain.
 * Enforces horizontal tenant and manager boundaries across partner endpoints.
 */
trait AuthorizesTempleManager
{
    /**
     * @return list<string>
     */
    protected function currentManagerIdentities(): array
    {
        $context = app(TenantContext::class);
        $identities = [
            (string) $context->membershipId(),
            (string) auth()->id(),
            (string) ($context->actor()['id'] ?? ''),
            (string) request()?->user()?->getAuthIdentifier(),
        ];

        return array_values(array_unique(array_filter($identities, static fn (string $id): bool => $id !== '')));
    }

    /**
     * Resolve the temple ensuring the current caller is either the owner
     * or an accepted manager of this temple in the ambient tenant.
     *
     * @throws ModelNotFoundException
     */
    protected function findAuthorizedTemple(string $tenantId, string $templeId): Temple
    {
        $identities = $this->currentManagerIdentities();

        if (empty($identities)) {
            throw (new ModelNotFoundException())->setModel(Temple::class, [$templeId]);
        }

        /** @var Temple|null $temple */
        $temple = Temple::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $templeId)
            ->where(static function ($q) use ($identities): void {
                $q->whereIn('owner_id', $identities)
                    ->orWhereHas('managers', static function ($sub) use ($identities): void {
                        $sub->whereIn('user_id', $identities)
                            ->whereNotNull('accepted_at');
                    });
            })
            ->first();

        if ($temple === null) {
            throw (new ModelNotFoundException())->setModel(Temple::class, [$templeId]);
        }

        return $temple;
    }

    /**
     * Check if the caller is the owner of the temple.
     */
    protected function isTempleOwner(string $tenantId, string $templeId): bool
    {
        $identities = $this->currentManagerIdentities();

        if (empty($identities)) {
            return false;
        }

        return Temple::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $templeId)
            ->where(static function ($q) use ($identities): void {
                $q->whereIn('owner_id', $identities)
                    ->orWhereHas('managers', static function ($sub) use ($identities): void {
                        $sub->whereIn('user_id', $identities)
                            ->where('role', TempleManager::ROLE_OWNER)
                            ->whereNotNull('accepted_at');
                    });
            })
            ->exists();
    }
}
