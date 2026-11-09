<?php

namespace App\Modules\Payment\Domain;

use App\Modules\Payment\Models\Central\ProviderOperationRoute;
use App\Modules\Payment\Models\PaymentAttempt;
use App\Modules\Payment\Models\PaymentOperation;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Throwable;

final class DurablePaymentService
{
    public function __construct(
        private readonly AuditWriter $audit,
        private readonly Outbox $outbox,
    ) {
    }

    /**
     * Executes a payment operation using the 5-step durable protocol.
     *
     * @param callable(PaymentOperation): array{ok: bool, provider_operation_id?: string|null, payload?: array<string, mixed>|null, response?: array<string, mixed>|null, error_code?: string|null, error_message?: string|null} $gatewayCall
     * @return array{ok: bool, operation: PaymentOperation, attempt: PaymentAttempt}
     */
    public function executeOperation(
        string $tenantId,
        string $bookingId,
        string $operationType,
        int $amountMinor,
        string $currency,
        string $providerCode,
        callable $gatewayCall
    ): array {
        // Step 1: Pre-commit operation record in tenant DB (PENDING)
        $operation = new PaymentOperation();
        $operation->forceFill([
            'tenant_id' => $tenantId,
            'booking_id' => $bookingId,
            'operation_type' => $operationType,
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'status' => PaymentOperation::STATUS_PENDING,
            'provider_code' => $providerCode,
            'retry_count' => 0,
        ]);
        $operation->save();

        $attemptNumber = 1;
        $attempt = new PaymentAttempt();
        $attempt->forceFill([
            'tenant_id' => $tenantId,
            'payment_operation_id' => (string) $operation->getKey(),
            'attempt_number' => $attemptNumber,
            'status' => PaymentAttempt::STATUS_INITIATED,
        ]);
        $attempt->save();

        // Step 2 & 3: Move to PROCESSING and call gateway with latency timing
        $operation->status = PaymentOperation::STATUS_PROCESSING;
        $operation->save();

        $startTime = microtime(true);
        try {
            $result = $gatewayCall($operation);
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            $isSuccess = (bool) ($result['ok'] ?? false);
            $providerOpId = $result['provider_operation_id'] ?? null;

            // Step 4: Record attempt result
            $attempt->status = $isSuccess ? PaymentAttempt::STATUS_SUCCEEDED : PaymentAttempt::STATUS_FAILED;
            $attempt->provider_payload_json = $result['payload'] ?? null;
            $attempt->provider_response_json = $result['response'] ?? null;
            $attempt->duration_ms = $durationMs;
            $attempt->save();

            // Step 5: Post-commit transition
            if ($isSuccess) {
                $operation->status = PaymentOperation::STATUS_SUCCEEDED;
                $operation->provider_operation_id = $providerOpId;
                $operation->error_code = null;
                $operation->error_message = null;
                $operation->save();

                // Register central route for future webhooks/lookups
                if (is_string($providerOpId) && $providerOpId !== '') {
                    $this->registerRoute($providerCode, $providerOpId, $tenantId);
                }

                $this->outbox->append(
                    $tenantId,
                    'payment_operation',
                    (string) $operation->getKey(),
                    'payment.operation.succeeded',
                    [
                        'operation_id' => (string) $operation->getKey(),
                        'booking_id' => $bookingId,
                        'operation_type' => $operationType,
                        'amount_minor' => $amountMinor,
                        'currency' => $currency,
                        'provider_code' => $providerCode,
                        'provider_operation_id' => $providerOpId,
                    ]
                );

                $this->audit->record(
                    'payment.operation.succeeded',
                    ['module' => 'payment', 'resource_type' => 'payment_operation', 'resource_id' => (string) $operation->getKey()],
                    ['status' => PaymentOperation::STATUS_PROCESSING],
                    ['status' => PaymentOperation::STATUS_SUCCEEDED, 'amount_minor' => $amountMinor, 'currency' => $currency]
                );

                return ['ok' => true, 'operation' => $operation, 'attempt' => $attempt];
            }

            // Failure branch
            $errorCode = $result['error_code'] ?? 'gateway_error';
            $errorMessage = $result['error_message'] ?? 'Payment gateway call failed.';

            $operation->status = PaymentOperation::STATUS_FAILED;
            $operation->error_code = $errorCode;
            $operation->error_message = $errorMessage;
            $operation->retry_count = 1;
            // Exponential backoff (e.g. 5 minutes for first retry)
            $operation->next_retry_at = CarbonImmutable::now('UTC')->addMinutes(5);
            $operation->save();

            $this->outbox->append(
                $tenantId,
                'payment_operation',
                (string) $operation->getKey(),
                'payment.operation.failed',
                [
                    'operation_id' => (string) $operation->getKey(),
                    'booking_id' => $bookingId,
                    'operation_type' => $operationType,
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                ]
            );

            return ['ok' => false, 'operation' => $operation, 'attempt' => $attempt];
        } catch (Throwable $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            $attempt->status = PaymentAttempt::STATUS_FAILED;
            $attempt->duration_ms = $durationMs;
            $attempt->provider_response_json = ['exception' => $e->getMessage()];
            $attempt->save();

            $operation->status = PaymentOperation::STATUS_FAILED;
            $operation->error_code = 'exception';
            $operation->error_message = $e->getMessage();
            $operation->retry_count = 1;
            $operation->next_retry_at = CarbonImmutable::now('UTC')->addMinutes(5);
            $operation->save();

            return ['ok' => false, 'operation' => $operation, 'attempt' => $attempt];
        }
    }

    /**
     * Registers a central routing lookup mapping provider operation IDs to tenant connections.
     */
    public function registerRoute(
        string $providerCode,
        string $providerOperationId,
        string $tenantId,
        ?string $connectionName = null
    ): ProviderOperationRoute {
        $resolvedConnection = $connectionName ?? app(TenantContext::class)->connectionName() ?? 'tenant_shared';

        /** @var ProviderOperationRoute $route */
        $route = ProviderOperationRoute::query()->updateOrCreate(
            [
                'provider_code' => $providerCode,
                'provider_operation_id' => $providerOperationId,
            ],
            [
                'tenant_id' => $tenantId,
                'connection_name' => $resolvedConnection,
            ]
        );

        return $route;
    }

    /**
     * Resolves tenant routing from provider operation ID.
     */
    public function resolveRoute(string $providerCode, string $providerOperationId): ?ProviderOperationRoute
    {
        return ProviderOperationRoute::query()
            ->where('provider_code', $providerCode)
            ->where('provider_operation_id', $providerOperationId)
            ->first();
    }
}
