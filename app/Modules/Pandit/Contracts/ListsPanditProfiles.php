<?php

namespace App\Modules\Pandit\Contracts;

/**
 * Pandit profile read port (deptrac-blessed Contracts surface): other modules
 * list verified profiles and resolve profiles without importing pandit models.
 * Ambient tenant context scopes every call.
 *
 * @phpstan-type PanditPublicProfile array{id: string, display_name: string, rating_avg: float, rating_count: int, specializations: list<string>, base_rate_minor: int|null, currency: string}
 * @phpstan-type PanditProfileData array{id: string, display_name: string, bio: string|null, languages: list<string>|null, specializations: list<string>|null, experience_years: int|null, certification_refs: list<string>|null, base_rate_minor: int|null, currency: string, market: string, rating_avg: float, rating_count: int, total_bookings: int, verification_status: string, status: string}
 * @phpstan-type PanditAdminProfile array{id: string, display_name: string, status: string, verification_status: string}
 * @phpstan-type PanditPage array{limit: int, has_more: bool, next_cursor: string|null}
 */
interface ListsPanditProfiles
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: list<PanditPublicProfile>, page: PanditPage}
     */
    public function publicProfiles(array $filters = [], int $limit = 20): array;

    /**
     * @return PanditProfileData|null
     */
    public function profileById(string $id): ?array;

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: list<PanditAdminProfile>, page: PanditPage}
     */
    public function adminProfiles(array $filters = [], int $limit = 20): array;
}
