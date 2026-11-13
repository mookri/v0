<?php

namespace App\Domain\Jyotish\Contracts;

/**
 * Port contract for birthplace geocoding and coordinates resolution.
 * Pure domain contract — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
interface GeocodingProvider
{
    /**
     * Resolve a birthplace query string to candidate places.
     *
     * @param string $query Location name, city, or hub query
     * @return list<array{place_id: string, name_en: string, latitude: float, longitude: float, elevation_m: int, iana_timezone: string, is_verified: bool}>
     */
    public function resolve(string $query): array;
}

