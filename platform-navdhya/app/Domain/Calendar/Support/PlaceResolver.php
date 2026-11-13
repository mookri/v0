<?php

namespace App\Domain\Calendar\Support;

use App\Domain\Calendar\Exceptions\PlaceUnresolvedException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Server-side Place and Coordinate Resolver.
 * Closes P-14 (eliminates client coordinate tampering).
 */
final class PlaceResolver
{
    /**
     * Canonical in-memory seeds for baseline spiritual hubs.
     *
     * @var array<string, array{
     *     region_id: string,
     *     name: string,
     *     latitude: float,
     *     longitude: float,
     *     elevation_m: float,
     *     iana_timezone: string,
     *     is_verified: bool
     * }>
     */
    public const CANONICAL_HUBS = [
        'ujjain' => [
            'region_id' => 'reg_ujjain_central',
            'name' => 'Ujjain (Mahakaleshwar)',
            'latitude' => 23.1765,
            'longitude' => 75.7885,
            'elevation_m' => 494.0,
            'iana_timezone' => 'Asia/Kolkata',
            'is_verified' => true,
        ],
        'varanasi' => [
            'region_id' => 'reg_varanasi_central',
            'name' => 'Varanasi (Kashi Vishwanath)',
            'latitude' => 25.3176,
            'longitude' => 82.9739,
            'elevation_m' => 80.7,
            'iana_timezone' => 'Asia/Kolkata',
            'is_verified' => true,
        ],
        'delhi' => [
            'region_id' => 'reg_delhi_central',
            'name' => 'New Delhi',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'elevation_m' => 216.0,
            'iana_timezone' => 'Asia/Kolkata',
            'is_verified' => true,
        ],
        'haridwar' => [
            'region_id' => 'reg_haridwar_central',
            'name' => 'Haridwar',
            'latitude' => 29.9457,
            'longitude' => 78.1642,
            'elevation_m' => 314.0,
            'iana_timezone' => 'Asia/Kolkata',
            'is_verified' => true,
        ],
        'ayodhya' => [
            'region_id' => 'reg_ayodhya_central',
            'name' => 'Ayodhya',
            'latitude' => 26.7922,
            'longitude' => 82.1998,
            'elevation_m' => 93.0,
            'iana_timezone' => 'Asia/Kolkata',
            'is_verified' => true,
        ],
        'puri' => [
            'region_id' => 'reg_puri_central',
            'name' => 'Puri (Jagannath Temple)',
            'latitude' => 19.8135,
            'longitude' => 85.8312,
            'elevation_m' => 5.0,
            'iana_timezone' => 'Asia/Kolkata',
            'is_verified' => true,
        ],
        'rameshwaram' => [
            'region_id' => 'reg_rameshwaram_central',
            'name' => 'Rameshwaram',
            'latitude' => 9.2876,
            'longitude' => 79.3129,
            'elevation_m' => 10.0,
            'iana_timezone' => 'Asia/Kolkata',
            'is_verified' => true,
        ],
        'tirupati' => [
            'region_id' => 'reg_tirupati_central',
            'name' => 'Tirupati (Balaji)',
            'latitude' => 13.6288,
            'longitude' => 79.4192,
            'elevation_m' => 150.0,
            'iana_timezone' => 'Asia/Kolkata',
            'is_verified' => true,
        ],
        'mumbai' => [
            'region_id' => 'reg_mumbai_central',
            'name' => 'Mumbai',
            'latitude' => 19.0760,
            'longitude' => 72.8777,
            'elevation_m' => 14.0,
            'iana_timezone' => 'Asia/Kolkata',
            'is_verified' => true,
        ],
        'bengaluru' => [
            'region_id' => 'reg_bengaluru_central',
            'name' => 'Bengaluru',
            'latitude' => 12.9716,
            'longitude' => 77.5946,
            'elevation_m' => 920.0,
            'iana_timezone' => 'Asia/Kolkata',
            'is_verified' => true,
        ],
    ];

    /**
     * Resolve a slug or region_id to canonical place record.
     *
     * @return array{
     *     region_id: string,
     *     name: string,
     *     latitude: float,
     *     longitude: float,
     *     elevation_m: float,
     *     iana_timezone: string,
     *     is_verified: bool
     * }|null
     */
    public static function resolve(string $placeIdentifier): ?array
    {
        $normalized = Str::slug(trim($placeIdentifier));

        // 1. Try in-memory canonical hubs
        if (isset(self::CANONICAL_HUBS[$normalized])) {
            return self::CANONICAL_HUBS[$normalized];
        }

        foreach (self::CANONICAL_HUBS as $hub) {
            if ($hub['region_id'] === $placeIdentifier) {
                return $hub;
            }
        }

        // 2. Query central database calendar_places if available
        try {
            $row = DB::connection('central')->table('calendar_places')
                ->where('region_id', $placeIdentifier)
                ->where('is_verified', true)
                ->first();

            if ($row !== null) {
                return [
                    'region_id' => $row->region_id,
                    'name' => $row->region_id,
                    'latitude' => (float) $row->latitude,
                    'longitude' => (float) $row->longitude,
                    'elevation_m' => (float) ($row->elevation_m ?? 0.0),
                    'iana_timezone' => $row->iana_timezone,
                    'is_verified' => (bool) $row->is_verified,
                ];
            }
        } catch (\Throwable) {
            // DB fallback failed or running without central DB
        }

        return null;
    }

    /**
     * Resolve or throw PlaceUnresolvedException.
     *
     * @return array{
     *     region_id: string,
     *     name: string,
     *     latitude: float,
     *     longitude: float,
     *     elevation_m: float,
     *     iana_timezone: string,
     *     is_verified: bool
     * }
     */
    public static function resolveOrThrow(string $placeIdentifier): array
    {
        $resolved = self::resolve($placeIdentifier);
        if ($resolved === null) {
            throw new PlaceUnresolvedException($placeIdentifier);
        }

        return $resolved;
    }
}

