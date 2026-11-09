<?php

namespace Tests\Modules\Customers;

use App\Modules\Customers\Models\ConsentLedger;
use App\Modules\Customers\Models\NotificationDelivery;
use App\Modules\Customers\Models\NotificationPreference;
use App\Modules\Customers\NotificationConsentGateService;
use App\Modules\Customers\Providers\Contracts\NotificationProvider;
use App\Modules\Customers\Providers\FakeNotificationProvider;
use App\Modules\Customers\Providers\NotificationMessage;
use Tests\TenantIntegrationTestCase;

final class NotificationProviderGatewayTest extends TenantIntegrationTestCase
{
    public function test_fake_notification_provider_dispatches_cleanly(): void
    {
        $provider = app(NotificationProvider::class);
        $this->assertInstanceOf(FakeNotificationProvider::class, $provider);

        $msg = new NotificationMessage(
            userId: 1001,
            recipient: '+919877001122',
            category: 'REMINDER',
            channel: 'WHATSAPP',
            template: 'ritual_reminder',
            variables: ['puja_name' => 'Satyanarayan Vrata']
        );

        $result = $provider->send($msg);
        $this->assertTrue($result->ok);
        $this->assertNotEmpty($result->providerMessageId);
        $this->assertSame(1, $provider->count());
    }

    public function test_consent_gate_enforces_privacy_rules(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();
        $userId = 1002;

        $gate = app(NotificationConsentGateService::class);

        // 1. Transactional is always permitted even with zero consent rows
        $txVerdict = $gate->canDispatch($userId, 'TRANSACTIONAL', 'WHATSAPP');
        $this->assertTrue($txVerdict['permitted']);
        $this->assertSame('transactional_exemption', $txVerdict['reason']);

        // 2. Reminder without consent is denied
        $remVerdict1 = $gate->canDispatch($userId, 'REMINDER', 'WHATSAPP');
        $this->assertFalse($remVerdict1['permitted']);
        $this->assertStringContainsString('consent_missing_or_withdrawn', $remVerdict1['reason']);

        // 3. Grant RITUAL_REMINDERS consent
        ConsentLedger::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'purpose' => ConsentLedger::PURPOSE_RITUAL_REMINDERS,
            'state' => ConsentLedger::STATE_GRANTED,
            'source' => 'SETTINGS_PORTAL',
            'policy_version' => '2026.1',
        ]);

        // Now permitted!
        $remVerdict2 = $gate->canDispatch($userId, 'REMINDER', 'WHATSAPP');
        $this->assertTrue($remVerdict2['permitted']);

        // 4. Disable WHATSAPP in notification preferences
        NotificationPreference::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'category' => 'REMINDER',
            'channel' => 'WHATSAPP',
            'is_enabled' => false,
        ]);

        // Channel preference now blocks it
        $remVerdict3 = $gate->canDispatch($userId, 'REMINDER', 'WHATSAPP');
        $this->assertFalse($remVerdict3['permitted']);
        $this->assertStringContainsString('disabled_by_user_preference', $remVerdict3['reason']);

        // 5. Withdraw consent
        ConsentLedger::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'purpose' => ConsentLedger::PURPOSE_RITUAL_REMINDERS,
            'state' => ConsentLedger::STATE_WITHDRAWN,
            'source' => 'SETTINGS_PORTAL',
            'policy_version' => '2026.1',
        ]);

        $remVerdict4 = $gate->canDispatch($userId, 'REMINDER', 'EMAIL');
        $this->assertFalse($remVerdict4['permitted']);
    }

    public function test_receipt_webhook_updates_delivery_status(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        // Seed a pending delivery row
        $msgId = 'msg_provider_ext_12345';
        $delivery = NotificationDelivery::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 1003,
            'kind' => 'reminder',
            'category' => 'reminder',
            'channel' => 'sms',
            'status' => NotificationDelivery::STATUS_PENDING,
            'provider_message_id' => $msgId,
        ]);

        // Post delivery receipt webhook
        $response = $this->postJson('/api/v1/customers/webhooks/msg91', [
            'request_id' => $msgId,
            'status' => 'delivered',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'acknowledged', 'message_id' => $msgId]);

        $delivery->refresh();
        $this->assertSame('delivered', $delivery->status);
    }
}

