<?php

namespace App\Modules\Booking;

use App\Modules\Booking\Contracts\RanksServices;
use App\Modules\Booking\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * RanksServices port implementation: booking-count popularity over the
 * trailing window plus per-customer SKU history, ambient-tenant scoped.
 */
final class ServiceRankings implements RanksServices
{
    public function popularSkus(int $days, int $limit): array
    {
        $since = CarbonImmutable::parse(gmdate('Y-m-d\TH:i:s.v\Z'))->subDays(max(1, $days))->toDateTimeString();

        return Booking::query()
            ->select('service_sku', DB::raw('COUNT(*) as bookings_count'))
            ->whereNotNull('service_sku')
            ->where('created_at', '>=', $since)
            ->groupBy('service_sku')
            ->orderByDesc('bookings_count')
            ->orderBy('service_sku')
            ->take(max(1, min(50, $limit)))
            ->pluck('service_sku')
            ->all();
    }

    public function customerSkus(string $userId, int $limit): array
    {
        return Booking::query()
            ->where('user_id', $userId)
            ->whereNotNull('service_sku')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take(max(1, min(100, $limit)))
            ->pluck('service_sku')
            ->unique()
            ->values()
            ->all();
    }
}
