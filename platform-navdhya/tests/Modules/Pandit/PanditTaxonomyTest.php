<?php

namespace Tests\Modules\Pandit;

use App\Models\User;
use App\Modules\Pandit\Contracts\GotraList;
use App\Modules\Pandit\Contracts\ListsPanditProfiles;
use App\Modules\Pandit\Contracts\VedicTradition;
use App\Modules\Pandit\Models\PanditProfile;
use App\Modules\Pandit\Models\PanditShakha;
use App\Platform\Tenancy\Models\Tenant;
use Tests\TenantIntegrationTestCase;

/**
 * Pandit Vedic Taxonomy tests (Task B1): validates Vedic traditions, gotras,
 * shakhas, travel buffers, timezones, and public directory filtering.
 */
final class PanditTaxonomyTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: Tenant, 1: User}
     */
    private function applicant(): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->actingAs($user);

        return [$tenant, $user];
    }

    private function seedProfile(Tenant $tenant, User $user, array $overrides = []): PanditProfile
    {
        $this->resolveContext($tenant);

        try {
            return PanditProfile::factory()->create(array_merge([
                'user_id' => $user->getAuthIdentifier(),
            ], $overrides));
        } finally {
            $this->clearContext();
        }
    }

    public function test_gotra_list_canonical_validation(): void
    {
        $this->assertTrue(GotraList::isValid('BHARADWAJA'));
        $this->assertTrue(GotraList::isValid('kashyapa'));
        $this->assertTrue(GotraList::isValid('VASISHTHA'));
        $this->assertTrue(GotraList::isValid('OTHER'));
        $this->assertFalse(GotraList::isValid('UNKNOWN_LINEAGE'));
    }

    public function test_own_profile_patch_accepts_valid_vedic_taxonomy(): void
    {
        [$tenant, $user] = $this->applicant();
        $profile = $this->seedProfile($tenant, $user);
        $headers = $this->tenantHeaders($tenant);

        // Seed a valid shakha
        $this->resolveContext($tenant);
        try {
            PanditShakha::query()->create([
                'code' => 'TAITTIRIYA',
                'veda' => 'YAJUR_KRISHNA',
                'name' => 'Taittiriya Shakha',
                'is_active' => true,
            ]);
        } finally {
            $this->clearContext();
        }

        $payload = [
            'tradition' => VedicTradition::SMARTHA->value,
            'gotra' => 'BHARADWAJA',
            'shakha_code' => 'TAITTIRIYA',
            'travel_buffer_minutes' => 45,
            'travel_radius_km' => 30,
            'timezone' => 'Asia/Kolkata',
        ];

        $response = $this->patchJson('/api/v1/pandit/profile', $payload, $headers);
        $response->assertOk()
            ->assertJsonPath('data.tradition', 'SMARTHA')
            ->assertJsonPath('data.gotra', 'BHARADWAJA')
            ->assertJsonPath('data.shakha_code', 'TAITTIRIYA')
            ->assertJsonPath('data.travel_buffer_minutes', 45)
            ->assertJsonPath('data.travel_radius_km', 30)
            ->assertJsonPath('data.timezone', 'Asia/Kolkata');

        $this->resolveContext($tenant);
        try {
            $fresh = PanditProfile::query()->find($profile->getKey());
            $this->assertNotNull($fresh);
            $this->assertSame(VedicTradition::SMARTHA, $fresh->tradition);
            $this->assertSame('BHARADWAJA', $fresh->gotra);
            $this->assertSame('TAITTIRIYA', $fresh->shakha_code);
            $this->assertSame(45, $fresh->travel_buffer_minutes);
            $this->assertSame(30, $fresh->travel_radius_km);
            $this->assertSame('Asia/Kolkata', $fresh->timezone);
        } finally {
            $this->clearContext();
        }
    }

    public function test_own_profile_patch_rejects_invalid_tradition(): void
    {
        [$tenant, $user] = $this->applicant();
        $this->seedProfile($tenant, $user);
        $headers = $this->tenantHeaders($tenant);

        $response = $this->patchJson('/api/v1/pandit/profile', [
            'tradition' => 'INVALID_SECT',
        ], $headers);

        $response->assertUnprocessable();
    }

    public function test_public_directory_filters_by_tradition_and_gotra(): void
    {
        $tenant = $this->makeTenant();
        $member = User::factory()->create();
        $this->makeMembership($tenant, $member);
        $this->actingAs($member);
        $headers = $this->tenantHeaders($tenant);

        $user1 = User::factory()->create();
        $this->makeMembership($tenant, $user1);
        $profile1 = $this->seedProfile($tenant, $user1, [
            'verification_status' => PanditProfile::VERIFICATION_VERIFIED,
            'status' => PanditProfile::STATUS_ACTIVE,
            'tradition' => VedicTradition::VAISHNAVA->value,
            'gotra' => 'VASISHTHA',
        ]);

        $user2 = User::factory()->create();
        $this->makeMembership($tenant, $user2);
        $profile2 = $this->seedProfile($tenant, $user2, [
            'verification_status' => PanditProfile::VERIFICATION_VERIFIED,
            'status' => PanditProfile::STATUS_ACTIVE,
            'tradition' => VedicTradition::SMARTHA->value,
            'gotra' => 'KASHYAPA',
        ]);

        // Filter by tradition=VAISHNAVA
        $resTradition = $this->getJson('/api/v1/pandit/profiles?tradition=VAISHNAVA', $headers);
        $resTradition->assertOk();
        $itemsTradition = $resTradition->json('data');
        $this->assertCount(1, $itemsTradition);
        $this->assertSame((string) $profile1->getKey(), $itemsTradition[0]['id']);

        // Filter by gotra=KASHYAPA
        $resGotra = $this->getJson('/api/v1/pandit/profiles?gotra=KASHYAPA', $headers);
        $resGotra->assertOk();
        $itemsGotra = $resGotra->json('data');
        $this->assertCount(1, $itemsGotra);
        $this->assertSame((string) $profile2->getKey(), $itemsGotra[0]['id']);
    }

    public function test_lists_pandit_profiles_port_resolves_taxonomy(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);

        $profile = $this->seedProfile($tenant, $user, [
            'verification_status' => PanditProfile::VERIFICATION_VERIFIED,
            'status' => PanditProfile::STATUS_ACTIVE,
            'tradition' => VedicTradition::SHAIVA->value,
            'gotra' => 'AGASTYA',
            'shakha_code' => 'KANVA',
            'travel_buffer_minutes' => 60,
            'travel_radius_km' => 40,
            'timezone' => 'Asia/Kolkata',
        ]);

        $this->resolveContext($tenant);
        try {
            /** @var ListsPanditProfiles $port */
            $port = app(ListsPanditProfiles::class);
            $data = $port->profileById((string) $profile->getKey());

            $this->assertNotNull($data);
            $this->assertSame('SHAIVA', $data['tradition'] ?? null);
            $this->assertSame('AGASTYA', $data['gotra'] ?? null);
            $this->assertSame('KANVA', $data['shakha_code'] ?? null);
            $this->assertSame(60, $data['travel_buffer_minutes'] ?? null);
            $this->assertSame(40, $data['travel_radius_km'] ?? null);
            $this->assertSame('Asia/Kolkata', $data['timezone'] ?? null);
        } finally {
            $this->clearContext();
        }
    }
}

