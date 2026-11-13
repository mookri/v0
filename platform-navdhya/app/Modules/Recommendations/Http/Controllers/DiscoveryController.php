<?php

namespace App\Modules\Recommendations\Http\Controllers;

use App\Modules\Recommendations\Models\RecoImpression;
use App\Modules\Recommendations\Models\RecoInteraction;
use App\Modules\Recommendations\Services\ContentTaxonomyCandidateSource;
use App\Modules\Recommendations\Services\PopularityCandidateSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DiscoveryController
{
    public function __construct(
        private readonly PopularityCandidateSource $popularitySource,
        private readonly ContentTaxonomyCandidateSource $taxonomySource,
    ) {
    }

    /**
     * POST /api/v1/discovery
     */
    public function feed(Request $request): JsonResponse
    {
        $surface = (string) $request->input('surface', 'home_pujas');
        $limit = min(max((int) $request->input('limit', 10), 1), 30);
        $city = $request->input('city');
        $city = is_string($city) && $city !== '' ? $city : null;
        $category = $request->input('category');
        $category = is_string($category) && $category !== '' ? $category : null;
        $entityType = $request->input('entity_type');
        $entityType = is_string($entityType) && $entityType !== '' ? $entityType : null;

        $user = $request->user();
        $userId = $user ? (int) $user->id : null;
        $sessionId = (string) ($request->header('X-Session-Id') ?: $request->input('session_id') ?: Str::ulid());

        // Fetch popularity candidates
        $candidates = $this->popularitySource->getCandidates(
            surface: $surface,
            userId: $userId,
            city: $city,
            entityType: $entityType,
            limit: $limit
        );

        // Fallback to taxonomy if insufficient
        if ($candidates->count() < $limit && $category) {
            $taxCandidates = $this->taxonomySource->getCandidates(
                userId: $userId,
                category: $category,
                limit: $limit - $candidates->count()
            );
            $candidates = $candidates->merge($taxCandidates);
        }

        $cards = [];
        $impressionRows = [];
        $now = now();

        foreach ($candidates->values() as $index => $item) {
            $doc = $item['document'];
            $card = $doc->card_json;
            if (is_string($card)) {
                $decoded = json_decode($card, true);
                $card = is_array($decoded) ? $decoded : [];
            }

            $cards[] = [
                'entity_type' => $doc->entity_type,
                'entity_id' => $doc->entity_id,
                'slug' => $doc->slug,
                'title' => $doc->title,
                'subtitle' => $doc->subtitle,
                'url' => $doc->url_path,
                'city' => $doc->city,
                'image' => $doc->image_url,
                'card' => is_array($card) ? $card : [],
                'score' => $item['score'],
                'reason_code' => $item['reason_code'],
                'reason_label' => $item['reason_label'],
            ];

            $impressionRows[] = [
                'id' => (string) Str::ulid(),
                'surface' => $surface,
                'user_id' => $userId,
                'session_id' => $sessionId,
                'entity_type' => $doc->entity_type,
                'entity_id' => $doc->entity_id,
                'position' => $index,
                'reason_code' => $item['reason_code'],
                'impressed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Asynchronously record impressions
        if (! empty($impressionRows)) {
            try {
                RecoImpression::insert($impressionRows);
            } catch (\Throwable) {
                // Fail-open
            }
        }

        $etag = '"' . md5(json_encode(array_column($cards, 'entity_id'))) . '"';

        $response = response()->json([
            'data' => [
                'surface' => $surface,
                'items' => $cards,
            ],
        ]);
        $response->headers->set('ETag', $etag);
        return $response;
    }

    /**
     * POST /api/v1/discovery/impressions
     */
    public function impressions(Request $request): JsonResponse
    {
        $batch = $request->input('impressions', []);
        if (! is_array($batch) || empty($batch)) {
            return response()->json(['error' => 'Invalid impressions batch'], 422);
        }

        $user = $request->user();
        $userId = $user ? (int) $user->id : null;
        $now = now();
        $rows = [];

        foreach ($batch as $imp) {
            if (empty($imp['entity_type']) || empty($imp['entity_id'])) {
                continue;
            }
            $rows[] = [
                'id' => (string) Str::ulid(),
                'surface' => (string) ($imp['surface'] ?? 'home_pujas'),
                'user_id' => isset($imp['user_id']) ? (int) $imp['user_id'] : $userId,
                'session_id' => (string) ($imp['session_id'] ?? Str::ulid()),
                'entity_type' => (string) $imp['entity_type'],
                'entity_id' => (string) $imp['entity_id'],
                'position' => (int) ($imp['position'] ?? 0),
                'reason_code' => (string) ($imp['reason_code'] ?? 'POPULAR_IN_CATEGORY'),
                'impressed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($rows)) {
            RecoImpression::insert($rows);
        }

        return response()->json(['data' => ['accepted' => count($rows)]]);
    }

    /**
     * POST /api/v1/discovery/interactions
     */
    public function interactions(Request $request): JsonResponse
    {
        $batch = $request->input('interactions', []);
        if (! is_array($batch) || empty($batch)) {
            return response()->json(['error' => 'Invalid interactions batch'], 422);
        }

        $user = $request->user();
        $userId = $user ? (int) $user->id : null;
        $now = now();
        $rows = [];

        foreach ($batch as $act) {
            if (empty($act['entity_type']) || empty($act['entity_id'])) {
                continue;
            }
            $rows[] = [
                'id' => (string) Str::ulid(),
                'reco_impression_id' => $act['impression_id'] ?? null,
                'user_id' => isset($act['user_id']) ? (int) $act['user_id'] : $userId,
                'session_id' => $act['session_id'] ?? null,
                'entity_type' => (string) $act['entity_type'],
                'entity_id' => (string) $act['entity_id'],
                'interaction_type' => (string) ($act['action'] ?? $act['type'] ?? 'click'),
                'dwell_ms' => (int) ($act['dwell_ms'] ?? 0),
                'interacted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($rows)) {
            RecoInteraction::insert($rows);
        }

        return response()->json(['data' => ['accepted' => count($rows)]]);
    }
}

