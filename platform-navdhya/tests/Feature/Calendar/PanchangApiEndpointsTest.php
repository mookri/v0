<?php

namespace Tests\Feature\Calendar;

use Tests\TenantIntegrationTestCase;

/**
 * Feature API tests for Daily & Monthly Panchang Endpoints (T-JY05-07).
 */
final class PanchangApiEndpointsTest extends TenantIntegrationTestCase
{
    public function test_get_daily_panchang_returns_complete_payload(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);

        $response = $this->withHeaders($headers)->getJson(route('calendar.panchang.daily', [
            'date' => '2026-11-13',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'timezone' => 'Asia/Kolkata',
        ]));

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertSame('success', $json['status']);
        $this->assertSame('daily_panchang_v1', $json['meta']['schema']);
        $this->assertArrayHasKey('data', $json);

        $data = $json['data'];
        $this->assertSame('2026-11-13', $data['date']);
        $this->assertArrayHasKey('limbs', $data);
        $this->assertArrayHasKey('solar_horizon', $data);
        $this->assertArrayHasKey('inauspicious_windows', $data);
        $this->assertArrayHasKey('day_choghadiya', $data);
        $this->assertArrayHasKey('observances', $data);
    }

    public function test_get_monthly_panchang_returns_all_days(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);

        $response = $this->withHeaders($headers)->getJson(route('calendar.panchang.monthly', [
            'year' => 2026,
            'month' => 11,
            'latitude' => 28.6139,
            'longitude' => 77.2090,
        ]));

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertSame('success', $json['status']);
        $this->assertSame('monthly_panchang_v1', $json['meta']['schema']);

        $data = $json['data'];
        $this->assertSame(2026, $data['year']);
        $this->assertSame(11, $data['month']);
        $this->assertSame(30, $data['days_count']);
        $this->assertCount(30, $data['grid']);

        $firstDay = $data['grid'][0];
        $this->assertSame(1, $firstDay['day']);
        $this->assertSame('2026-11-01', $firstDay['date']);
        $this->assertNotEmpty($firstDay['tithi']);
        $this->assertNotEmpty($firstDay['nakshatra']);
    }

    public function test_validation_failure_on_invalid_date(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);

        $response = $this->withHeaders($headers)->getJson(route('calendar.panchang.daily', [
            'date' => 'invalid-date-format',
        ]));

        $response->assertStatus(422);
    }
}
