<?php

namespace Tests\Modules\Customers;

use App\Modules\Customers\Models\ConsentLedger;
use App\Modules\Customers\Models\CustomerFamilyMember;
use App\Modules\Customers\Models\CustomerInsight;
use App\Modules\Customers\Models\CustomerRitualReminder;
use App\Modules\Customers\Models\CustomerSpiritualProfile;
use App\Modules\Customers\Models\NotificationPreference;
use App\Modules\Customers\Models\ProfileFieldProvenance;
use App\Modules\Customers\Models\UserEvent;
use App\Modules\Customers\PreferencesReader;
use Tests\TenantIntegrationTestCase;

final class CustomerSpiritualSchemaTest extends TenantIntegrationTestCase
{
    public function test_customer_spiritual_and_telemetry_schema_models(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();
        $userId = 10099;

        // 1. Spiritual Profile
        $profile = CustomerSpiritualProfile::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'gotra' => 'BHARADVAJA',
            'nakshatra' => 'ROHINI',
            'rashi' => 'VRISHABHA',
            'kuladevata_name' => 'Sri Venkatachalapathi',
            'birth_date' => '1990-05-15',
            'tradition' => CustomerSpiritualProfile::TRADITION_PURNIMANTA,
        ]);
        $this->assertNotNull($profile->id);
        $this->assertSame(CustomerSpiritualProfile::TRADITION_PURNIMANTA, $profile->tradition);

        // 2. Family Member (Deceased Ancestor for Shraddha)
        $member = CustomerFamilyMember::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'full_name' => 'Late Sri Ramachandra',
            'relation' => 'FATHER',
            'death_date' => '2020-10-24',
            'data_subject_kind' => CustomerFamilyMember::KIND_DECEASED,
        ]);
        $this->assertTrue($member->isDeceased());

        // 3. Ritual Reminder
        $reminder = CustomerRitualReminder::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'family_member_id' => (string) $member->getKey(),
            'type' => CustomerRitualReminder::TYPE_SHRADDHA,
            'title' => 'Pitru Shraddha Ceremony',
            'anchor_rule' => [
                'tithi' => 11,
                'paksha' => 'KRISHNA',
                'masa' => 'ASHVINA',
                'tradition' => 'PURNIMANTA',
            ],
            'advance_days' => [7, 3, 1, 0],
            'next_occurrence_at' => now()->addDays(14),
            'is_active' => true,
        ]);
        $this->assertSame(11, $reminder->anchor_rule['tithi']);
        $this->assertContains(7, $reminder->advance_days);

        // 4. Consent Ledger (Append-Only)
        $consent = ConsentLedger::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'purpose' => ConsentLedger::PURPOSE_RITUAL_REMINDERS,
            'state' => ConsentLedger::STATE_GRANTED,
            'source' => 'SETTINGS_PORTAL',
            'policy_version' => '2026.1',
        ]);
        $this->assertSame(ConsentLedger::STATE_GRANTED, $consent->state);

        // 5. Notification Preferences & Reader
        NotificationPreference::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'category' => NotificationPreference::CATEGORY_REMINDER,
            'channel' => NotificationPreference::CHANNEL_WHATSAPP,
            'is_enabled' => true,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '06:00',
        ]);

        $reader = app(PreferencesReader::class);
        $this->assertTrue($reader->isChannelEnabled($userId, NotificationPreference::CATEGORY_REMINDER, NotificationPreference::CHANNEL_WHATSAPP));
        $quiet = $reader->quietHours($userId);
        $this->assertSame('22:00', $quiet['start']);
        $this->assertSame('06:00', $quiet['end']);

        // 6. User Event Telemetry (Max 2KB guard)
        $event = UserEvent::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'event_type' => 'reminder_viewed',
            'properties' => ['reminder_id' => (string) $reminder->getKey()],
            'source' => UserEvent::SOURCE_API,
            'purpose' => 'ANALYTICS',
            'occurred_at' => now(),
        ]);
        $this->assertNotNull($event->id);

        // 7. Field Provenance
        $prov = ProfileFieldProvenance::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'field_name' => 'gotra',
            'source' => ProfileFieldProvenance::SOURCE_USER_ENTERED,
            'confidence' => 100,
        ]);
        $this->assertSame(100, $prov->confidence);

        // 8. Customer Insights
        $insight = CustomerInsight::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'booking_cadence_days' => 45,
            'category_affinity' => ['homa' => 3, 'puja' => 5],
            'reminder_engagement_rate' => 95,
        ]);
        $this->assertSame(45, $insight->booking_cadence_days);
    }
}

