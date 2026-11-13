<?php

namespace App\Modules\Jyotish\Http\Controllers;

use App\Domain\Jyotish\Astronomy\AyanamsaEngine;
use App\Domain\Jyotish\Astronomy\BirthProfileNormalizer;
use App\Domain\Jyotish\Chart\D1ChartGenerator;
use App\Domain\Jyotish\Chart\HouseSystem\HouseCuspCalculator;
use App\Domain\Jyotish\Geography\PlaceResolver;
use App\Modules\Jyotish\Http\JyotishProblemResponder;
use App\Modules\Jyotish\Http\Presenters\JyotishResultEnvelope;
use App\Modules\Jyotish\Models\BirthProfile;
use App\Modules\Jyotish\Models\Chart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * REST API Controller for Kundli & D1 Chart Operations (T-JY03-04).
 */
class ChartController extends Controller
{
    /**
     * Compute canonical D1 Kundli chart.
     * POST /api/v1/jyotish/charts/d1
     */
    public function calculateD1(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'birth_profile_id' => ['nullable', 'string', 'size:26'],
            'birth_date' => ['required_without:birth_profile_id', 'date_format:Y-m-d'],
            'birth_time' => ['required_without:birth_profile_id', 'date_format:H:i:s'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required_without:birth_profile_id', 'numeric', 'between:-90,90'],
            'longitude' => ['required_without:birth_profile_id', 'numeric', 'between:-180,180'],
            'timezone' => ['required_without:birth_profile_id', 'string', 'timezone:all'],
            'ayanamsa' => ['nullable', 'string'],
            'house_system' => ['nullable', 'string'],
            'persist' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return JyotishProblemResponder::validationFailed($validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $ayanamsa = $validated['ayanamsa'] ?? AyanamsaEngine::SYSTEM_LAHIRI;
        $houseSystem = $validated['house_system'] ?? HouseCuspCalculator::SYSTEM_WHOLE_SIGN;

        try {
            $birthProfile = null;

            if (! empty($validated['birth_profile_id'])) {
                /** @var BirthProfile|null $birthProfile */
                $birthProfile = BirthProfile::query()->find($validated['birth_profile_id']);
                if (! $birthProfile) {
                    return JyotishProblemResponder::respond(
                        status: 404,
                        errorCode: 'birth_profile_not_found',
                        title: 'Birth Profile Not Found',
                        detail: "Birth profile with ID '{$validated['birth_profile_id']}' not found in tenant context."
                    );
                }

                $jdUt1 = (float) $birthProfile->jd_utc;
                $jdTt = (float) $birthProfile->jd_tt;
                $lat = (float) $birthProfile->latitude;
                $lon = (float) $birthProfile->longitude;
            } else {
                $dateParts = explode('-', (string) $validated['birth_date']);
                $timeParts = explode(':', (string) $validated['birth_time']);

                $normalizer = new BirthProfileNormalizer(new PlaceResolver());
                $normalized = $normalizer->normalize([
                    'birth_place' => $validated['birth_place'] ?? 'Custom Coordinates',
                    'year' => (int) $dateParts[0],
                    'month' => (int) $dateParts[1],
                    'day' => (int) $dateParts[2],
                    'hour' => (int) ($timeParts[0] ?? 0),
                    'minute' => (int) ($timeParts[1] ?? 0),
                    'second' => (float) ($timeParts[2] ?? 0.0),
                    'latitude' => (float) $validated['latitude'],
                    'longitude' => (float) $validated['longitude'],
                    'iana_timezone' => $validated['timezone'],
                ]);

                $jdUt1 = $normalized['astronomical_time']->ut1->value;
                $jdTt = $normalized['astronomical_time']->tt->value;
                $lat = (float) $validated['latitude'];
                $lon = (float) $validated['longitude'];
            }

            $d1Payload = D1ChartGenerator::generate(
                jdUt1: $jdUt1,
                jdTt: $jdTt,
                latitude: $lat,
                longitude: $lon,
                ayanamsaSystem: $ayanamsa,
                houseSystemCode: $houseSystem
            );

            $chartRecord = null;
            $shouldPersist = $validated['persist'] ?? ($birthProfile !== null);

            if ($shouldPersist && $birthProfile !== null) {
                $profileHash = hash('sha256', "{$birthProfile->id}_{$ayanamsa}_{$houseSystem}");

                $chartRecord = Chart::query()->create([
                    'birth_profile_id' => $birthProfile->id,
                    'chart_type' => 'D1',
                    'ayanamsa_code' => $d1Payload['ayanamsa']['system'],
                    'house_system' => $d1Payload['house_system']['code'],
                    'profile_hash' => $profileHash,
                    'lagna_degrees' => $d1Payload['lagna']['sidereal_degrees'],
                    'positions_json' => $d1Payload['positions'],
                    'houses_json' => $d1Payload['houses'],
                    'vargas_json' => ['bhava_chalit' => $d1Payload['bhava_chalit']],
                ]);
            }

            $responsePayload = array_merge($d1Payload, [
                'chart_id' => $chartRecord?->id,
                'birth_profile_id' => $birthProfile?->id,
                'persisted' => $chartRecord !== null,
            ]);

            return response()->json(
                JyotishResultEnvelope::wrap($responsePayload, 'd1_chart_v1'),
                $chartRecord !== null ? 201 : 200
            );
        } catch (Throwable $e) {
            return JyotishProblemResponder::respond(
                status: 422,
                errorCode: 'calculation_failed',
                title: 'D1 Calculation Failed',
                detail: $e->getMessage()
            );
        }
    }

    /**
     * Retrieve persisted chart by ID.
     * GET /api/v1/jyotish/charts/{id}
     */
    public function show(string $id): JsonResponse
    {
        /** @var Chart|null $chart */
        $chart = Chart::query()->find($id);

        if (! $chart) {
            return JyotishProblemResponder::respond(
                status: 404,
                errorCode: 'chart_not_found',
                title: 'Chart Not Found',
                detail: "Chart with ID '{$id}' does not exist in this tenant."
            );
        }

        return response()->json(
            JyotishResultEnvelope::wrap($chart->toArray(), 'chart_detail_v1')
        );
    }
}
