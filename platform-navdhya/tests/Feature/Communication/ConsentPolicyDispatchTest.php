<?php

namespace Tests\Feature\Communication;

use App\Models\User;
use App\Modules\Customers\Jobs\DispatchCustomerNotification;
use App\Modules\Customers\Models\ConsentLedger;
use App\Modules\Customers\Models\NotificationDelivery;
use App\Modules\Customers\Providers\Contracts\NotificationProvider;
use App\Modules\Customers\Providers\FakeNotificationProvider;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM08-01 (Closes C-05 / Gate G-CONSENT-1):
 * Proves that consent_ledger is evaluated at dispatch,
 * and changing consent state from GRANTED to WITHDRAWN immediately suppresses messages.
 */
final class ConsentPolicyDispatchTest extends TenantIntegrationTestCase
{
    public function test_withdrawing_consent_in_ledger_immediately_suppresses_dispatch(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $fakeProvider = new FakeNotificationProvider();
        $this->app->instance(NotificationProvider::class, $fakeProvider);

        $user = User::factory()->create([
            'phone' => '+919876543210',
            'email' => 'devotee_consent@example.com',
        ]);

        // 1. Grant consent for RITUAL_REMINDERS
        ConsentLedger::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'purpose' => ConsentLedger::PURPOSE_RITUAL_REMINDERS,
            'state' => ConsentLedger::STATE_GRANTED,
            'source' => 'WEB_PORTAL',
            'policy_version' => '2026.1',
        ]);

        $job1 = new DispatchCustomerNotification(
            tenantId: $tenantId,
            userId: (string) $user->id,
            kind: 'reminder',
            title: 'Ekadashi Reminder',
            body: 'Fasting begins tomorrow morning.',
            eventId: 'evt_consent_granted_001'
        );
        $job1->handle();

        // Must be sent
        $delivery1 = NotificationDelivery::query()
            ->where('user_id', $user->id)
            ->where('kind', 'reminder')
            ->where('channel', 'sms')
            ->first();

        $this->assertNotNull($delivery1);
        $this->assertSame(NotificationDelivery::STATUS_SENT, $delivery1->status);

        // 2. Withdraw consent in ledger
        ConsentLedger::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'purpose' => ConsentLedger::PURPOSE_RITUAL_REMINDERS,
            'state' => ConsentLedger::STATE_WITHDRAWN,
            'source' => 'WEB_PORTAL',
            'policy_version' => '2026.1',
        ]);

        $job2 = new DispatchCustomerNotification(
            tenantId: $tenantId,
            userId: (string) $user->id,
            kind: 'reminder',
            title: 'Pradosh Reminder',
            body: 'Puja begins this evening.',
            eventId: 'evt_consent_withdrawn_002'
        );
        $job2->handle();

        // Must be suppressed due to missing/withdrawn consent
        $delivery2 = NotificationDelivery::query()
            ->where('user_id', $user->id)
            ->where('dedup_key', 'like', '%evt_consent_withdrawn_002%sms%')
            ->first();

        $this->assertNotNull($delivery2);
        $this->assertSame(NotificationDelivery::STATUS_SUPPRESSED, $delivery2->status);
        $this->assertStringContainsString('consent_missing_or_withdrawn', $delivery2->error);
    }
}

