<?php

namespace App\Modules\Temple;

use App\Modules\Booking\Contracts\ReadsBookings;
use App\Modules\Catalog\Contracts\ListsCatalogItems;
use App\Modules\Temple\Contracts\ProvidesTempleDashboard;
use App\Modules\Temple\Models\Temple;
use App\Modules\Temple\Models\TemplePuja;
use App\Platform\Api\CursorPagination;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Temple dashboard service (deptrac-blessed ProvidesTempleDashboard implementation).
 * Ambient tenant context scopes every call.
 *
 * Booking integration (S14) crosses module lines only through ports: each
 * assignment's puja_id resolves to a catalog item (sku, name, list price)
 * through the catalog read port, and those skus key every booking read
 * through the booking read port. Money is this temple's own unit price
 * (assignment price_override_minor, else catalog list price) multiplied
 * over committed bookings — bookings store no amount until the payments
 * slice, so revenue is a booked-value estimate, never a settlement
 * figure. Unresolvable puja references fail closed to zeros/empty rows.
 *
 * @phpstan-type PujaContext array{assignments: list<TemplePuja>, skus: list<string>, skuByPujaId: array<string, string>, nameByPujaId: array<string, string>, nameBySku: array<string, string>, priceBySku: array<string, int>}
 * @phpstan-type SlotInstance array{id: string, puja_id: string, puja_name: string, start: string, end: string, capacity: int, booked: int}
 */
final class TempleDashboardService implements ProvidesTempleDashboard
{
    public function __construct(
        private readonly ReadsBookings $bookings,
        private readonly ListsCatalogItems $catalog,
    ) {
    }

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

