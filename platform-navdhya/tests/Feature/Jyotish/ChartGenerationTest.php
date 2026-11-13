<?php

namespace Tests\Feature\Jyotish;

use App\Modules\Jyotish\Models\BirthProfile;
use App\Modules\Jyotish\Models\Chart;
use Tests\TenantIntegrationTestCase;

/**
 * Feature API tests for D1 Kundli Generation & Persistence (T-JY03-04).
 */
final class ChartGenerationTest extends TenantIntegrationTestCase
{
    public function test_calculate_d1_with_inline_coordinates(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['jyotish:chart:write', 'jyotish:chart:read']);
        $headers = $this->tenantHeaders($tenant);

        $response = $this->withHeaders($headers)
            ->postJson(route('jyotish.charts.d1'), [
                'birth_date' => '1990-05-15',
                'birth_time' => '14:30:00',
                'birth_place' => 'New Delhi, India',
                'latitude' => 28.6139,
                'longitude' => 77.2090,
                'timezone' => 'Asia/Kolkata',
                'ayanamsa' => 'LAHIRI',
                'house_system' => 'WHOLE_SIGN',
            ]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertSame('d1_chart_v1', $json['meta']['schema']);
        $this->assertArrayHasKey('data', $json);

        $data = $json['data'];
        $this->assertSame('D1', $data['chart_type']);
        $this->assertSame('LAHIRI', $data['ayanamsa']['system']);
        $this->assertSame('WHOLE_SIGN', $data['house_system']['code']);

        // Lagna verification
        $this->assertArrayHasKey('lagna', $data);
        $this->assertGreaterThanOrEqual(0.0, $data['lagna']['sidereal_degrees']);
        $this->assertLessThan(360.0, $data['lagna']['sidereal_degrees']);
        $this->assertSame('normal', $data['lagna']['horizon_status']);

        // 9 Grahas verification
        $this->assertArrayHasKey('positions', $data);
        $this->assertCount(9, $data['positions']);
        foreach (['sun', 'moon', 'mars', 'mercury', 'jupiter', 'venus', 'saturn', 'rahu', 'ketu'] as $graha) {
            $this->assertArrayHasKey($graha, $data['positions']);
            $pos = $data['positions'][$graha];
            $this->assertArrayHasKey('sidereal_degrees', $pos);
            $this->assertArrayHasKey('rashi', $pos);
            $this->assertArrayHasKey('nakshatra', $pos);
            $this->assertGreaterThanOrEqual(1, $pos['d1_bhava']);
            $this->assertLessThanOrEqual(12, $pos['d1_bhava']);
        }

        // 12 Houses verification
        $this->assertArrayHasKey('houses', $data);
        $this->assertCount(12, $data['houses']);

        // Bhava Chalit verification
        $this->assertArrayHasKey('bhava_chalit', $data);
        $this->assertCount(12, $data['bhava_chalit']['bhavas']);
        $this->assertCount(9, $data['bhava_chalit']['planetary_placements']);
    }

    public function test_calculate_and_persist_chart_with_birth_profile_id(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['jyotish:chart:write', 'jyotish:chart:read']);
        $headers = $this->tenantHeaders($tenant);

        // 1. Create a BirthProfile first
        $profileResponse = $this->withHeaders($headers)
            ->postJson(route('jyotish.birth_profiles.store'), [
                'name' => 'Aditya Sharma',
                'gender' => 'male',
                'birth_place' => 'Ujjain',
                'year' => 1995,
                'month' => 10,
                'day' => 24,
                'hour' => 6,
                'minute' => 15,
                'second' => 0,
            ]);

        $profileResponse->assertStatus(201);
        $profileId = $profileResponse->json('data.id');

        // 2. Generate and persist D1 chart using birth_profile_id
        $chartResponse = $this->withHeaders($headers)
            ->postJson(route('jyotish.charts.d1'), [
                'birth_profile_id' => $profileId,
                'ayanamsa' => 'LAHIRI',
                'house_system' => 'SRIPATI',
                'persist' => true,
            ]);

        $chartResponse->assertStatus(201);
        $chartId = $chartResponse->json('data.chart_id');
        $this->assertNotEmpty($chartId);
        $this->assertTrue($chartResponse->json('data.persisted'));

        // 3. Query the persisted chart via GET /charts/{id}
        $getResponse = $this->withHeaders($headers)
            ->getJson(route('jyotish.charts.show', ['id' => $chartId]));

        $getResponse->assertStatus(200);
        $this->assertSame($chartId, $getResponse->json('data.id'));
        $this->assertSame('D1', $getResponse->json('data.chart_type'));
        $this->assertSame('SRIPATI', $getResponse->json('data.house_system'));
    }

    public function test_ayanamsa_switch_produces_distinct_sidereal_lagna(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['jyotish:chart:write', 'jyotish:chart:read']);
        $headers = $this->tenantHeaders($tenant);

        $input = [
            'birth_date' => '2000-01-01',
            'birth_time' => '12:00:00',
            'birth_place' => 'Varanasi, India',
            'latitude' => 25.3176,
            'longitude' => 82.9739,
            'timezone' => 'Asia/Kolkata',
        ];

        $lahiriRes = $this->withHeaders($headers)
            ->postJson(route('jyotish.charts.d1'), array_merge($input, ['ayanamsa' => 'LAHIRI']));
        $ramanRes = $this->withHeaders($headers)
            ->postJson(route('jyotish.charts.d1'), array_merge($input, ['ayanamsa' => 'RAMAN']));

        $lahiriLagna = $lahiriRes->json('data.lagna.sidereal_degrees');
        $ramanLagna = $ramanRes->json('data.lagna.sidereal_degrees');

        // Raman ayanamsa is approximately 1.44° less than Lahiri, so Raman sidereal Lagna is ~1.44° greater
        $this->assertNotEquals($lahiriLagna, $ramanLagna);
        $diff = $ramanLagna - $lahiriLagna;
        if ($diff < 0) {
            $diff += 360.0;
        }
        $this->assertEqualsWithDelta(1.44, $diff, 0.1);
    }
}
