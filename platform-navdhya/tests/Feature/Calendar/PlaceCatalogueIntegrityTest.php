<?php

declare(strict_types=1);

namespace Tests\Feature\Calendar;

use Illuminate\Support\Facades\DB;
use Tests\TenantIntegrationTestCase;

final class PlaceCatalogueIntegrityTest extends TenantIntegrationTestCase
{
    public function test_place_catalogue_contains_at_least_300_verified_places(): void
    {
        $this->seed(\Database\Seeders\JyotishPlacesSeeder::class);

        $count = DB::connection('central')->table('jyotish_places')->count();
        $this->assertGreaterThanOrEqual(300, $count, "Expected at least 300 places in catalogue, found {$count}.");

        $verifiedCount = DB::connection('central')->table('jyotish_places')
            ->where('is_verified', true)
            ->count();
        $this->assertGreaterThanOrEqual(300, $verifiedCount, "Expected at least 300 verified places.");
    }

    public function test_all_places_have_valid_wgs84_coordinates_and_valid_iana_timezone(): void
    {
        $this->seed(\Database\Seeders\JyotishPlacesSeeder::class);

        $places = DB::connection('central')->table('jyotish_places')->get();

        $validTimezones = \DateTimeZone::listIdentifiers();
        $validTzMap = array_flip($validTimezones);

        $this->assertNotEmpty($places);

        foreach ($places as $place) {
            // Latitude [-90, 90]
            $this->assertGreaterThanOrEqual(-90.0, (float) $place->latitude, "Place {$place->place_id} latitude < -90");
            $this->assertLessThanOrEqual(90.0, (float) $place->latitude, "Place {$place->place_id} latitude > 90");

            // Longitude [-180, 180]
            $this->assertGreaterThanOrEqual(-180.0, (float) $place->longitude, "Place {$place->place_id} longitude < -180");
            $this->assertLessThanOrEqual(180.0, (float) $place->longitude, "Place {$place->place_id} longitude > 180");

            // Valid IANA timezone
            $this->assertArrayHasKey(
                $place->iana_timezone,
                $validTzMap,
                "Place {$place->place_id} has invalid IANA timezone {$place->iana_timezone}"
            );

            // Names present
            $this->assertNotEmpty($place->name_en, "Place {$place->place_id} has empty English name");
        }
    }
}
