<?php

namespace App\Modules\Temple\Contracts;

/**
 * Temple profile read port (deptrac-blessed Contracts surface): other modules
 * list verified/active profiles and resolve profiles without importing temple models.
 * Ambient tenant context scopes every call.
 *
 * @phpstan-type TemplePublicProfile array{id: string, slug: string, name: string, trust_name: string, type: string, city: string, state: string, lat: float|null, lng: float|null, images: list<string>, amenities: list<string>, description: string|null, rating_avg: float, review_count: int, verification_status: string, status: string}
 * @phpstan-type TempleAdminProfile array{id: string, slug: string, name: string, trust_name: string, type: string, city: string, state: string, status: string, verification_status: string, owner_id: string}
 * @phpstan-type TemplePartnerProfile array{id: string, slug: string, name: string, trust_name: string, type: string, city: string, state: string, status: string, verification_status: string, role: string}
 * @phpstan-type TemplePage array{limit: int, has_more: bool, next_cursor: string|null}
 */
interface ListsTemples
{
    /**
     * Public profiles: only VERIFIED + ACTIVE temples, no capability required.
     * Machine principals need `temple:read` scope.
     *
     * @param  array<string, mixed>  $filters
     * @return array{items: list<array{id: string, slug: string, name: string, trust_name: string, type: string, city: string, state: string, lat: float|null, lng: float|null, images: list<string>, amenities: list<string>, description: string|null, rating_avg: float, review_count: int, verification_status: string, status: string}>, page: array{limit: int, has_more: bool, next_cursor: string|null}}
     */
    public function publicProfiles(array $filters = [], int $limit = 20): array;

    /**
     * @return array{id: string, slug: string, name: string, trust_name: string, type: string, city: string, state: string, lat: float|null, lng: float|null, images: list<string>, amenities: list<string>, description: string|null, rating_avg: float, review_count: int, verification_status: string, status: string}|null
     */
    public function profileById(string $id): ?array;

    /**
     * Admin profiles: all statuses, capability-gated.
     *
     * @param  array<string, mixed>  $filters
     * @return array{items: list<array{id: string, slug: string, name: string, trust_name: string, type: string, city: string, state: string, status: string, verification_status: string, owner_id: string}>, page: array{limit: int, has_more: bool, next_cursor: string|null}}
     */
    public function adminProfiles(array $filters = [], int $limit = 20): array;

    /**
     * Profiles by owner/manager identity for partner self-serve.
     *
     * @param  array<string, mixed>  $filters
     * @return array{items: list<array{id: string, slug: string, name: string, trust_name: string, type: string, city: string, state: string, status: string, verification_status: string, role: string}>, page: array{limit: int, has_more: bool, next_cursor: string|null}}
     */
    public function partnerProfiles(string $identityId, array $filters = [], int $limit = 20): array;
}
