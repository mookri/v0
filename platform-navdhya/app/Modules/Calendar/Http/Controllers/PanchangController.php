<?php

namespace App\Modules\Calendar\Http\Controllers;

use App\Domain\Jyotish\Astronomy\Time\AstronomicalTime;
use App\Domain\Jyotish\Panchang\Festivals\VedicFestivalResolver;
use App\Domain\Jyotish\Panchang\PanchangOrchestrator;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

/**
 * High-Precision Calendar & Daily Panchang API Controller (T-JY05-07).
 */
class PanchangController extends Controller
{
    /**
     * Daily Five-Limb Panchang with Solar Transitions and Muhurats.
     * GET /api/v1/calendar/panchang
     */
    public function daily(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => ['nullable', 'date_format:Y-m-d'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'elevation_m' => ['nullable', 'integer', 'min:0'],
            'timezone' => ['nullable', 'string', 'timezone:all'],
            'ayanamsa' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'type' => 'https://api.navdhya.com/problems/validation_failed',
                'title' => 'Validation Failed',
                'status' => 422,
                'detail' => 'The given input failed validation.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $dateStr = (string) ($request->input('date') ?? CarbonImmutable::now('Asia/Kolkata')->format('Y-m-d'));
        $lat = (float) ($request->input('latitude') ?? 28.6139); // New Delhi
        $lon = (float) ($request->input('longitude') ?? 77.2090);
        $elevation = (float) ($request->input('elevation_m') ?? 0.0);
        $tz = (string) ($request->input('timezone') ?? 'Asia/Kolkata');
        $ayanamsa = (string) ($request->input('ayanamsa') ?? 'LAHIRI');

        $cacheKey = "panchang_daily_{$dateStr}_{$lat}_{$lon}_{$ayanamsa}";
        $isCached = Cache::has($cacheKey);

        $payload = Cache::remember($cacheKey, 1800, function () use ($dateStr, $lat, $lon, $elevation, $ayanamsa): array {
            $date = CarbonImmutable::parse($dateStr);
            $jdNoon = AstronomicalTime::calculateJulianDay($date->year, $date->month, $date->day, 6, 0, 0); // 06:00 UTC ~ 11:30 IST

            $panchang = PanchangOrchestrator::calculate(
                jdUt1: $jdNoon,
                jdTt: $jdNoon,
                latitude: $lat,
                longitude: $lon,
                elevationMeters: $elevation,
                ayanamsaSystem: $ayanamsa
            );

            $observances = VedicFestivalResolver::resolveObservances(
                tithiNumber: $panchang['limbs']['tithi']['number'],
                paksha: $panchang['limbs']['tithi']['paksha'],
                sunriseJd: (float) ($panchang['solar_horizon']['sunrise_jd'] ?? $jdNoon),
                sunsetJd: (float) ($panchang['solar_horizon']['sunset_jd'] ?? ($jdNoon + 0.5)),
                tithiEndJd: (float) $panchang['limbs']['tithi']['end_jd']
            );

            return array_merge($panchang, [
                'date' => $dateStr,
                'observances' => $observances,
            ]);
        });

        return response()->json([
            'status' => 'success',
            'data' => $payload,
            'meta' => [
                'schema' => 'daily_panchang_v1',
                'cache_hit' => $isCached,
                'calculated_at' => CarbonImmutable::now('UTC')->toIso8601String(),
            ],
        ]);
    }

    /**
     * Monthly Panchang Grid.
     * GET /api/v1/calendar/panchang/monthly
     */
    public function monthly(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'year' => ['required', 'integer', 'between:1800,2200'],
            'month' => ['required', 'integer', 'between:1,12'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'type' => 'https://api.navdhya.com/problems/validation_failed',
                'title' => 'Validation Failed',
                'status' => 422,
                'errors' => $validator->errors(),
            ], 422);
        }

        $year = (int) $request->input('year');
        $month = (int) $request->input('month');
        $lat = (float) ($request->input('latitude') ?? 28.6139);
        $lon = (float) ($request->input('longitude') ?? 77.2090);

        $startDate = CarbonImmutable::create($year, $month, 1);
        $daysInMonth = $startDate->daysInMonth;
        $grid = [];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $currentDate = $startDate->day($d);
            $jdNoon = AstronomicalTime::calculateJulianDay($year, $month, $d, 6, 0, 0);

            $p = PanchangOrchestrator::calculate(
                jdUt1: $jdNoon,
                jdTt: $jdNoon,
                latitude: $lat,
                longitude: $lon
            );

            $grid[] = [
                'day' => $d,
                'date' => $currentDate->format('Y-m-d'),
                'tithi' => $p['limbs']['tithi']['name'],
                'tithi_number' => $p['limbs']['tithi']['number'],
                'paksha' => $p['limbs']['tithi']['paksha'],
                'nakshatra' => $p['limbs']['nakshatra']['name'],
                'vara' => $p['limbs']['vara']['name'],
                'is_ekadashi' => in_array($p['limbs']['tithi']['number'], [11, 26], true),
                'is_purnima' => ($p['limbs']['tithi']['number'] === 15),
                'is_amavasya' => ($p['limbs']['tithi']['number'] === 30),
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'year' => $year,
                'month' => $month,
                'days_count' => $daysInMonth,
                'grid' => $grid,
            ],
            'meta' => [
                'schema' => 'monthly_panchang_v1',
            ],
        ]);
    }
}

