<?php

namespace App\Modules\Temple\Contracts;

/**
 * Temple puja management port (deptrac-blessed Contracts surface).
 * Ambient tenant context scopes every call.
 *
 * @phpstan-type TemplePujaAssignment array{id: string, temple_id: string, puja_id: string, price_override_minor: int|null, capacity: int|null, schedule_json: string|null, status: string, created_at: string}
 * @phpstan-type TemplePage array{limit: int, has_more: bool, next_cursor: string|null}
 */
interface ManagesTemplePujas
{
    /**
     * Assign a puja to a temple with price override and schedule.
     *
     * @param  array{
     *   puja_id: string,
     *   price_override_minor: int|null,
     *   capacity: int|null,
     *   schedule_json: string|null // JSON: day_of_week -> [{start, end, capacity}]
     * }  $data
     *
     * @return array{id: string, temple_id: string, puja_id: string, price_override_minor: int|null, capacity: int|null, schedule_json: string|null, status: string, created_at: string}
     *
     * @throws \App\Modules\Temple\TempleNotFoundException
     * @throws \InvalidArgumentException when puja not found or conflict
     */
    public function assignPuja(string $templeId, array $data): array;

    /**
     * Update puja assignment (price, capacity, schedule).
     *
     * @param  array<string, mixed>  $data
     *
     * @return array{id: string, temple_id: string, puja_id: string, price_override_minor: int|null, capacity: int|null, schedule_json: string|null, status: string, created_at: string}
     *
     * @throws \App\Modules\Temple\TemplePujaNotFoundException
     */
    public function updatePuja(string $templeId, string $pujaId, array $data): array;

    /**
     * Remove puja from temple.
     *
     * @throws \App\Modules\Temple\TemplePujaNotFoundException
     */
    public function removePuja(string $templeId, string $pujaId): void;

    /**
     * List pujas for a temple (partner self-serve).
     *
     * @param  array<string, mixed>  $filters
     * @return array{items: list<array{id: string, temple_id: string, puja_id: string, price_override_minor: int|null, capacity: int|null, schedule_json: string|null, status: string}>, page: array{limit: int, has_more: bool, next_cursor: string|null}}
     */
    public function listPujas(string $templeId, array $filters = [], int $limit = 20): array;

    /**
     * Fetch one puja assignment (partner self-serve), scoped to the
     * temple — the same tenant + temple posture as update/remove.
     *
     * @return array{id: string, temple_id: string, puja_id: string, price_override_minor: int|null, capacity: int|null, schedule_json: string|null, status: string, created_at: string}
     *
     * @throws \App\Modules\Temple\TemplePujaNotFoundException
     */
    public function showPuja(string $templeId, string $pujaId): array;

    /**
     * Public pujas of a VERIFIED + ACTIVE temple (ACTIVE assignments
     * only). Returns null when the temple is absent or not publicly
     * visible so the caller can collapse both into one 404 — no
     * existence oracle for temples awaiting vetting (P11g).
     *
     * @return array{items: list<array{id: string, temple_id: string, puja_id: string, price_override_minor: int|null, capacity: int|null, schedule_json: string|null, status: string, created_at: string}>, page: array{limit: int, has_more: bool, next_cursor: string|null}}|null
     */
    public function publicPujas(string $templeId, int $limit = 20): ?array;

    /**
     * Public weekly availability of a VERIFIED + ACTIVE temple, expanded
     * from ACTIVE puja schedule windows (schedule_json: day_of_week ->
     * [{start, end, capacity}]). day_of_week follows date('w'):
     * 0 = Sunday .. 6 = Saturday. The optional inclusive date range
     * restricts rows to weekdays in range; malformed schedule entries are
     * skipped, never fatal. Null when the temple is not publicly visible.
     *
     * @return array{items: list<array{assignment_id: string, puja_id: string, day_of_week: int, start: string, end: string, capacity: int|null, price_override_minor: int|null}>}|null
     */
    public function publicAvailability(string $templeId, ?string $startDate = null, ?string $endDate = null): ?array;
}
