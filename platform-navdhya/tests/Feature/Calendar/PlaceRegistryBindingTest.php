<?php

namespace Tests\Feature\Calendar;

use App\Domain\Calendar\Exceptions\PlaceUnresolvedException;
use App\Domain\Calendar\Support\PlaceResolver;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Tests\TenantIntegrationTestCase;

class PlaceRegistryBindingTest extends TenantIntegrationTestCase
{
    public function test_canonical_hubs_resolve_with_verified_metadata(): void
    {
        $ujjain = PlaceResolver::resolveOrThrow('ujjain');
        $this->assertSame('reg_ujjain_central', $ujjain['region_id']);
        $this->assertEqualsWithDelta(23.1765, $ujjain['latitude'], 0.001);
        $this->assertEqualsWithDelta(75.7885, $ujjain['longitude'], 0.001);
        $this->assertTrue($ujjain['is_verified']);
        $this->assertContains('Asia/Kolkata', DateTimeZone::listIdentifiers());

        $varanasi = PlaceResolver::resolveOrThrow('varanasi');
        $this->assertSame('reg_varanasi_central', $varanasi['region_id']);
        $this->assertTrue($varanasi['is_verified']);

        $delhi = PlaceResolver::resolveOrThrow('delhi');
        $this->assertSame('reg_delhi_central', $delhi['region_id']);
    }

    public function test_unresolved_place_throws_place_unresolved_exception_closes_p14(): void
    {
        $this->expectException(PlaceUnresolvedException::class);
        PlaceResolver::resolveOrThrow('atlantis_ocean_fake_city');
    }

    public function test_database_calendar_places_lookup_on_central(): void
    {
        DB::connection('central')->table('calendar_places')->insert([
            'region_id' => 'reg_custom_mathura',
            'latitude' => 27.4924,
            'longitude' => 77.6737,
            'elevation_m' => 174.0,
            'iana_timezone' => 'Asia/Kolkata',
            'is_verified' => true,
        ]);

        $mathura = PlaceResolver::resolveOrThrow('reg_custom_mathura');
        $this->assertSame('reg_custom_mathura', $mathura['region_id']);
        $this->assertEqualsWithDelta(27.4924, $mathura['latitude'], 0.001);
        $this->assertTrue($mathura['is_verified']);
    }
}

