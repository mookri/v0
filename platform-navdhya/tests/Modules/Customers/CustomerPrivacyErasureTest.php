<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Customers\Models\BuyerProfile;
use App\Modules\Customers\Models\ConsentLedger;
use App\Modules\Customers\Models\CustomerFamilyMember;
use App\Modules\Customers\Models\CustomerRitualReminder;
use App\Modules\Customers\Models\CustomerSpiritualProfile;
use App\Modules\Customers\Models\NotificationPreference;
use App\Modules\Customers\Models\ProfileFieldProvenance;
use App\Modules\Customers\Models\UserEvent;
use App\Platform\Tenancy\Models\TenantMembership;
use Tests\TenantIntegrationTestCase;

final class CustomerPrivacyErasureTest extends TenantIntegrationTestCase
{
    public function test_devotee_data_export_compiles_complete_archive(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $user = User::factory()->create(['name' => 'Devotee Sharma', 'phone' => '+919876543210']);
        $this->makeMembership($tenant, $user);
        $this->actingAs($user);
        $tenantId = (string) $tenant->getKey();
        $userId = (int) $user->id;

        // Seed profile, spiritual, family, reminder, consent, preference, and telemetry
        BuyerProfile::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'locale' => 'te',
            'status' => BuyerProfile::STATUS_ACTIVE,
        ]);

        CustomerSpiritualProfile::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'gotra' => 'Kashyapa',
            'nakshatra' => 'Rohini',
            'rashi' => 'Vrishabha',
            'tradition' => 'AMANTA',
        ]);

        CustomerFamilyMember::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'full_name' => 'Father Sharma',
            'relation' => 'FATHER',
            'data_subject_kind' => 'DECEASED',
        ]);

        CustomerRitualReminder::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => 'SHRADDHA',
            'title' => 'Pitru Shraddha',
            'anchor_rule' => ['type' => 'SHRADDHA', 'tithi' => 15, 'lunar_month' => 6],
            'advance_days' => [7, 3, 1],
            'is_active' => true,
        ]);

        ConsentLedger::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'purpose' => 'RITUAL_REMINDERS',
            'state' => 'GRANTED',
            'source' => 'SETTINGS_PORTAL',
            'policy_version' => '2026.1',
        ]);

        NotificationPreference::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'category' => 'REMINDER',
            'channel' => 'WHATSAPP',
            'is_enabled' => true,
        ]);

        UserEvent::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'event_type' => 'view_item',
            'properties' => ['item_id' => 'puja_101'],
            'source' => 'WEB_COLLECTOR',
            'purpose' => 'ANALYTICS',
            'occurred_at' => now(),
        ]);

        $this->clearContext();

        // Perform export request
        $response = $this->getJson('/api/v1/customers/me/data-export', [
            'X-Tenant-Id' => $tenantId,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.devotee.name', 'Devotee Sharma');
        $response->assertJsonPath('data.spiritual_profile.gotra', 'Kashyapa');
        $response->assertJsonPath('data.spiritual_profile.tradition', 'AMANTA');
        $this->assertCount(1, $response->json('data.family_members'));
        $this->assertSame('Father Sharma', $response->json('data.family_members.0.full_name'));
        $this->assertCount(1, $response->json('data.ritual_reminders'));
        $this->assertSame('Pitru Shraddha', $response->json('data.ritual_reminders.0.title'));
        $this->assertCount(1, $response->json('data.consent_ledger'));
        $this->assertCount(1, $response->json('data.notification_preferences'));
        $this->assertCount(1, $response->json('data.user_events'));
    }

    public function test_devotee_erasure_sweeps_pii_and_preserves_financial_exception(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->actingAs($user);
        $tenantId = (string) $tenant->getKey();
        $userId = (int) $user->id;

        // Devotee spiritual, family, reminder, provenance, preferences, user events
        $profile = BuyerProfile::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'status' => BuyerProfile::STATUS_ACTIVE,
        ]);

        CustomerSpiritualProfile::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'gotra' => 'Bharadwaja',
        ]);

        CustomerFamilyMember::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'full_name' => 'Ancestral Relative',
            'relation' => 'GRANDFATHER',
            'data_subject_kind' => 'DECEASED',
        ]);

        CustomerRitualReminder::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => 'SHRADDHA',
            'title' => 'Shraddha Reminder',
            'anchor_rule' => ['type' => 'SHRADDHA'],
            'advance_days' => [7],
            'is_active' => true,
        ]);

        NotificationPreference::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'category' => 'REMINDER',
            'channel' => 'EMAIL',
            'is_enabled' => true,
        ]);

        ProfileFieldProvenance::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'field_name' => 'gotra',
            'source' => 'USER_ENTERED',
            'confidence' => 100,
        ]);

        UserEvent::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'event_type' => 'page_view',
            'properties' => ['path' => '/home'],
            'source' => 'WEB_COLLECTOR',
            'purpose' => 'ANALYTICS',
            'occurred_at' => now(),
        ]);

        $this->clearContext();

        // Perform erasure request
        $response = $this->postJson('/api/v1/customers/me/erasure', [
            'reason' => 'user_requested_account_closure',
        ], ['X-Tenant-Id' => $tenantId]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'accepted');
        $response->assertJsonPath('financial_exception.legal_basis', 'statutory_accounting_and_tax_compliance');

        $this->resolveContext($tenant);

        // 1. Devotee Spiritual Profile soft-deleted
        $this->assertNull(CustomerSpiritualProfile::query()->where('user_id', $userId)->first());
        $this->assertNotNull(CustomerSpiritualProfile::withTrashed()->where('user_id', $userId)->first());

        // 2. Family member soft-deleted
        $this->assertNull(CustomerFamilyMember::query()->where('user_id', $userId)->first());
        $this->assertNotNull(CustomerFamilyMember::withTrashed()->where('user_id', $userId)->first());

        // 3. Ritual reminder hard-deleted (dispatches ceased)
        $this->assertSame(0, CustomerRitualReminder::query()->where('user_id', $userId)->count());

        // 4. Preferences & provenance hard-deleted
        $this->assertSame(0, NotificationPreference::query()->where('user_id', $userId)->count());
        $this->assertSame(0, ProfileFieldProvenance::query()->where('user_id', $userId)->count());

        // 5. User telemetry pseudonymized (user_id nullified)
        $this->assertSame(0, UserEvent::query()->where('user_id', $userId)->count());
        $this->assertSame(1, UserEvent::query()->whereNull('user_id')->count());

        // 6. BuyerProfile deactivated & soft-deleted
        $this->assertNull(BuyerProfile::query()->where('user_id', $userId)->first());
        $deletedProfile = BuyerProfile::withTrashed()->where('user_id', $userId)->first();
        $this->assertNotNull($deletedProfile);
        $this->assertSame(BuyerProfile::STATUS_DEACTIVATED, $deletedProfile->status);

        // 7. TenantMembership suspended
        $membership = TenantMembership::query()->withoutGlobalScopes()->where('user_id', $userId)->first();
        $this->assertNotNull($membership);
        $this->assertSame(TenantMembership::STATE_SUSPENDED, $membership->state);

        // 8. Consent ledger has TOMBSTONE record
        $tombstone = ConsentLedger::query()->where('user_id', $userId)->orderByDesc('id')->first();
        $this->assertNotNull($tombstone);
        $this->assertSame('ALL', $tombstone->purpose);
        $this->assertSame(ConsentLedger::STATE_WITHDRAWN, $tombstone->state);
        $this->assertSame('ERASURE_REQUEST', $tombstone->source);
    }

    public function test_spiritual_family_reminders_and_consent_crud(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->actingAs($user);
        $tenantId = (string) $tenant->getKey();
        $this->clearContext();

        // 1. Update & Show Spiritual Profile
        $resSpiritual = $this->putJson('/api/v1/customers/spiritual', [
            'gotra' => 'Vashistha',
            'nakshatra' => 'Ashwini',
            'rashi' => 'Mesha',
            'kuladevata_name' => 'Sri Venkatachalapathi',
            'tradition' => 'PURNIMANTA',
        ], ['X-Tenant-Id' => $tenantId]);

        $resSpiritual->assertStatus(200);
        $resSpiritual->assertJsonPath('data.gotra', 'Vashistha');

        $getSpiritual = $this->getJson('/api/v1/customers/spiritual', ['X-Tenant-Id' => $tenantId]);
        $getSpiritual->assertStatus(200);
        $getSpiritual->assertJsonPath('data.gotra', 'Vashistha');
        $getSpiritual->assertJsonPath('data.tradition', 'PURNIMANTA');

        // 2. Family Member Store & List
        $resFamily = $this->postJson('/api/v1/customers/family', [
            'full_name' => 'Ananya Sharma',
            'relation' => 'DAUGHTER',
            'nakshatra' => 'Rohini',
            'rashi' => 'Vrishabha',
            'data_subject_kind' => 'SELF',
        ], ['X-Tenant-Id' => $tenantId]);

        $resFamily->assertStatus(201);
        $familyId = $resFamily->json('data.id');

        $getFamily = $this->getJson('/api/v1/customers/family', ['X-Tenant-Id' => $tenantId]);
        $getFamily->assertStatus(200);
        $this->assertCount(1, $getFamily->json('data'));

        // 3. Ritual Reminder Store & List with calculated occurrence
        $resReminder = $this->postJson('/api/v1/customers/reminders', [
            'family_member_id' => $familyId,
            'type' => 'LUNAR_BIRTHDAY',
            'title' => "Ananya's Janma Tithi",
            'anchor_rule' => [
                'type' => 'LUNAR_BIRTHDAY',
                'lunar_month' => 2, // Vaisakha
                'paksha' => 'SHUKLA',
                'tithi' => 3, // Akshaya Tritiya
                'tradition' => 'AMANTA',
            ],
            'advance_days' => [7, 3, 1],
        ], ['X-Tenant-Id' => $tenantId]);

        $resReminder->assertStatus(201);
        $this->assertNotNull($resReminder->json('data.next_occurrence_at'));

        $getReminders = $this->getJson('/api/v1/customers/reminders', ['X-Tenant-Id' => $tenantId]);
        $getReminders->assertStatus(200);
        $this->assertCount(1, $getReminders->json('data'));

        // 4. Consent Update & Get
        $resConsent = $this->postJson('/api/v1/customers/consent', [
            'purpose' => 'RITUAL_REMINDERS',
            'state' => 'GRANTED',
        ], ['X-Tenant-Id' => $tenantId]);

        $resConsent->assertStatus(200);
        $resConsent->assertJsonPath('data.state', 'GRANTED');

        $getConsent = $this->getJson('/api/v1/customers/consent', ['X-Tenant-Id' => $tenantId]);
        $getConsent->assertStatus(200);
        $getConsent->assertJsonPath('data.RITUAL_REMINDERS.state', 'GRANTED');

        // 5. Preferences Update & Get
        $resPref = $this->patchJson('/api/v1/customers/preferences', [
            'category' => 'REMINDER',
            'channel' => 'WHATSAPP',
            'is_enabled' => true,
        ], ['X-Tenant-Id' => $tenantId]);

        $resPref->assertStatus(200);
        $resPref->assertJsonPath('data.is_enabled', true);

        $getPref = $this->getJson('/api/v1/customers/preferences', ['X-Tenant-Id' => $tenantId]);
        $getPref->assertStatus(200);
        $this->assertCount(1, $getPref->json('data'));
    }
}
