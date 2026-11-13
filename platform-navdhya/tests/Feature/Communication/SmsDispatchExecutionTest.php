<?php

namespace Tests\Feature\Communication;

use App\Modules\Customers\Jobs\DispatchCustomerNotification;
use App\Modules\Customers\Models\NotificationDelivery;
use App\Modules\Customers\Providers\Contracts\NotificationProvider;
use App\Modules\Customers\Providers\FakeNotificationProvider;
use App\Models\User;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM05-01 (Closes C-04):
 * Proves that SMS is actually dispatched and tracked via deliverSms(),
 * eliminating the "routed-but-undelivered" defect.
 */
final class SmsDispatchExecutionTest extends TenantIntegrationTestCase
{
    public function test_deliver_sms_executes_and_creates_sent_delivery_row(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        // Bind fake provider to spy on SMS send
        $fakeProvider = new FakeNotificationProvider();
        $this->app->instance(NotificationProvider::class, $fakeProvider);

        // Create user with phone
        $user = User::factory()->create([
            'phone' => '+919876543210',
            'email' => 'devotee@example.com',
        ]);

        $eventId = 'evt_sms_dispatch_001';

        $job = new DispatchCustomerNotification(
            tenantId: $tenantId,
            userId: (string) $user->id,
            kind: 'booking_confirmed',
            title: 'Puja Confirmed',
            body: 'Your puja has been scheduled.',
            eventId: $eventId
        );

        $job->handle();

        // Assert provider received the SMS message
        $this->assertGreaterThanOrEqual(1, $fakeProvider->count());

        // Assert communication_deliveries has a SENT row for SMS
        $smsDelivery = NotificationDelivery::query()
            ->where('user_id', $user->id)
            ->where('channel', 'sms')
            ->first();

        $this->assertNotNull($smsDelivery);
        $this->assertSame(NotificationDelivery::STATUS_SENT, $smsDelivery->status);
        $this->assertNotEmpty($smsDelivery->provider_message_id);
        $this->assertNotNull($smsDelivery->sent_at);
    }

    public function test_deliver_sms_suppresses_when_no_phone(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $fakeProvider = new FakeNotificationProvider();
        $this->app->instance(NotificationProvider::class, $fakeProvider);

        // Create user without phone
        $user = User::factory()->create([
            'phone' => null,
            'email' => 'devotee_nophone@example.com',
        ]);

        $job = new DispatchCustomerNotification(
            tenantId: $tenantId,
            userId: (string) $user->id,
            kind: 'booking_confirmed',
            title: 'Puja Confirmed',
            body: 'Your puja has been scheduled.',
            eventId: 'evt_sms_nophone_002'
        );

        $job->handle();

        // Assert suppressed row created
        $smsDelivery = NotificationDelivery::query()
            ->where('user_id', $user->id)
            ->where('channel', 'sms')
            ->first();

        $this->assertNotNull($smsDelivery);
        $this->assertSame(NotificationDelivery::STATUS_SUPPRESSED, $smsDelivery->status);
        $this->assertSame('no_phone', $smsDelivery->error);
    }
}
