<?php

namespace App\Domain\Jyotish\Geography;

use App\Domain\Jyotish\Contracts\GeocodingProvider;

/**
 * Pure domain Place Resolver with canonical Indian hubs and international astrological reference centers (T-JY01-01).
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class PlaceResolver implements GeocodingProvider
{
    /**
     * Curated canonical hubs list.
     * @var list<array{place_id: string, name_en: string, latitude: float, longitude: float, elevation_m: int, iana_timezone: string, is_verified: bool, aliases?: list<string>}>
     */
    public const CANONICAL_HUBS = [
        [
            'place_id' => 'hub_in_del',
            'name_en' => 'New Delhi, India',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'elevation_m' => 216,
            'iana_timezone' => 'Asia/Kolkata',
            'is_verified' => true,
            'aliases' => ['delhi', 'new delhi', 'dilli'],
        ],
        [
            'place_id' => 'hub_in_bom',
            'name_en' => 'Mumbai, Maharashtra, India',
            'latitude' => 19.0760,
            'longitude' => 72.8777,
            'elevation_m' => 14,
            'iana_timezone' => 'Asia/Kolkata',
            'is_verified' => true,
            'aliases' => ['mumbai', 'bombay'],
        ],
        [
            'place_id' => 'hub_in_blr',
            'name_en' => 'Bengaluru, Karnataka, India',
            'latitude' => 12.9716,
            'longitude' => 77.5946,
            'elevation_m' => 920,
            'iana_timezone' => 'Asia/Kolkata',
            'is_verified' => true,
            'aliases' => ['bengaluru', 'bangalore'],
        ],
        [
            'place_id' => 'hub_in_vns',
            'name_en' => 'Varanasi, Uttar Pradesh, India',
            'latitude' => 25.3176,
            'longitude' => 82.9739,
            'elevation_m' => 81,
            'iana_timezone' => 'Asia/Kolkata',
            'is_verified' => true,
            'aliases' => ['varanasi', 'kashi', 'banaras'],
        ],
        [
            'place_id' => 'hub_in_ujn',
            'name_en' => 'Ujjain, Madhya Pradesh, India',
            'latitude' => 23.1765,
            'longitude' => 75.7885,
            'elevation_m' => 492,
            'iana_timezone' => 'Asia/Kolkata',
            'is_verified' => true,
            'aliases' => ['ujjain', 'ujjayini', 'avanti'],
        ],
        [
            'place_id' => 'hub_in_hrd',
            'name_en' => 'Haridwar, Uttarakhand, India',
            'latitude' => 29.9457,
            'longitude' => 78.1642,
            'elevation_m' => 314,
            'iana_timezone' => 'Asia/Kolkata',
            'is_verified' => true,
            'aliases' => ['haridwar', 'hardwar', 'mayapuri'],
        ],
        [
            'place_id' => 'hub_in_hyd',
            'name_en' => 'Hyderabad, Telangana, India',
            'latitude' => 17.3850,
            'longitude' => 78.4867,
            'elevation_m' => 542,
            'iana_timezone' => 'Asia/Kolkata',
            'is_verified' => true,
            'aliases' => ['hyderabad, telangana', 'hyderabad, india'],
        ],
        [
            'place_id' => 'hub_pk_hyd',
            'name_en' => 'Hyderabad, Sindh, Pakistan',
            'latitude' => 25.3960,
            'longitude' => 68.3578,
            'elevation_m' => 13,
            'iana_timezone' => 'Asia/Karachi',
            'is_verified' => true,
            'aliases' => ['hyderabad, sindh', 'hyderabad, pakistan'],
        ],
        [
            'place_id' => 'hub_gb_lon',
            'name_en' => 'London, United Kingdom',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'elevation_m' => 11,
            'iana_timezone' => 'Europe/London',
            'is_verified' => true,
            'aliases' => ['london'],
        ],
        [
            'place_id' => 'hub_us_nyc',
            'name_en' => 'New York, NY, USA',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'elevation_m' => 10,
            'iana_timezone' => 'America/New_York',
            'is_verified' => true,
            'aliases' => ['new york', 'nyc', 'new york city'],
        ],
    ];

    public function resolve(string $query): array
    {
        $normalized = strtolower(trim($query));
        if ($normalized === '') {
            return [];
        }

        $matches = [];

        foreach (self::CANONICAL_HUBS as $hub) {
            $name = strtolower($hub['name_en']);
            $aliases = array_map('strtolower', $hub['aliases'] ?? []);

            if ($normalized === $name || in_array($normalized, $aliases, true)) {
                $matches[] = $this->formatHub($hub);
                continue;
            }

            if (str_contains($name, $normalized)) {
                $matches[] = $this->formatHub($hub);
                continue;
            }

            foreach ($aliases as $alias) {
                if (str_contains($alias, $normalized)) {
                    $matches[] = $this->formatHub($hub);
                    break;
                }
            }
        }

        return $matches;
    }

    /**
     * @param array<string, mixed> $hub
     * @return array{place_id: string, name_en: string, latitude: float, longitude: float, elevation_m: int, iana_timezone: string, is_verified: bool}
     */
    private function formatHub(array $hub): array
    {
        return [
            'place_id' => (string) $hub['place_id'],
            'name_en' => (string) $hub['name_en'],
            'latitude' => (float) $hub['latitude'],
            'longitude' => (float) $hub['longitude'],
            'elevation_m' => (int) $hub['elevation_m'],
            'iana_timezone' => (string) $hub['iana_timezone'],
            'is_verified' => (bool) $hub['is_verified'],
        ];
    }
}

