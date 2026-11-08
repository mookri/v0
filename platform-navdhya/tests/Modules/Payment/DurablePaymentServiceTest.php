<?php

namespace Tests\Modules\Payment;

use App\Modules\Payment\Domain\DurablePaymentService;
use App\Modules\Payment\Models\Central\ProviderEventInbox;
use App\Modules\Payment\Models\Central\ProviderOperationRoute;
use App\Modules\Payment\Models\PaymentAttempt;
use App\Modules\Payment\Models\PaymentOperation;
use App\Platform\Tenancy\Models\Tenant;
use Tests\TenantIntegrationTestCase;

final class DurablePaymentServiceTest extends TenantIntegrationTestCase
{
    public function test_five_step_protocol_happy_path_and_route_registration(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        try {
            /** @var DurablePaymentService $service */
            $service = app(DurablePaymentService::class);

            $result = $service->executeOperation(
                tenantId: (string) $tenant->getKey(),
                bookingId: 'booking_durable_1',
                operationType: PaymentOperation::TYPE_CAPTURE,
                amountMinor: 25000,
                currency: 'INR',
                providerCode: 'RAZORPAY',
                gatewayCall: function (PaymentOperation $op): array {
                    return [
                        'ok' => true,
                        'provider_operation_id' => 'pay_razorpay_9999',
                        'payload' => ['amount' => 25000],
                        'response' => ['status' => 'captured', 'id' => 'pay_razorpay_9999'],
                    ];
                }
            );

            $this->assertTrue($result['ok']);

            /** @var PaymentOperation $op */
            $op = $result['operation'];
            $this->assertSame(PaymentOperation::STATUS_SUCCEEDED, $op->status);
            $this->assertSame('pay_razorpay_9999', $op->provider_operation_id);
            $this->assertSame(25000, $op->amount_minor);

            // Verify Attempt
            /** @var PaymentAttempt $attempt */
            $attempt = $result['attempt'];
            $this->assertSame(PaymentAttempt::STATUS_SUCCEEDED, $attempt->status);
            $this->assertNotNull($attempt->duration_ms);

            // Verify Central Route Registered
            $route = $service->resolveRoute('RAZORPAY', 'pay_razorpay_9999');
            $this->assertNotNull($route);
            $this->assertSame((string) $tenant->getKey(), $route->tenant_id);

            // Verify Outbox Event
            $this->assertDatabaseHas('outbox_events', [
                'tenant_id' => (string) $tenant->getKey(),
                'event_type' => 'payment.operation.succeeded',
            ], 'tenant_shared');
        } finally {
            $this->clearContext();
        }
    }

    public function test_failure_records_attempt_and_schedules_backoff_retry(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        try {
            /** @var DurablePaymentService $service */
            $service = app(DurablePaymentService::class);

            $result = $service->executeOperation(
                tenantId: (string) $tenant->getKey(),
                bookingId: 'booking_durable_fail_1',
                operationType: PaymentOperation::TYPE_CAPTURE,
                amountMinor: 15000,
                currency: 'INR',
                providerCode: 'RAZORPAY',
                gatewayCall: function (PaymentOperation $op): array {
                    return [
                        'ok' => false,
                        'error_code' => 'BAD_REQUEST_ERROR',
                        'error_message' => 'Payment failed at bank gateway.',
                    ];
                }
            );

            $this->assertFalse($result['ok']);

            /** @var PaymentOperation $op */
            $op = $result['operation'];
            $this->assertSame(PaymentOperation::STATUS_FAILED, $op->status);
            $this->assertSame('BAD_REQUEST_ERROR', $op->error_code);
            $this->assertSame(1, $op->retry_count);
            $this->assertNotNull($op->next_retry_at);

            // Verify Outbox Event for failure
            $this->assertDatabaseHas('outbox_events', [
                'tenant_id' => (string) $tenant->getKey(),
                'event_type' => 'payment.operation.failed',
            ], 'tenant_shared');
        } finally {
            $this->clearContext();
        }
    }

    public function test_webhook_deduplication_via_central_inbox(): void
    {
        $tenant = $this->makeTenant();
        $tenantId = (string) $tenant->getKey();

        // Seed a central inbox event
        ProviderEventInbox::query()->create([
            'provider_code' => 'RAZORPAY',
            'event_id' => 'evt_duplicate_test_1',
            'event_type' => 'payment.captured',
            'raw_payload' => '{}',
            'headers_json' => [],
            'status' => ProviderEventInbox::STATUS_PROCESSED,
        ]);

        $secret = 'test_webhook_secret_key';
        config(['payments.razorpay.webhook_secret' => $secret]);

        $payload = json_encode([
            'event' => 'payment.captured',
            'event_id' => 'evt_duplicate_test_1',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_123',
                        'order_id' => 'order_123',
                        'amount' => 10000,
                        'currency' => 'INR',
                    ],
                ],
            ],
        ]);

        $signature = hash_hmac('sha256', (string) $payload, $secret);

        $response = $this->call('POST', '/api/v1/payment/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
        ], (string) $payload);

        $response->assertOk();
        $this->assertSame('ignored', $response->json('data.status'));
        $this->assertSame('replay', $response->json('data.reason'));
    }
}
