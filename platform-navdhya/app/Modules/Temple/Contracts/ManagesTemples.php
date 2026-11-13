<?php

namespace App\Modules\Temple\Contracts;

/**
 * Temple management port (deptrac-blessed Contracts surface).
 * Ambient tenant context scopes every call.
 *
 * @phpstan-type TempleCreated array{id: string, slug: string, name: string, trust_name: string, type: string, city: string, state: string, status: string, verification_status: string, created_at: string}
 * @phpstan-type TempleUpdated array{id: string, slug: string, name: string, trust_name: string, type: string, city: string, state: string, status: string, verification_status: string, updated_at: string}
 * @phpstan-type TemplePartnerProfile array{id: string, slug: string, name: string, trust_name: string, type: string, address: string, city: string, state: string, lat: float|null, lng: float|null, images: list<string>, amenities: list<string>, description: string|null, status: string, verification_status: string, created_at: string, updated_at: string, managers: list<array{id: string, user_id: string, role: string}>}
 */
interface ManagesTemples
{
    /**
     * Create a new temple (owner is the calling identity).
     *
     * @param  array{
     *   slug: string,
     *   name: string,
     *   trust_name: string,
     *   type: string, // 'individual' | 'trust'
     *   address: string,
     *   city: string,
     *   state: string,
     *   lat: float|null,
     *   lng: float|null,
     *   images: list<string>,
     *   amenities: list<string>,
     *   description: string|null
     * }  $data
     *
     * @return array{id: string, slug: string, name: string, trust_name: string, type: string, city: string, state: string, status: string, verification_status: string, created_at: string}
     *
     * @throws \InvalidArgumentException when slug is not unique
     */
    public function create(array $data): array;

    /**
     * Update temple details (partial update, owner/admin only).
     *
     * @param  array<string, mixed>  $data
     *
     * @return array{id: string, slug: string, name: string, trust_name: string, type: string, city: string, state: string, status: string, verification_status: string, updated_at: string}
     *
     * @throws \App\Modules\Temple\TempleNotFoundException
     * @throws \InvalidArgumentException when state transition is invalid
     */
    public function update(string $templeId, array $data): array;

    /**
     * Verify temple (admin only, step-up required). Does not publish draft visibility.
     *
     * @return array{id: string, slug: string, name: string, trust_name: string, type: string, city: string, state: string, status: string, verification_status: string, updated_at: string}
     *
     * @throws \App\Modules\Temple\TempleNotFoundException
     * @throws \InvalidArgumentException when already verified
     */
    public function verify(string $templeId): array;

    /**
     * Explicitly publish a verified temple into public discovery.
     *
     * @return array{id: string, slug: string, name: string, trust_name: string, type: string, city: string, state: string, status: string, verification_status: string, updated_at: string}
     *
     * @throws \App\Modules\Temple\TempleNotFoundException
     * @throws \InvalidArgumentException when unverified or already active
     */
    public function publish(string $templeId): array;

    /**
     * Suspend temple (admin only, step-up required).
     *
     * @return array{id: string, slug: string, name: string, trust_name: string, type: string, city: string, state: string, status: string, verification_status: string, updated_at: string}
     *
     * @throws \App\Modules\Temple\TempleNotFoundException
     * @throws \InvalidArgumentException when already suspended
     */
    public function suspend(string $templeId): array;

    /**
     * Reactivate temple (admin only, step-up required).
     *
     * @return array{id: string, slug: string, name: string, trust_name: string, type: string, city: string, state: string, status: string, verification_status: string, updated_at: string}
     *
     * @throws \App\Modules\Temple\TempleNotFoundException
     * @throws \InvalidArgumentException when not suspended
     */
    public function reactivate(string $templeId): array;

    /**
     * Get temple detail for partner self-serve (owner/manager only).
     *
     * @return array{
     *   id: string,
     *   slug: string,
     *   name: string,
     *   trust_name: string,
     *   type: string,
     *   address: string,
     *   city: string,
     *   state: string,
     *   lat: float|null,
     *   lng: float|null,
     *   images: list<string>,
     *   amenities: list<string>,
     *   description: string|null,
     *   status: string,
     *   verification_status: string,
     *   created_at: string,
     *   updated_at: string,
     *   managers: list<array{id: string, user_id: string, role: string}>
     * }|null
     */
    public function partnerProfile(string $templeId): ?array;
}
