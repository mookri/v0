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

    public function listPujas(string $templeId, array $filters = [], int $limit = 20, ?string $cursor = null): array
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();

        $query = \App\Modules\Temple\Models\TemplePuja::query()
            ->where('tenant_id', (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId())
            ->where('temple_id', $templeId);

        if (isset($filters['status']) && is_string($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $request = new \Illuminate\Http\Request(['limit' => $limit, 'cursor' => $cursor]);
        $paginator = \App\Platform\Api\CursorPagination::paginate($query, $request, $limit);

        return [
            'items' => array_map(fn ($p) => $this->toAssignment($p), $paginator[0]),
            'page' => $paginator[1],
        ];
    }

    public function showPuja(string $templeId, string $pujaId): array
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

        return $this->toAssignment($puja);
    }

    public function publicPujas(string $templeId, int $limit = 20, ?string $cursor = null): ?array
    {
        if (! $this->publiclyVisible($templeId)) {
            return null;
        }

        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();

        $query = TemplePuja::query()
            ->where('tenant_id', $tenantId)
            ->where('temple_id', $templeId)
            ->where('status', TemplePuja::STATUS_ACTIVE);

        $request = new \Illuminate\Http\Request(['limit' => $limit, 'cursor' => $cursor]);
        [$items, $page] = \App\Platform\Api\CursorPagination::paginate($query, $request, $limit);

        return [
            'items' => array_map(fn (TemplePuja $p) => $this->toAssignment($p), $items),
            'page' => $page,
        ];
    }

    public function publicAvailability(string $templeId, ?string $startDate = null, ?string $endDate = null): ?array
    {
        if (! $this->publiclyVisible($templeId)) {
            return null;
        }

        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();
        $allowedDays = $this->weekdaysInRange($startDate, $endDate);

        $assignments = TemplePuja::query()
            ->where('tenant_id', $tenantId)
            ->where('temple_id', $templeId)
            ->where('status', TemplePuja::STATUS_ACTIVE)
            ->orderBy('id')
            ->get();

        $items = [];

        foreach ($assignments as $assignment) {
            $raw = $assignment->schedule_json;
            $schedule = is_string($raw) ? json_decode($raw, true) : null;

            if (! is_array($schedule)) {
                // Malformed or absent schedule: skip the row, never 500.
                continue;
            }

            foreach ($schedule as $day => $windows) {
                if (! is_numeric($day) || ! is_array($windows)) {
                    continue;
                }

                $dayOfWeek = (int) $day;

                if ($dayOfWeek < 0 || $dayOfWeek > 6) {
                    continue;
                }

                if ($allowedDays !== null && ! in_array($dayOfWeek, $allowedDays, true)) {
                    continue;
                }

                foreach ($windows as $window) {
                    if (! is_array($window) || ! isset($window['start'], $window['end'])) {
                        continue;
                    }

                    $items[] = [
                        'assignment_id' => (string) $assignment->getKey(),
                        'puja_id' => (string) $assignment->puja_id,
                        'day_of_week' => $dayOfWeek,
                        'start' => (string) $window['start'],
                        'end' => (string) $window['end'],
                        'capacity' => isset($window['capacity']) ? (int) $window['capacity'] : $assignment->capacity,
                        'price_override_minor' => $assignment->price_override_minor,
                    ];
                }
            }
        }

        return ['items' => $items];
    }

    /**
     * VERIFIED + ACTIVE posture for every public temple read — the same
     * filter publicProfiles applies, so an unverified or suspended temple
     * never leaks its pujas or schedule.
     */
    private function publiclyVisible(string $templeId): bool
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();

        return Temple::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $templeId)
            ->where('verification_status', Temple::VERIFICATION_VERIFIED)
            ->where('status', Temple::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * Weekdays (date('w'): 0 = Sunday) covered by the optional inclusive
     * range; null when neither bound is given (every day). Weekly windows
     * recur forever, so ranges of 7+ days cover all seven weekdays and the
     * loop runs at most seven times.
     *
     * @return list<int>|null
     */
    private function weekdaysInRange(?string $startDate, ?string $endDate): ?array
    {
        if ($startDate === null && $endDate === null) {
            return null;
        }

        $start = \Carbon\CarbonImmutable::parse($startDate ?? '1970-01-01')->startOfDay();
        $end = \Carbon\CarbonImmutable::parse($endDate ?? '2999-12-31')->endOfDay();

        if ($start->diffInDays($end) >= 7) {
            return [0, 1, 2, 3, 4, 5, 6];
        }

        $days = [];
        for ($date = $start; $date->lte($end); $date = $date->addDay()) {
            $days[(int) $date->dayOfWeek] = true;
        }

        return array_keys($days);
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
