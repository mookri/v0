<?php

namespace App\Modules\Recommendations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Recommendations\Models\RecoExclusion;
use App\Modules\Recommendations\Models\RecoImpression;
use App\Modules\Recommendations\Models\RecoInteraction;
use App\Modules\Recommendations\Models\RecoUserInterest;
use App\Modules\Recommendations\Models\RecoUserProfile;
use App\Modules\Search\Models\SearchQuery;
use App\Modules\Search\Models\SearchQueryEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserDiscoveryPreferencesController extends Controller
{
    private function resolveUserId(Request $request): int
    {
        $user = $request->user();
        $id = $user ? $user->id : ($request->header('X-User-Id') ?? $request->input('user_id'));

        if (! $id) {
            abort(401, 'Unauthenticated');
        }

        return (int) $id;
    }

    public function getPreferences(Request $request): JsonResponse
    {
        $userId = $this->resolveUserId($request);

        $profile = RecoUserProfile::firstOrCreate(
            ['user_id' => $userId],
            [
                'personalization_enabled' => true,
                'preferred_language' => 'en',
            ]
        );

        return response()->json([
            'user_id' => $profile->user_id,
            'personalization_enabled' => (bool) $profile->personalization_enabled,
            'tradition' => $profile->tradition,
            'gotra' => $profile->gotra,
            'preferred_region_id' => $profile->preferred_region_id,
            'preferred_language' => $profile->preferred_language,
            'last_active_at' => $profile->last_active_at?->toISOString(),
        ]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $userId = $this->resolveUserId($request);

        $validated = $request->validate([
            'personalization_enabled' => 'nullable|boolean',
            'tradition' => 'nullable|string|max:32',
            'gotra' => 'nullable|string|max:64',
            'preferred_region_id' => 'nullable|string|max:26',
            'preferred_language' => 'nullable|string|max:16',
        ]);

        $profile = RecoUserProfile::firstOrCreate(['user_id' => $userId]);

        if (array_key_exists('personalization_enabled', $validated)) {
            $profile->personalization_enabled = (bool) $validated['personalization_enabled'];
        }
        if (array_key_exists('tradition', $validated)) {
            $profile->tradition = $validated['tradition'];
        }
        if (array_key_exists('gotra', $validated)) {
            $profile->gotra = $validated['gotra'];
        }
        if (array_key_exists('preferred_region_id', $validated)) {
            $profile->preferred_region_id = $validated['preferred_region_id'];
        }
        if (array_key_exists('preferred_language', $validated)) {
            $profile->preferred_language = (string) $validated['preferred_language'];
        }

        $profile->save();

        return response()->json([
            'user_id' => $profile->user_id,
            'personalization_enabled' => (bool) $profile->personalization_enabled,
            'tradition' => $profile->tradition,
            'gotra' => $profile->gotra,
            'preferred_region_id' => $profile->preferred_region_id,
            'preferred_language' => $profile->preferred_language,
        ]);
    }

    public function getInterests(Request $request): JsonResponse
    {
        $userId = $this->resolveUserId($request);

        $interests = RecoUserInterest::where('user_id', $userId)
            ->orderByDesc('weight')
            ->get();

        return response()->json([
            'user_id' => $userId,
            'interests' => $interests->map(fn ($i) => [
                'id' => $i->id,
                'kind' => $i->interest_kind,
                'key' => $i->interest_key,
                'weight' => (float) $i->weight,
                'source' => $i->source,
                'decay_half_life_days' => $i->decay_half_life_days,
            ]),
        ]);
    }

    public function addExclusion(Request $request): JsonResponse
    {
        $userId = $this->resolveUserId($request);

        $validated = $request->validate([
            'entity_type' => 'required|string|max:32',
            'entity_id' => 'required|string|max:36',
            'reason' => 'nullable|string|max:64',
            'expires_at' => 'nullable|date',
        ]);

        $exclusion = RecoExclusion::updateOrCreate(
            [
                'user_id' => $userId,
                'entity_type' => $validated['entity_type'],
                'entity_id' => $validated['entity_id'],
            ],
            [
                'reason' => $validated['reason'] ?? 'user_dismissed',
                'expires_at' => $validated['expires_at'] ?? null,
            ]
        );

        return response()->json([
            'exclusion' => [
                'id' => $exclusion->id,
                'entity_type' => $exclusion->entity_type,
                'entity_id' => $exclusion->entity_id,
                'reason' => $exclusion->reason,
            ],
        ], 201);
    }

    public function deleteSearchHistory(Request $request): JsonResponse
    {
        $userId = $this->resolveUserId($request);

        $deletedEvents = SearchQueryEvent::where('user_id', $userId)->delete();
        $deletedQueries = SearchQuery::where('user_id', $userId)->delete();

        return response()->json([
            'deleted' => true,
            'deleted_events_count' => $deletedEvents,
            'deleted_queries_count' => $deletedQueries,
        ]);
    }

    public function resetRecommendations(Request $request): JsonResponse
    {
        $userId = $this->resolveUserId($request);

        // Wipe only inferred history, keeping explicit devotee preferences intact
        $deletedInterests = RecoUserInterest::where('user_id', $userId)
            ->where('source', 'inferred')
            ->delete();

        $deletedInteractions = RecoInteraction::where('user_id', $userId)->delete();
        $deletedImpressions = RecoImpression::where('user_id', $userId)->delete();

        return response()->json([
            'reset' => true,
            'inferred_interests_cleared' => $deletedInterests,
            'interactions_cleared' => $deletedInteractions,
            'impressions_cleared' => $deletedImpressions,
            'message' => 'Inferred recommendation history reset.',
        ]);
    }
}
