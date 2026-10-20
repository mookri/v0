<?php

namespace App\Modules\Booking\Http\Controllers;

use App\Modules\Pandit\Contracts\ListsPanditProfiles;
use App\Modules\Pandit\Contracts\ManagesPanditAssignments;
use App\Modules\Pandit\Contracts\ManagesPanditAvailability;
use App\Modules\Temple\Contracts\ListsTemples;
use App\Modules\Temple\Contracts\ManagesTemplePujas;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Instant-book search (B2): one endpoint — date + slot (+ optional puja)
 * → assignable pandits and open temple slots. Cross-module reads go
 * through Contracts ports only (deptrac): directory profiles, pandit
 * availability/conflict, temple schedule windows. No writes, no prices
 * quoted (quote stays a separate step, B3).
 *
 * Assignable = availability windows cover the whole requested slot AND
 * no PENDING/CONFIRMED assignment overlaps it (fail-closed, same guard
 * as assign()). Temple slots = weekday schedule windows of VERIFIED +
 * ACTIVE temples that cover the slot (capacity is declared, never
 * decremented anywhere yet — surfaced as-is).
 */
final class InstantSearchController
{
    /**
     * Directory scan bound: one page of profiles per side, then filter.
     * Tenant directories are small; results are capped by `limit`.
     */
    private const SCAN_LIMIT = 100;

    public function __construct(
        private readonly ListsPanditProfiles $pandits,
        private readonly ManagesPanditAvailability $availability,
        private readonly ManagesPanditAssignments $assignments,
        private readonly ListsTemples $temples,
        private readonly ManagesTemplePujas $templePujas,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start' => ['required', 'date_format:H:i'],
            'end' => ['required', 'date_format:H:i', 'after:start'],
            'puja_id' => ['sometimes', 'string', 'max:26'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $date = (string) $validated['date'];
        $start = (string) $validated['start'];
        $end = (string) $validated['end'];
        $pujaId = $validated['puja_id'] ?? null;
        $limit = (int) ($validated['limit'] ?? 20);

        $slotStart = CarbonImmutable::parse($date.' '.$start);
        $slotEnd = CarbonImmutable::parse($date.' '.$end);
        $weekday = (int) CarbonImmutable::parse($date)->format('w');

        return new JsonResponse([
            'data' => [
                'object' => 'instant_search',
                'date' => $date,
                'start' => $start,
                'end' => $end,
                'puja_id' => $pujaId,
                'pandits' => $this->assignablePandits($date, $slotStart, $slotEnd, $limit),
                'temple_slots' => $this->templeSlots($date, $weekday, $start, $end, $pujaId, $limit),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * @return list<array{id: string, display_name: string, rating_avg: float, rating_count: int, specializations: list<string>, base_rate_minor: int|null, currency: string}>
     */
    private function assignablePandits(string $date, CarbonImmutable $slotStart, CarbonImmutable $slotEnd, int $limit): array
    {
        $profiles = $this->pandits->publicProfiles([], self::SCAN_LIMIT);

        $assignable = [];

        foreach ($profiles['items'] as $profile) {
            if (count($assignable) >= $limit) {
                break;
            }

            $windows = $this->availability->windowsOnDate($profile['id'], $date);

            if (! $this->coversSlot($windows, $slotStart, $slotEnd)) {
                continue;
            }

            if ($this->assignments->hasWindowConflict(
                $profile['id'],
                $slotStart->toIso8601String(),
                $slotEnd->toIso8601String(),
            )) {
                continue;
            }

            $assignable[] = $profile;
        }

        return $assignable;
    }

    /**
     * @return list<array{temple_id: string, temple_name: string, city: string, puja_id: string, day_of_week: int, start: string, end: string, capacity: int|null, price_override_minor: int|null}>
     */
    private function templeSlots(
        string $date,
        int $weekday,
        string $start,
        string $end,
        ?string $pujaId,
        int $limit,
    ): array {
        $profiles = $this->temples->publicProfiles([], self::SCAN_LIMIT);

        $slots = [];

        foreach ($profiles['items'] as $temple) {
            if (count($slots) >= $limit) {
                break;
            }

            // Date range = the single date: expands to that weekday only.
            $availability = $this->templePujas->publicAvailability($temple['id'], $date, $date);

            if ($availability === null) {
                continue; // not publicly visible (VERIFIED + ACTIVE gate)
            }

            foreach ($availability['items'] as $window) {
                if (count($slots) >= $limit) {
                    break;
                }

                if ((int) $window['day_of_week'] !== $weekday) {
                    continue;
                }

                if ($pujaId !== null && $window['puja_id'] !== $pujaId) {
                    continue;
                }

                if (! $this->coversTime((string) $window['start'], (string) $window['end'], $start, $end)) {
                    continue;
                }

                $slots[] = [
                    'temple_id' => $temple['id'],
                    'temple_name' => $temple['name'],
                    'city' => $temple['city'],
                    'puja_id' => (string) $window['puja_id'],
                    'day_of_week' => (int) $window['day_of_week'],
                    'start' => (string) $window['start'],
                    'end' => (string) $window['end'],
                    'capacity' => $window['capacity'],
                    'price_override_minor' => $window['price_override_minor'],
                ];
            }
        }

        return $slots;
    }

    /**
     * The whole requested slot must fit inside one availability window
     * (containment, not mere overlap — a booking has to fit). Times are
     * compared as minutes-since-midnight on both sides, never as
     * datetimes (window rows are clock-only, the slot carries a date).
     *
     * @param  list<array{start_time: string, end_time: string}>  $windows
     */
    private function coversSlot(array $windows, CarbonImmutable $slotStart, CarbonImmutable $slotEnd): bool
    {
        $start = $this->minutesOfDay($slotStart->format('H:i:s'));
        $end = $this->minutesOfDay($slotEnd->format('H:i:s'));

        foreach ($windows as $window) {
            if ($this->minutesOfDay($window['start_time']) <= $start
                && $end <= $this->minutesOfDay($window['end_time'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Same containment rule for clock-only strings (H:i[:s] windows from
     * the temple schedule vs the requested slot).
     */
    private function coversTime(string $windowStart, string $windowEnd, string $start, string $end): bool
    {
        return $this->minutesOfDay($windowStart) <= $this->minutesOfDay($start)
            && $this->minutesOfDay($end) <= $this->minutesOfDay($windowEnd);
    }

    /**
     * Minutes since midnight for H:i or H:i:s (leading-zero safe, never
     * lexicographic).
     */
    private function minutesOfDay(string $clock): int
    {
        $parts = explode(':', $clock);

        return ((int) ($parts[0] ?? 0)) * 60 + (int) ($parts[1] ?? 0);
    }
}
