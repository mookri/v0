<?php

namespace App\Modules\Search\Services;

use App\Modules\Booking\Models\BookingLine;
use App\Modules\Search\Models\SearchDictionary;
use App\Modules\Search\Models\SearchDocument;
use App\Modules\Search\Models\SearchImpression;
use App\Modules\Search\Models\SearchSelection;
use Illuminate\Support\Facades\Schema;

class PopularityProjector
{
    private const ALPHA = 1.0;
    private const BETA = 20.0;

    /**
     * Compute and project popularity metrics onto SearchDocument and SearchDictionary.
     *
     * @return array{documents_updated: int, views_tracked: int, clicks_tracked: int}
     */
    public function project(): array
    {
        $since7d = now()->subDays(7);
        $since30d = now()->subDays(30);

        // 1. Collect views (impressions) in last 7 days
        $viewsMap = [];
        if (Schema::connection('central')->hasTable('search_impressions')) {
            $views = SearchImpression::where('impressed_at', '>=', $since7d)
                ->selectRaw('entity_type, entity_id, COUNT(*) as count')
                ->groupBy('entity_type', 'entity_id')
                ->get();
            foreach ($views as $v) {
                $viewsMap[$v->entity_type . ':' . $v->entity_id] = (int) $v->getAttribute('count');
            }
        }

        // 2. Collect clicks (selections) in last 7 days
        $clicksMap = [];
        if (Schema::connection('central')->hasTable('search_selections')) {
            $clicks = SearchSelection::where('selected_at', '>=', $since7d)
                ->selectRaw('entity_type, entity_id, COUNT(*) as count')
                ->groupBy('entity_type', 'entity_id')
                ->get();
            foreach ($clicks as $c) {
                $clicksMap[$c->entity_type . ':' . $c->entity_id] = (int) $c->getAttribute('count');
            }
        }

        // 3. Collect bookings in last 30 days
        $bookingsMap = [];
        if (Schema::connection('tenant_shared')->hasTable('booking_lines')) {
            try {
                $lines = BookingLine::where('created_at', '>=', $since30d)->get();
                foreach ($lines as $line) {
                    if ($line->selected_pandit_id) {
                        $key = 'pandit:' . $line->selected_pandit_id;
                        $bookingsMap[$key] = ($bookingsMap[$key] ?? 0) + 1;
                    }
                    if ($line->selected_temple_id) {
                        $key = 'temple:' . $line->selected_temple_id;
                        $bookingsMap[$key] = ($bookingsMap[$key] ?? 0) + 1;
                    }
                    if ($line->sku) {
                        $key = 'puja_service:' . $line->sku;
                        $bookingsMap[$key] = ($bookingsMap[$key] ?? 0) + 1;
                    }
                }
            } catch (\Throwable) {
                // Fail-open for test environments
            }
        }

        // 4. Update SearchDocuments in chunks
        $updated = 0;
        SearchDocument::chunkById(200, function ($docs) use ($viewsMap, $clicksMap, $bookingsMap, &$updated) {
            foreach ($docs as $doc) {
                $key = $doc->entity_type . ':' . $doc->entity_id;
                $views = $viewsMap[$key] ?? 0;
                $clicks = $clicksMap[$key] ?? 0;
                $bookings = $bookingsMap[$key] ?? 0;

                // Bayesian-smoothed CTR
                $ctrPosterior = ($clicks + self::ALPHA) / ($views + self::ALPHA + self::BETA);
                $bookingSignal = min(1.0, $bookings / 10.0);
                $popularityScore = ($ctrPosterior * 0.60) + ($bookingSignal * 0.40);

                $doc->update([
                    'views_7d' => $views,
                    'bookings_30d' => $bookings,
                    'popularity_updated_at' => now(),
                ]);

                // Update dictionary popularity for primary document term keys
                if (! empty($doc->title)) {
                    $termKey = mb_strtolower(trim($doc->title), 'UTF-8');
                    SearchDictionary::where('term_key', $termKey)
                        ->update(['popularity' => (int) round($popularityScore * 1000)]);
                }

                $updated++;
            }
        });

        return [
            'documents_updated' => $updated,
            'views_tracked' => count($viewsMap),
            'clicks_tracked' => count($clicksMap),
        ];
    }
}
