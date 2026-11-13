<?php

namespace App\Modules\Catalog\Console;

use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Customers\Models\UserEvent;
use App\Platform\Outbox\OutboxEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

final class RefreshCatalogInsightsCommand extends Command
{
    protected $signature = 'catalog:refresh-insights {--days=30 : Window in days}';

    protected $description = 'Rolls up catalog discovery telemetry and conversion funnel insights (F5)';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $this->info("Refreshing Catalog Discovery & Conversion Insights (last {$days} days)...");

        $cutoff = Carbon::now('UTC')->subDays($days);
        $items = CatalogItem::query()
            ->where('status', CatalogItem::STATUS_ACTIVE)
            ->get();

        if ($items->isEmpty()) {
            $this->info('No active catalog items to analyze.');
            return 0;
        }

        $rows = [];
        $hasUserEvents = Schema::connection('tenant_shared')->hasTable('user_events');
        $hasOutbox = Schema::connection('tenant_shared')->hasTable('outbox_events');

        foreach ($items as $item) {
            $views = 0;
            $quotes = 0;
            $bookings = 0;

            if ($hasUserEvents) {
                $views = UserEvent::query()
                    ->where('event_type', 'view_item')
                    ->where('occurred_at', '>=', $cutoff)
                    ->whereJsonContains('properties->sku', $item->sku)
                    ->count();

                $quotes = UserEvent::query()
                    ->where('event_type', 'quote_viewed')
                    ->where('occurred_at', '>=', $cutoff)
                    ->whereJsonContains('properties->sku', $item->sku)
                    ->count();
            }

            if ($hasOutbox) {
                $bookings = OutboxEvent::query()
                    ->where('event_type', 'booking.reservation.created')
                    ->where('occurred_at', '>=', $cutoff)
                    ->whereJsonContains('payload->service_sku', $item->sku)
                    ->count();
            }

            $conversionRate = $views > 0 ? round(($bookings / $views) * 100, 1) . '%' : 'N/A';

            $rows[] = [
                'SKU' => $item->sku,
                'Name' => $item->name,
                'Kind' => $item->item_kind,
                'Views' => $views,
                'Quotes' => $quotes,
                'Bookings' => $bookings,
                'Conv %' => $conversionRate,
            ];
        }

        $this->table(['SKU', 'Name', 'Kind', 'Views', 'Quotes', 'Bookings', 'Conv %'], $rows);
        $this->info("Catalog insights successfully rolled up across {$items->count()} active SKUs.");

        return 0;
    }
}