        // Scoped lookup: unknown temple fails closed with the kernel 404.
        Temple::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $templeId)
            ->firstOrFail();

        $totalPujas = TemplePuja::query()
            ->where('tenant_id', $tenantId)
            ->where('temple_id', $templeId)
            ->count();

        $activePujas = TemplePuja::query()
            ->where('tenant_id', $tenantId)
            ->where('temple_id', $templeId)
            ->where('status', 'ACTIVE')
            ->count();

        $context = $this->pujaContext($templeId);
        $totals = $this->bookings->dashboardTotalsBySkus($context['skus']);

        // Lifetime booked value: every committed start × this temple's unit
        // price (pre-payment estimate — bookings store no amount).
        $revenueMinor = 0;
        foreach ($this->bookings->scheduledStartsBySkus($context['skus']) as $start) {
            if ($start['start_at'] === '') {
                continue;
            }
            $revenueMinor += $context['priceBySku'][$start['service_sku']] ?? 0;
        }

        // Recent activity: newest rows regardless of status.
        $recentBookings = [];
        foreach ($this->bookings->dashboardRowsBySkus($context['skus'], [], 5, null)['items'] as $row) {
            $recentBookings[] = [
                'id' => $row['id'],
                'puja_name' => $context['nameBySku'][$row['service_sku']] ?? 'Puja',
                'user_name' => $row['user_name'],
                'start_at' => $row['start_at'] ?? '',
                'status' => $row['status'],
            ];
        }

        // Avg occupancy: committed bookings ÷ slot capacity for the current
        // calendar month (0.0 when nothing is scheduled this month).
        $monthStart = CarbonImmutable::now()->startOfMonth();
        $bookedSlots = 0;
        $capacitySlots = 0;
        foreach ($this->slotDays($context, $monthStart, $monthStart->copy()->addMonth()) as $day) {
            foreach ($day['slots'] as $slot) {
                $bookedSlots += $slot['booked'];
                $capacitySlots += $slot['capacity'];
            }
        }

        return [
            'total_pujas' => $totalPujas,
            'active_pujas' => $activePujas,
            'total_bookings' => $totals['total'],
            'upcoming_bookings' => $totals['upcoming'],
            'revenue_minor' => $revenueMinor,
            'avg_occupancy' => $capacitySlots > 0 ? round($bookedSlots / $capacitySlots, 4) : 0.0,
            'recent_bookings' => $recentBookings,
        ];
    }

    public function pujas(string $templeId, array $filters = [], int $limit = 20, ?string $cursor = null): array
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();

        $query = TemplePuja::query()
            ->where('tenant_id', $tenantId)
            ->where('temple_id', $templeId);

        if (isset($filters['status']) && is_string($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['puja_id']) && is_string($filters['puja_id'])) {
            $query->where('puja_id', $filters['puja_id']);
        }

        $request = new Request(['limit' => $limit, 'cursor' => $cursor]);
        [$items, $page] = CursorPagination::paginate($query, $request, $limit);

        // Booking counts for the pujas on this page only (resolved skus).
        $context = $this->pujaContext($templeId);
        $pageSkus = [];
        foreach ($items as $item) {
            $sku = $context['skuByPujaId'][(string) $item->puja_id] ?? null;
            if ($sku !== null) {
                $pageSkus[$sku] = $sku;
            }
        }
        $counts = $this->bookings->dashboardTotalsBySkus(array_values($pageSkus))['counts_by_sku'];

        $rows = [];
        foreach ($items as $item) {
            $rows[] = $this->toDashboardPuja($item, $context, $counts);
        }

        return [
            'items' => $rows,
            'page' => $page,
        ];
    }

    public function bookings(string $templeId, array $filters = [], int $limit = 20, ?string $cursor = null): array
    {
        $context = $this->pujaContext($templeId);

        $result = $this->bookings->dashboardRowsBySkus($context['skus'], $filters, $limit, $cursor);

        $items = [];
        foreach ($result['items'] as $row) {
            $items[] = [
                'id' => $row['id'],
                'puja_name' => $context['nameBySku'][$row['service_sku']] ?? 'Puja',
                'user_name' => $row['user_name'],
                'user_email' => $row['user_email'],
                'start_at' => $row['start_at'] ?? '',
                'end_at' => $row['end_at'] ?? '',
                'status' => $row['status'],
                'amount_minor' => $context['priceBySku'][$row['service_sku']] ?? 0,
            ];
        }

        return [
            'items' => $items,
            'page' => $result['page'],
        ];
    }

    public function calendar(string $templeId, int $year, int $month): array
    {
        $context = $this->pujaContext($templeId);

        $rangeStart = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $rangeEnd = $rangeStart->copy()->addMonth();

        $days = [];
        foreach ($this->slotDays($context, $rangeStart, $rangeEnd) as $day) {
            $slots = [];
            foreach ($day['slots'] as $slot) {
                $slots[] = [
                    'id' => $slot['id'],
                    'puja_name' => $slot['puja_name'],
                    'start' => $slot['start'],
                    'end' => $slot['end'],
                    'capacity' => $slot['capacity'],
                    'booked' => $slot['booked'],
                ];
            }

            $days[] = [
                'date' => $day['date'],
                'slots' => $slots,
            ];
        }

        return ['items' => $days];
    }

    public function analytics(string $templeId, int $months = 12): array
    {
        $context = $this->pujaContext($templeId);
        $months = max(1, min(24, $months));

        $rangeEnd = CarbonImmutable::now()->startOfMonth()->addMonth();
        $rangeStart = $rangeEnd->copy()->subMonthsNoOverflow($months);

        $starts = $this->bookings->scheduledStartsBySkus(
            $context['skus'],
            $rangeStart->toIso8601String(),
            $rangeEnd->toIso8601String(),
        );

        // revenue_by_month: zero-filled oldest → newest, bucket key 'Y-m'.
        $revenueByMonth = [];
        $bookingsByMonth = [];
        $monthKeys = [];
        for ($i = 0; $i < $months; $i++) {
            $key = $rangeStart->copy()->addMonthsNoOverflow($i)->format('Y-m');
            $monthKeys[] = $key;
            $revenueByMonth[$key] = 0;
            $bookingsByMonth[$key] = 0;
        }

        $hourCounts = [];
        $bookingsBySku = [];
        foreach ($starts as $start) {
            $bookingsBySku[$start['service_sku']] = ($bookingsBySku[$start['service_sku']] ?? 0) + 1;

            if ($start['start_at'] === '') {
                continue;
            }

            $begin = CarbonImmutable::parse($start['start_at']);
            $key = $begin->format('Y-m');
            if (isset($revenueByMonth[$key])) {
                $revenueByMonth[$key] += $context['priceBySku'][$start['service_sku']] ?? 0;
                $bookingsByMonth[$key]++;
            }

            $hour = (int) $begin->format('G');
            $hourCounts[$hour] = ($hourCounts[$hour] ?? 0) + 1;
        }

        $revenueByMonthList = [];
        foreach ($monthKeys as $key) {
            $revenueByMonthList[] = [
                'month' => $key,
                'revenue_minor' => $revenueByMonth[$key],
                'bookings' => $bookingsByMonth[$key],
            ];
        }

        // occupancy_by_puja: Σbooked ÷ Σcapacity over the range's slots
        // (values above 1.0 mean a slot was overbooked); slotless pujas
        // report 0.0. Unresolvable puja references are omitted (fail closed).
        $bookedByPujaId = [];
        $capacityByPujaId = [];
        foreach ($this->slotDays($context, $rangeStart, $rangeEnd) as $day) {
            foreach ($day['slots'] as $slot) {
                $pujaId = $slot['puja_id'];
                $bookedByPujaId[$pujaId] = ($bookedByPujaId[$pujaId] ?? 0) + $slot['booked'];
                $capacityByPujaId[$pujaId] = ($capacityByPujaId[$pujaId] ?? 0) + $slot['capacity'];
            }
        }

        $occupancyByPuja = [];
        foreach ($context['assignments'] as $assignment) {
            $pujaId = (string) $assignment->puja_id;
            $sku = $context['skuByPujaId'][$pujaId] ?? null;
            if ($sku === null) {
                continue;
            }

            $capacity = $capacityByPujaId[$pujaId] ?? 0;
            $booked = $bookedByPujaId[$pujaId] ?? 0;

            $occupancyByPuja[] = [
                'puja_name' => $context['nameByPujaId'][$pujaId] ?? 'Puja',
                'occupancy' => $capacity > 0 ? round($booked / $capacity, 4) : 0.0,
                'bookings' => $bookingsBySku[$sku] ?? 0,
            ];
        }

        // peak_hours: hours with at least one booking, busiest first.
        $peakHours = [];
        foreach ($hourCounts as $hour => $count) {
            $peakHours[] = ['hour' => $hour, 'bookings' => $count];
        }
        usort($peakHours, static function (array $a, array $b): int {
            if ($a['bookings'] !== $b['bookings']) {
                return $b['bookings'] <=> $a['bookings'];
            }

            return $a['hour'] <=> $b['hour'];
        });

        return [
            'revenue_by_month' => $revenueByMonthList,
            'occupancy_by_puja' => $occupancyByPuja,
            'peak_hours' => $peakHours,
        ];
    }

    public function settings(string $templeId): array
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();

        $temple = Temple::query()
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
            'amenities' => Temple::getAvailableAmenities(),
        ];
    }

    /**
     * The temple's assignments plus their catalog resolution: puja_id →
     * {sku, name, list price}. Unknown puja references stay unresolved
     * (fail closed — no sku, no money, no bucketed bookings, never an
     * error), and a sku maps to at most one assignment per temple
     * (tenant+temple+puja_id is unique and skus are unique per tenant).
     *
     * @return PujaContext
     */
    private function pujaContext(string $templeId): array
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();

        $assignments = TemplePuja::query()
            ->where('tenant_id', $tenantId)
            ->where('temple_id', $templeId)
            ->orderBy('id')
            ->get()
            ->all();

        $items = $this->catalog->itemsByIds(array_map(
            static fn (TemplePuja $assignment): string => (string) $assignment->puja_id,
            $assignments,
        ));

        $catalogById = [];
        foreach ($items as $item) {
            $catalogById[$item['id']] = $item;
        }

        $skus = [];
        $skuByPujaId = [];
        $nameByPujaId = [];
        $nameBySku = [];
        $priceBySku = [];

        foreach ($assignments as $assignment) {
            $pujaId = (string) $assignment->puja_id;
            $item = $catalogById[$pujaId] ?? null;
            if ($item === null) {
                continue;
            }

            $sku = $item['sku'];
            $skus[] = $sku;
            $skuByPujaId[$pujaId] = $sku;
            $nameByPujaId[$pujaId] = $item['name'];
            $nameBySku[$sku] = $item['name'];
            $priceBySku[$sku] = $assignment->price_override_minor ?? $item['price_minor'];
        }

        return [
            'assignments' => $assignments,
            'skus' => array_values(array_unique($skus)),
            'skuByPujaId' => $skuByPujaId,
            'nameByPujaId' => $nameByPujaId,
            'nameBySku' => $nameBySku,
            'priceBySku' => $priceBySku,
        ];
    }

    /**
     * Slot instances across [rangeStart, rangeEnd) with committed bookings
     * bucketed in — a booking [start, start + duration) consumes a slot
     * [start, end) exactly when they intersect (half-open arithmetic lives
     * in this consumer, per the read-port rule). ACTIVE assignments only;
     * unresolved puja ids bucket no bookings (fail closed).
     *
     * @param  PujaContext  $context
     * @param  CarbonImmutable  $rangeStart  inclusive, normalized to the day
     * @param  CarbonImmutable  $rangeEnd  exclusive
     * @return list<array{date: string, slots: list<SlotInstance>}>
     */
    private function slotDays(array $context, CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd): array
    {
        $startsBySku = [];
        foreach ($this->bookings->scheduledStartsBySkus(
            $context['skus'],
            $rangeStart->toIso8601String(),
            $rangeEnd->toIso8601String(),
        ) as $start) {
            if ($start['start_at'] === '') {
                continue;
            }

            $begin = CarbonImmutable::parse($start['start_at']);
            $startsBySku[$start['service_sku']][] = [
                'begin' => $begin,
                'end' => $begin->addMinutes($start['duration_minutes']),
            ];
        }

        $days = [];

        for ($date = $rangeStart->startOfDay(); $date->lt($rangeEnd); $date = $date->addDay()) {
            $dayOfWeek = $date->dayOfWeek; // 0 = Sunday
            $daySlots = [];

            foreach ($context['assignments'] as $assignment) {
                if ($assignment->status !== 'ACTIVE') {
                    continue;
                }

                $schedule = $assignment->schedule_json;
                if (is_string($schedule)) {
                    $schedule = json_decode($schedule, true);
                }
                if (! is_array($schedule) || ! isset($schedule[$dayOfWeek])) {
                    continue;
                }

                $pujaId = (string) $assignment->puja_id;
                $sku = $context['skuByPujaId'][$pujaId] ?? null;
                $pujaName = $context['nameByPujaId'][$pujaId] ?? 'Puja';
                $starts = $sku !== null ? ($startsBySku[$sku] ?? []) : [];

                foreach ($schedule[$dayOfWeek] as $slot) {
                    if (! is_array($slot) || ! isset($slot['start'], $slot['end']) || ! is_string($slot['start']) || ! is_string($slot['end'])) {
                        continue;
                    }

                    $startAt = CarbonImmutable::parse($date->format('Y-m-d') . ' ' . $slot['start']);
                    $endAt = CarbonImmutable::parse($date->format('Y-m-d') . ' ' . $slot['end']);
                    $capacity = (int) ($slot['capacity'] ?? ($assignment->capacity ?? 1));

                    $booked = 0;
                    foreach ($starts as $booking) {
                        if ($booking['begin']->lt($endAt) && $startAt->lt($booking['end'])) {
                            $booked++;
                        }
                    }

                    $daySlots[] = [
                        'id' => (string) $assignment->getKey() . '_' . $slot['start'] . '_' . $date->format('Ymd'),
                        'puja_id' => $pujaId,
                        'puja_name' => $pujaName,
                        'start' => $startAt->toISOString(),
                        'end' => $endAt->toISOString(),
                        'capacity' => $capacity,
                        'booked' => $booked,
                    ];
                }
            }

            if ($daySlots !== []) {
                $days[] = [
                    'date' => $date->format('Y-m-d'),
                    'slots' => $daySlots,
                ];
            }
        }

        return $days;
    }

    /**
     * @param  PujaContext  $context
     * @param  array<string, int>  $countsBySku
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
    private function toDashboardPuja(TemplePuja $p, array $context, array $countsBySku): array
    {
        $pujaId = (string) $p->puja_id;
        $sku = $context['skuByPujaId'][$pujaId] ?? null;

        return [
            'id' => (string) $p->getKey(),
            'puja_id' => $pujaId,
            'puja_name' => $context['nameByPujaId'][$pujaId] ?? 'Puja',
            'price_override_minor' => $p->price_override_minor,
            'capacity' => $p->capacity,
            'schedule_json' => $p->schedule_json,
            'status' => $p->status,
            'bookings_count' => $sku !== null ? ($countsBySku[$sku] ?? 0) : 0,
        ];
    }
}
