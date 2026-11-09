<?php

namespace App\Modules\Pandit\Console;

use App\Modules\Booking\Contracts\ReadsBookings;
use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditProfile;
use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Console\Command;

/**
 * Recomputes pandit profile ratings and total booking counts from booking reviews.
 */
final class RecomputePanditRatingsCommand extends Command
{
    protected $signature = 'pandit:recompute-ratings {--tenant= : Scope to specific tenant} {--pandit= : Scope to specific pandit}';

    protected $description = 'Recompute pandit rating_avg, rating_count, and total_bookings from booking reviews';

    public function handle(
        TenantContext $context,
        ReadsBookings $bookingsPort
    ): int {
        $tenantIdOption = $this->option('tenant');
        $panditIdOption = $this->option('pandit');

        $tenants = Tenant::query()
            ->where('state', Tenant::STATE_ACTIVE)
            ->when($tenantIdOption !== null, fn ($q) => $q->where('id', $tenantIdOption))
            ->get();

        $totalRecomputed = 0;

        foreach ($tenants as $tenant) {
            $tenantId = (string) $tenant->getKey();
            $context->clear();
            $context->set($tenantId, null, null, (new ConnectionResolver())->cachedFor($tenant), ['system' => 'rating-recompute']);

            $query = PanditProfile::query()->where('tenant_id', $tenantId);
            if ($panditIdOption !== null) {
                $query->where('id', $panditIdOption);
            }

            $profiles = $query->get();

            foreach ($profiles as $profile) {
                $panditId = (string) $profile->getKey();

                // 1. Total completed bookings
                $totalBookings = PanditAssignment::query()
                    ->where('tenant_id', $tenantId)
                    ->where('pandit_id', $panditId)
                    ->where('status', PanditAssignment::STATUS_COMPLETED)
                    ->count();

                // 2. Reviews from bookings assigned to this pandit
                $assignedBookingIds = PanditAssignment::query()
                    ->where('tenant_id', $tenantId)
                    ->where('pandit_id', $panditId)
                    ->pluck('booking_id')
                    ->all();

                $reviews = $bookingsPort->reviewsForBookings($assignedBookingIds);
                $reviewCount = count($reviews);

                $ratingAvg = 0.0;
                if ($reviewCount > 0) {
                    $ratings = array_column($reviews, 'rating');
                    $ratingAvg = round(array_sum($ratings) / $reviewCount, 1);
                }

                $profile->forceFill([
                    'total_bookings' => $totalBookings,
                    'rating_count' => $reviewCount,
                    'rating_avg' => $ratingAvg,
                ])->save();

                $totalRecomputed++;
            }
        }

        $this->info("Recomputed ratings for {$totalRecomputed} pandit profiles.");

        return 0;
    }
}

