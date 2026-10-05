<?php

namespace App\Modules\Temple;

use App\Modules\Temple\Contracts\ProvidesTempleDashboard;
use App\Modules\Temple\Models\Temple;
use App\Modules\Temple\Models\TemplePuja;
use App\Platform\Api\CursorPagination;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\Request;

/**
 * Temple dashboard service (deptrac-blessed ProvidesTempleDashboard implementation).
 * Ambient tenant context scopes every call.
 */
final class TempleDashboardService implements ProvidesTempleDashboard
{
    /**
     * @return array{
     *   total_pujas: int,
     *   active_pujas: int,
     *   total_bookings: int,
     *   upcoming_bookings: int,
     *   revenue_minor: int,
     *   avg_occupancy: float,
     *   recent_bookings: list<array{id: string, puja_name: string, user_name: string, start_at: string, status: string}>
     * }
     */
    public function overview(string $templeId): array
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();

        $temple = \App\Modules\Temple\Models\Temple::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $templeId)
            ->firstOrFail();

        // Get puja counts
        $totalPujas = TemplePuja::query()
            ->where('tenant_id', (string) app(TenantContext::class)->tenantId())
            ->where('temple_id', $templeId)
            ->count();

        $activePujas = TemplePuja::query()
            ->where('tenant_id', (string) app(TenantContext::class)->tenantId())
            ->where('temple_id', $templeId)
            ->where('status', 'ACTIVE')
            ->count();

        // Get booking counts (assuming Booking model exists in Booking module)
        // For now returning placeholder values
        $totalBookings = 0;
        $upcomingBookings = 0;
        $revenueMinor = 0;
        $avgOccupancy = 0.0;
        $recentBookings = [];

        // TODO: Integrate with Booking module when available
        // $bookings = Booking::query()...

        return [
            'total_pujas' => $totalPujas,
            'active_pujas' => $activePujas,
            'total_bookings' => $totalBookings,
            'upcoming_bookings' => $upcomingBookings,
            'revenue_minor' => $revenueMinor,
            'avg_occupancy' => $avgOccupancy,
            'recent_bookings' => $recentBookings,
        ];
    }

    public function pujas(string $templeId, array $filters = [], int $limit = 20): array
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();

        $query = \App\Modules\Temple\Models\TemplePuja::query()
            ->where('tenant_id', $tenantId)
            ->where('temple_id', $templeId);

        if (isset($filters['status']) && is_string($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['puja_id']) && is_string($filters['puja_id'])) {
            $query->where('puja_id', $filters['puja_id']);
        }

        $request = new Request(['limit' => $limit]);
        [$items, $page] = CursorPagination::paginate($query, $request, $limit);

        return [
            'items' => array_map(fn ($p) => $this->toDashboardPuja($p), $items),
            'page' => $page,
        ];
    }

    public function bookings(string $templeId, array $filters = [], int $limit = 20): array
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();

        // TODO: Integrate with Booking module
        // For now returning empty results
        $request = new Request(['limit' => $limit]);
        $query = \App\Modules\Temple\Models\TemplePuja::query()
            ->where('tenant_id', (string) app(TenantContext::class)->tenantId())
            ->where('temple_id', $templeId)
            ->whereRaw('1=0'); // Empty query

        [$items, $page] = CursorPagination::paginate($query, new \Illuminate\Http\Request(['limit' => $limit]), $limit);

        return [
            'items' => [],
            'page' => $page,
        ];
    }

    public function calendar(string $templeId, int $year, int $month): array
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();

        $startDate = \Carbon\CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $pujas = \App\Modules\Temple\Models\TemplePuja::query()
            ->where('tenant_id', (string) app(TenantContext::class)->tenantId())
            ->where('temple_id', $templeId)
            ->where('status', 'ACTIVE')
            ->get();

        $days = [];

        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $dayOfWeek = $date->dayOfWeek; // 0 = Sunday
            $daySlots = [];

            foreach ($pujas as $puja) {
                $schedule = $puja->schedule_json ? json_decode($puja->schedule_json, true) : [];

                if (isset($schedule[$dayOfWeek])) {
                    foreach ($schedule[$dayOfWeek] as $slot) {
                        $start = \Carbon\CarbonImmutable::parse($date->format('Y-m-d') . ' ' . $slot['start']);
                        $end = \Carbon\CarbonImmutable::parse($date->format('Y-m-d') . ' ' . $slot['end']);
                        $capacity = $slot['capacity'] ?? ($puja->capacity ?? 1);

                        // TODO: Get actual booked count from Booking module
                        $booked = 0;

                        $daySlots[] = [
                            'id' => (string) $puja->getKey() . '_' . $slot['start'] . '_' . $date->format('Ymd'),
                            'puja_name' => $puja->puja->name ?? 'Puja',
                            'start' => $start->toISOString(),
                            'end' => $end->toISOString(),
                            'capacity' => $capacity,
                            'booked' => $booked,
                        ];
                    }
                }
            }

            if (!empty($daySlots)) {
                $days[] = [
                    'date' => $date->format('Y-m-d'),
                    'slots' => $daySlots,
                ];
            }
        }

        return ['items' => $days];
    }

    public function analytics(string $templeId, int $months = 12): array
    {
        // TODO: Implement with actual booking data
        return [
            'revenue_by_month' => [],
            'occupancy_by_puja' => [],
            'peak_hours' => [],
        ];
    }

    public function settings(string $templeId): array
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();

        $temple = \App\Modules\Temple\Models\Temple::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $templeId)
            ->with('managers.user')
            ->firstOrFail();

        $managers = $temple->managers->map(fn ($m) => [
            'id' => (string) $m->getKey(),
            'user_id' => (string) $m->user_id,
            'role' => $m->role,
            'invited_at' => $m->invited_at?->toISOString(),
            'accepted_at' => $m->accepted_at?->toISOString(),
            'user' => $m->user ? [
                'id' => (string) $m->user->getKey(),
                'name' => $m->user->name,
                'email' => $m->user->email,
            ] : null,
        ])->all();

        return [
            'temple' => [
                'id' => (string) $temple->getKey(),
                'slug' => $temple->slug,
                'name' => $temple->name,
                'trust_name' => $temple->trust_name,
                'type' => $temple->type,
                'address' => $temple->address,
                'city' => $temple->city,
                'state' => $temple->state,
                'lat' => $temple->lat,
                'lng' => $temple->lng,
                'images' => $temple->images ?? [],
                'amenities' => $temple->amenities ?? [],
                'description' => $temple->description,
                'status' => $temple->status,
                'verification_status' => $temple->verification_status,
            ],
            'managers' => $managers,
            'amenities' => \App\Modules\Temple\Models\Temple::getAvailableAmenities(),
        ];
    }

    /**
     * @return array{
     *   id: string,
     *   puja_id: string,
     *   puja_name: string,
     *   price_override_minor: int|null,
     *   capacity: int|null,
     *   schedule_json: string|null,
     *   status: string,
     *   bookings_count: int
     * }
     */
    private function toDashboardPuja(\App\Modules\Temple\Models\TemplePuja $p): array
    {
        return [
            'id' => (string) $p->getKey(),
            'puja_id' => (string) $p->puja_id,
            'puja_name' => $p->puja->name ?? 'Puja',
            'price_override_minor' => $p->price_override_minor,
            'capacity' => $p->capacity,
            'schedule_json' => $p->schedule_json,
            'status' => $p->status,
            'bookings_count' => 0, // TODO: from booking module
        ];
    }
}
