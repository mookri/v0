<?php

namespace App\Modules\Pandit;

use App\Modules\Pandit\Contracts\ListsPanditProfiles;
use App\Modules\Pandit\Models\PanditProfile;
use App\Platform\Api\CursorPagination;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\Request;

/**
 * Pandit profile queries (deptrac-blessed Contracts implementation):
 * other modules list verified profiles and resolve profiles without
 * importing pandit models. Ambient tenant context scopes every call.
 *
 * @phpstan-import-type PanditPublicProfile from ListsPanditProfiles
 * @phpstan-import-type PanditProfileData from ListsPanditProfiles
 * @phpstan-import-type PanditAdminProfile from ListsPanditProfiles
 * @phpstan-import-type PanditPage from ListsPanditProfiles
 */
final class PanditProfileQueries implements ListsPanditProfiles
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: list<PanditPublicProfile>, page: PanditPage}
     */
    public function publicProfiles(array $filters = [], int $limit = 20, ?string $cursor = null): array
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();

        $query = PanditProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('verification_status', PanditProfile::VERIFICATION_VERIFIED)
            ->where('status', PanditProfile::STATUS_ACTIVE);

        if (isset($filters['specialization'])) {
            $query->whereJsonContains('specializations', $filters['specialization']);
        }

        if (isset($filters['market'])) {
            $query->where('market', $filters['market']);
        }

        if (isset($filters['min_rating'])) {
            $query->where('rating_avg', '>=', $filters['min_rating']);
        }

        $request = new Request(array_merge(['limit' => $limit, 'cursor' => $cursor], $filters));
        [$items, $page] = CursorPagination::paginate($query, $request);

        return [
            'items' => array_map(static fn (PanditProfile $p): array => [
                'id' => (string) $p->getKey(),
                'display_name' => $p->display_name,
                'rating_avg' => (float) $p->rating_avg,
                'rating_count' => (int) $p->rating_count,
                'specializations' => $p->specializations ?? [],
                'base_rate_minor' => $p->base_rate_minor,
                'currency' => $p->currency,
            ], $items),
            'page' => $page,
        ];
    }

    /**
     * @return PanditProfileData|null
     */
    public function profileById(string $id): ?array
    {
        $profile = PanditProfile::query()
            ->where('tenant_id', (string) app(TenantContext::class)->tenantId())
            ->where('id', $id)
            ->first();

        if ($profile === null) {
            return null;
        }

        return [
            'id' => (string) $profile->getKey(),
            'display_name' => $profile->display_name,
            'bio' => $profile->bio,
            'languages' => $profile->languages,
            'specializations' => $profile->specializations,
            'experience_years' => $profile->experience_years,
            'certification_refs' => $profile->certification_refs,
            'base_rate_minor' => $profile->base_rate_minor,
            'currency' => $profile->currency,
            'market' => $profile->market,
            'rating_avg' => (float) $profile->rating_avg,
            'rating_count' => (int) $profile->rating_count,
            'total_bookings' => (int) $profile->total_bookings,
            'verification_status' => $profile->verification_status,
            'status' => $profile->status,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: list<PanditAdminProfile>, page: PanditPage}
     */
    public function adminProfiles(array $filters = [], int $limit = 20, ?string $cursor = null): array
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();

        $query = PanditProfile::query()
            ->where('tenant_id', $tenantId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['verification_status'])) {
            $query->where('verification_status', $filters['verification_status']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('display_name', 'like', '%'.$filters['search'].'%');
            });
        }

        $request = new Request(array_merge(['limit' => $limit, 'cursor' => $cursor], $filters));
        [$items, $page] = CursorPagination::paginate($query, $request);

        return [
            'items' => array_map(static fn (PanditProfile $p): array => [
                'id' => (string) $p->getKey(),
                'display_name' => $p->display_name,
                'status' => $p->status,
                'verification_status' => $p->verification_status,
            ], $items),
            'page' => $page,
        ];
    }
}
