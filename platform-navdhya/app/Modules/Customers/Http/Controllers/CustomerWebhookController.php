<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Domain\Delivery\Models\CommunicationWebhookEvent;
use App\Domain\Delivery\State\DeliveryStateMachine;
use App\Modules\Customers\Models\NotificationDelivery;
use App\Modules\Customers\Providers\Msg91SmsProvider;
use App\Modules\Customers\Providers\WhatsAppCloudProvider;
use App\Platform\Tenancy\PlatformScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public notification delivery receipt webhooks (E4 / E-CM-02).
 * Fail-closed HMAC verification, callback deduplication, and tenant-guarded state transitions.
 */
final class CustomerWebhookController
{
    public function __construct(
        private readonly Msg91SmsProvider $msg91,
        private readonly WhatsAppCloudProvider $whatsapp,
        private readonly DeliveryStateMachine $stateMachine,
    ) {
    }

    public function handleMsg91(Request $request): JsonResponse
    {
        $result = $this->msg91->verifyWebhook($request);

        if (! $result->valid) {
            return new JsonResponse(['error' => 'invalid_signature', 'detail' => $result->error], 401);
        }

        $msgId = (string) $result->providerMessageId;
        if ($msgId !== '') {
            $dedup = $this->recordAndDedup('msg91', $msgId, $result->status, $request);
            if ($dedup) {
                return new JsonResponse(['status' => 'acknowledged', 'message_id' => $msgId, 'deduplicated' => true]);
            }

            $this->updateDeliveryStatus('msg91', $msgId, $result->status);
        }

        return new JsonResponse(['status' => 'acknowledged', 'message_id' => $result->providerMessageId]);
    }

    public function handleWhatsApp(Request $request): Response
    {
        // Meta GET verification challenge
        if ($request->isMethod('GET')) {
            $result = $this->whatsapp->verifyWebhook($request);
            if ($result->valid) {
                return response($request->query('hub_challenge'), 200, ['Content-Type' => 'text/plain']);
            }

            return new JsonResponse(['error' => 'verification_failed'], 403);
        }

        // POST status receipt
        $result = $this->whatsapp->verifyWebhook($request);
        if (! $result->valid) {
            return new JsonResponse(['error' => 'invalid_signature', 'detail' => $result->error], 401);
        }

        $msgId = (string) $result->providerMessageId;
        if ($msgId !== '') {
            $dedup = $this->recordAndDedup('whatsapp_cloud', $msgId, $result->status, $request);
            if ($dedup) {
                return new JsonResponse(['status' => 'acknowledged', 'message_id' => $msgId, 'deduplicated' => true]);
            }

            $this->updateDeliveryStatus('whatsapp_cloud', $msgId, $result->status);
        }

        return new JsonResponse(['status' => 'acknowledged', 'message_id' => $result->providerMessageId]);
    }

    /**
     * Deduplicates incoming webhook events. Returns true if this event was already processed.
     */
    private function recordAndDedup(string $provider, string $providerMessageId, string $status, Request $request): bool
    {
        $occurredAt = CarbonImmutable::now();
        $payloadHash = hash('sha256', (string) $request->getContent());

        // Check for duplicate in window
        $existing = CommunicationWebhookEvent::query()
            ->where('provider', $provider)
            ->where('provider_message_id', $providerMessageId)
            ->where('event_type', $status)
            ->first();

        if ($existing !== null) {
            return true;
        }

        try {
            CommunicationWebhookEvent::query()->create([
                'provider' => $provider,
                'provider_message_id' => $providerMessageId,
                'event_type' => $status,
                'occurred_at' => $occurredAt,
                'payload_hash' => $payloadHash,
                'processed_at' => $occurredAt,
            ]);
        } catch (UniqueConstraintViolationException) {
            return true;
        }

        return false;
    }

    /**
     * Tenant-scoped state transition without bypassing global scopes (C-06).
     */
    private function updateDeliveryStatus(string $provider, string $providerMessageId, string $status): void
    {
        $context = app(\App\Platform\Tenancy\TenantContext::class);

        if ($context->isResolved()) {
            $delivery = NotificationDelivery::query()
                ->where('provider_message_id', $providerMessageId)
                ->first();
        } else {
            // Unauthenticated webhook without ambient tenant: find record via explicit PlatformScope exit, bind context, and query scoped
            $candidate = PlatformScope::run(
                'customer_webhook_tenant_lookup',
                static fn () => NotificationDelivery::query()
                    ->where('provider_message_id', $providerMessageId)
                    ->first()
            );

            if ($candidate !== null) {
                $context->set($candidate->tenant_id, null);
                $delivery = NotificationDelivery::query()
                    ->where('provider_message_id', $providerMessageId)
                    ->first();
            } else {
                $delivery = null;
            }
        }

        if ($delivery === null) {
            Log::warning("Unmatched webhook receipt for provider [{$provider}] message [{$providerMessageId}]", [
                'status' => $status,
                'telemetry_metric' => 'unmatched_receipt_count',
            ]);

            return;
        }

        try {
            $this->stateMachine->apply($delivery, $status);
        } catch (\Throwable $e) {
            Log::error("Failed to apply state transition for delivery [{$delivery->id}]: {$e->getMessage()}", [
                'provider_message_id' => $providerMessageId,
                'target_status' => $status,
            ]);
        }
    }
}
