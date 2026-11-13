<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Customers\Domain\Enums\GotraEnum;
use App\Modules\Customers\Domain\Enums\NakshatraEnum;
use App\Modules\Customers\Domain\Enums\RashiEnum;
use App\Modules\Customers\Jobs\ProcessCustomerErasureJob;
use App\Modules\Customers\Models\CustomerFamilyMember;
use App\Modules\Customers\Models\CustomerSpiritualProfile;
use Tests\TenantIntegrationTestCase;

final class RitualIdentityValidationTest extends TenantIntegrationTestCase
{
    public function test_rejects_typo_nakshatra_with_422(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->clearContext();

        $response = $this->actingAs($user)
            ->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->putJson('/api/v1/customers/spiritual', [
                'nakshatra' => 'shravna', // Typo! Must be SHRAVANA
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_rejects_invalid_gotra_with_422(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->clearContext();

        $response = $this->actingAs($user)
            ->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->putJson('/api/v1/customers/spiritual', [
                'gotra' => 'random_gotra',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_rejects_invalid_rashi_with_422(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->clearContext();

        $response = $this->actingAs($user)
            ->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->putJson('/api/v1/customers/spiritual', [
                'rashi' => 'capricorn', // English/invalid! Must be MAKARA
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_rejects_invalid_timezone_with_422(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->clearContext();

        $response = $this->actingAs($user)
            ->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->putJson('/api/v1/customers/spiritual', [
                'birth_timezone' => 'Mars/Phobos',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_accepts_canonical_spiritual_attributes(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->clearContext();

        $response = $this->actingAs($user)
            ->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->putJson('/api/v1/customers/spiritual', [
                'gotra' => 'KASHYAPA',
                'nakshatra' => 'ROHINI',
                'rashi' => 'VRISHABHA',
                'birth_timezone' => 'Asia/Kolkata',
                'tradition' => 'PURNIMANTA',
            ]);

        $response->assertStatus(200);

        $this->resolveContext($tenant);
        $profile = CustomerSpiritualProfile::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($profile);
        $this->assertSame('KASHYAPA', $profile->gotra);
        $this->assertSame('ROHINI', $profile->nakshatra);
        $this->assertSame('VRISHABHA', $profile->rashi);
    }

    public function test_devotee_erasure_systematically_wipes_spiritual_profile_and_family(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $tenantId = (string) $tenant->getKey();
        $userId = (int) $user->id;

        CustomerSpiritualProfile::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'gotra' => 'KASHYAPA',
            'nakshatra' => 'ROHINI',
            'rashi' => 'VRISHABHA',
        ]);

        CustomerFamilyMember::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'full_name' => 'Ancestor One',
            'relation' => 'FATHER',
            'data_subject_kind' => 'DECEASED',
        ]);

        $this->assertNotNull(CustomerSpiritualProfile::query()->where('user_id', $userId)->first());
        $this->assertNotNull(CustomerFamilyMember::query()->where('user_id', $userId)->first());

        // Execute erasure job
        ProcessCustomerErasureJob::dispatchSync($tenantId, $userId, 'devotee_privacy_request');

        $this->resolveContext($tenant);

        // Both tables are soft-deleted
        $this->assertNull(CustomerSpiritualProfile::query()->where('user_id', $userId)->first());
        $this->assertNotNull(CustomerSpiritualProfile::withTrashed()->where('user_id', $userId)->first());

        $this->assertNull(CustomerFamilyMember::query()->where('user_id', $userId)->first());
        $this->assertNotNull(CustomerFamilyMember::withTrashed()->where('user_id', $userId)->first());
    }
}
