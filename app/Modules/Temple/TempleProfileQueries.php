<?php

namespace App\Modules\Temple;

use App\Modules\Temple\Contracts\ListsTemples;
use App\Modules\Temple\Models\Temple;
use App\Platform\Api\CursorPagination;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Temple profile query implementation (deptrac-blessed ListsTemples implementation).
 * Ambient tenant context scopes every call.
 */
final class TempleProfileQueries implements ListsTemples
{
    /**
     * Public profiles: only VERIFIED + ACTIVE temples.
     * Machine principals need `temple:read` scope.
     *
     * @param  array<string, mixed>  $filters
     * @return array{items: list<array{id: string, slug: string, name: string, trust_name: string, type: string, city: string, state: string, lat: float|null, lng: float|null, images: list<string>, amenities: list<string>, description: string|null, rating_avg: float, review_count: int, verification_status: string, status: string}>, page: array{limit: int, has_more: bool, next_cursor: string|null}}
     */
    public function publicProfiles(array $filters = [], int $limit = 20): array
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();

        $query = Temple::query()
            ->where('tenant_id', $tenantId)
            ->where('verification_status', Temple::VERIFICATION_VERIFIED)
            ->where('status', Temple::STATUS_ACTIVE);

        $this->applyFilters($query, $filters);

        $request = new Request(array_merge(['limit' => $limit], $filters));
        [$items, $page] = CursorPagination::paginate($query, $request, $limit);

        return [
            'items' => array_map(fn (Temple $t) => $this->toPublicProfile($t), $items),
            'page' => $page,
        ];
    }

    /**
     * @return array{
     *   id: string,
     *   slug: string,
     *   name: string,
     *   trust_name: string,
     *   type: string,
     *   city: string,
     *   state: string,
     *   lat: float|null,
     *   lng: float|null,
     *   images: list<string>,
     *   amenities: list<string>,
     *   description: string|null,
     *   rating_avg: float,
     *   review_count: int,
     *   verification_status: string,
     *   status: string
     * }|null
     */
    public function profileById(string $id): ?array
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();

        $temple = Temple::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        if ($temple === null) {
            return null;
        }

        return $this->toPublicProfile($temple);
    }

    /**
     * Admin profiles: all statuses, capability-gated.
     *
     * @param  array<string, mixed>  $filters
     * @return array{items: list<array{id: string, slug: string, name: string, trust_name: string, type: string, city: string, state: string, status: string, verification_status: string, owner_id: string}>, page: array{limit: int, has_more: bool, next_cursor: string|null}}
     */
    public function adminProfiles(array $filters = [], int $limit = 20): array
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();

        $query = Temple::query()->where('tenant_id', $tenantId);

        if (isset($filters['status']) && is_string($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['verification_status']) && is_string($filters['verification_status'])) {
            $query->where('verification_status', $filters['verification_status']);
        }
        if (isset($filters['search']) && is_string($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('trust_name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $request = new \Illuminate\Http\Request(array_merge(['limit' => $limit], $filters));
        [$items, $page] = \App\Platform\Api\CursorPagination::paginate($query, $request, $limit);

        return [
            'items' => array_map(fn (Temple $t) => $this->toAdminProfile($t), $items),
            'page' => $page,
        ];
    }

    /**
     * Profiles by owner/manager identity for partner self-serve.
     *
     * @param  array<string, mixed>  $filters
     * @return array{items: list<array{id: string, slug: string, name: string, trust_name: string, type: string, city: string, state: string, status: string, verification_status: string, role: string}>, page: array{limit: int, has_more: bool, next_cursor: string|null}}
     */
    public function partnerProfiles(string $identityId, array $filters = [], int $limit = 20): array
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();

        $query = \App\Modules\Temple\Models\Temple::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('managers', function (Builder $q) use ($identityId): void {
                $q->where('user_id', $identityId);
            });

        if (isset($filters['status']) && is_string($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $request = new \Illuminate\Http\Request(array_merge(['limit' => $limit], $filters));
        [$items, $page] = \App\Platform\Api\CursorPagination::paginate($query, $request, $limit);

        return [
            'items' => array_map(fn ($t) => $this->toPartnerProfile($t, $identityId), $items),
            'page' => $page,
        ];
    }

    /**
 * @param  \Illuminate\Database\Eloquent\Builder<\App\Modules\Temple\Models\Temple>  $query
 * @param  array<string, mixed>  $filters
 */
    private function applyFilters(\Illuminate\Database\Eloquent\Builder $query, array $filters): void
    {
        if (isset($filters['city']) && is_string($filters['city'])) {
            $query->where('city', $filters['city']);
        }
        if (isset($filters['state']) && is_string($filters['state'])) {
            $query->where('state', $filters['state']);
        }
        if (isset($filters['amenities']) && is_array($filters['amenities'])) {
            foreach ($filters['amenities'] as $amenity) {
                $query->whereJsonContains('amenities', $amenity);
            }
        }
    }

    /**
     * @return array{
     *   id: string,
     *   slug: string,
     *   name: string,
     *   trust_name: string,
     *   type: string,
     *   city: string,
     *   state: string,
     *   lat: float|null,
     *   lng: float|null,
     *   images: list<string>,
     *   amenities: list<string>,
     *   description: string|null,
     *   rating_avg: float,
     *   review_count: int,
     *   verification_status: string,
     *   status: string
     * }
     */
    private function toPublicProfile(\App\Modules\Temple\Models\Temple $t): array
    {
        return [
            'id' => (string) $t->getKey(),
            'slug' => $t->slug,
            'name' => $t->name,
            'trust_name' => $t->trust_name,
            'type' => $t->type,
            'city' => $t->city,
            'state' => $t->state,
            'lat' => $t->lat,
            'lng' => $t->lng,
            'images' => $t->images ?? [],
            'amenities' => $t->amenities ?? [],
            'description' => $t->description,
            'rating_avg' => 0.0,
            'review_count' => 0,
            'verification_status' => $t->verification_status,
            'status' => $t->status,
        ];
    }

    /**
     * @return array{
     *   id: string,
     *   slug: string,
     *   name: string,
     *   trust_name: string,
     *   type: string,
     *   city: string,
     *   state: string,
     *   status: string,
     *   verification_status: string,
     *   owner_id: string
     * }
     */
    private function toAdminProfile(\App\Modules\Temple\Models\Temple $t): array
    {
        return [
            'id' => (string) $t->getKey(),
            'slug' => $t->slug,
            'name' => $t->name,
            'trust_name' => $t->trust_name,
            'type' => $t->type,
            'city' => $t->city,
            'state' => $t->state,
            'status' => $t->status,
            'verification_status' => $t->verification_status,
            'owner_id' => $t->owner_id,
        ];
    }

    /**
     * @return array{
     *   id: string,
     *   slug: string,
     *   name: string,
     *   trust_name: string,
     *   type: string,
     *   city: string,
     *   state: string,
     *   status: string,
     *   verification_status: string,
     *   role: string
     * }
     */
    private function toPartnerProfile(\App\Modules\Temple\Models\Temple $t, string $identityId): array
    {
        $role = 'viewer';
        foreach ($t->managers as $manager) {
            if ((string) $manager->user_id === $identityId) {
                $role = $manager->role;
                break;
            }
        }

        return [
            'id' => (string) $t->getKey(),
            'slug' => $t->slug,
            'name' => $t->name,
            'trust_name' => $t->trust_name,
            'type' => $t->type,
            'city' => $t->city,
            'state' => $t->state,
            'status' => $t->status,
            'verification_status' => $t->verification_status,
            'role' => $role,
        ];
    }
}
