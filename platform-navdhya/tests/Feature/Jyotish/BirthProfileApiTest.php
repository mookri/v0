<?php

namespace Tests\Feature\Jyotish;

use App\Modules\Jyotish\Models\BirthProfile;
use Tests\TenantIntegrationTestCase;

/**
 * Feature API tests for Devotee Birth Profile endpoints (T-JY01-02).
 * Verifies AC-T-JY01-02:
 *  - Creation, storage and retrieval of birth profiles.
 *  - Preservation of raw birth_place_text.
 *  - Accurate utc_at_us microseconds timestamp.
 *  - Ambiguous place & DST failure handling.
 */
final class BirthProfileApiTest extends TenantIntegrationTestCase
{
    public function test_creates_birth_profile_with_canonical_place(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant, ['jyotish:chart:write']);
        $headers = $this->tenantHeaders($tenant);

        $payload = [
            'name' => 'Aditi Sharma',
            'gender' => 'female',
            'birth_place' => 'Varanasi',
            'year' => 2026,
            'month' => 11,
            'day' => 24,
            'hour' => 17,
            'minute' => 30,
            'second' => 0,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/jyotish/birth-profiles', $payload, $headers)
            ->assertCreated();

        $this->assertSame('birth_profile_detail_v1', $response->json('meta.schema'));
        $data = $response->json('data');

        $this->assertNotEmpty($data['id']);
        $this->assertSame('Aditi Sharma', $data['name']);
        $this->assertSame('Varanasi', $data['birth_place_text']);
        $this->assertEqualsWithDelta(25.3176, $data['latitude'], 0.001);
        $this->assertSame('Asia/Kolkata', $data['iana_timezone']);

        // 2026-11-24 17:30 IST is 12:00:00 UTC (timestamp 1795521600 seconds = 1795521600000000 microseconds)
        $expectedUtcMicros = 1795521600000000;
        $this->assertSame($expectedUtcMicros, $data['utc_at_us']);

        // Verify database persistence
        $this->assertDatabaseHas('jyotish_birth_profiles', [
            'id' => $data['id'],
            'tenant_id' => $tenant->id,
            'name' => 'Aditi Sharma',
            'birth_place_text' => 'Varanasi',
        ], 'tenant_shared');
    }

    public function test_ambiguous_place_returns_422_with_candidates(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant, ['jyotish:chart:write']);
        $headers = $this->tenantHeaders($tenant);

        $payload = [
            'name' => 'Rajesh Kumar',
            'birth_place' => 'Hyderabad',
            'year' => 2026,
            'month' => 5,
            'day' => 10,
            'hour' => 10,
            'minute' => 0,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/jyotish/birth-profiles', $payload, $headers)
            ->assertStatus(422);

        $this->assertSame('validation_failed', $response->json('error.code'));
        $invalidParams = $response->json('error.invalid_params');
        $this->assertSame('ambiguous_place', $invalidParams[0]['code']);
        $this->assertGreaterThanOrEqual(2, count($invalidParams[0]['candidates']));
    }

    public function test_dst_spring_forward_gap_returns_422(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant, ['jyotish:chart:write']);
        $headers = $this->tenantHeaders($tenant);

        // London spring-forward 2026-03-29 01:30:00 does not exist
        $payload = [
            'name' => 'Arthur Dent',
            'birth_place' => 'London',
            'year' => 2026,
            'month' => 3,
            'day' => 29,
            'hour' => 1,
            'minute' => 30,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/jyotish/birth-profiles', $payload, $headers)
            ->assertStatus(422);

        $this->assertSame('invalid_birth_time', $response->json('error.invalid_params.0.code'));
    }

    public function test_reads_created_birth_profile(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant, ['jyotish:chart:write', 'jyotish:chart:read']);
        $headers = $this->tenantHeaders($tenant);

        $created = $this->actingAs($user)
            ->postJson('/api/v1/jyotish/birth-profiles', [
                'name' => 'Kavita Iyer',
                'birth_place' => 'Bengaluru',
                'year' => 2026,
                'month' => 1,
                'day' => 1,
                'hour' => 10,
                'minute' => 0,
                'second' => 0,
            ], $headers)
            ->assertCreated();

        $profileId = $created->json('data.id');

        $response = $this->actingAs($user)
            ->getJson("/api/v1/jyotish/birth-profiles/{$profileId}", $headers)
            ->assertOk();

        $this->assertSame($profileId, $response->json('data.id'));
        $this->assertSame('Kavita Iyer', $response->json('data.name'));
    }
}

