<?php

namespace App\Modules\Temple\Contracts;

/**
 * Temple manager management port (deptrac-blessed Contracts surface).
 * Ambient tenant context scopes every call.
 *
 * @phpstan-type TempleManager array{id: string, temple_id: string, user_id: string, role: string, invited_at: string, accepted_at: string|null}
 */
interface ManagesTempleManagers
{
    /**
     * Add a manager to a temple (owner only).
     *
     * @param  array{
     *   user_id: string,
     *   role: string, // 'admin' | 'manager' | 'viewer'
     *   invited_by: string
     * }  $data
     *
     * @return array{id: string, temple_id: string, user_id: string, role: string, invited_at: string, accepted_at: string|null}
     *
     * @throws \App\Modules\Temple\TempleNotFoundException
     * @throws \InvalidArgumentException when user already a manager
     */
    public function addManager(string $templeId, array $data): array;

    /**
     * Remove a manager from a temple (owner only).
     *
     * @throws \App\Modules\Temple\TempleNotFoundException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException
     */
    public function removeManager(string $templeId, string $managerId): void;

    /**
     * Update manager role (owner only).
     *
     * @return array{id: string, temple_id: string, user_id: string, role: string, invited_at: string, accepted_at: string|null}
     *
     * @throws \App\Modules\Temple\TempleNotFoundException
     * @throws \App\Modules\Temple\TempleManagerNotFoundException
     * @throws \InvalidArgumentException
     */
    public function updateManagerRole(string $templeId, string $managerId, string $role): array;

    /**
     * List managers for a temple.
     *
     * @return array{items: list<array{id: string, temple_id: string, user_id: string, role: string, invited_at: string, accepted_at: string|null}>}
     *
     * @throws \App\Modules\Temple\TempleNotFoundException
     */
    public function listManagers(string $templeId): array;
}
