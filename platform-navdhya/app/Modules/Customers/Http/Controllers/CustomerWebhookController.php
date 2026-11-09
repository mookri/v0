<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Modules\Customers\Models\NotificationDelivery;
use App\Modules\Customers\Providers\Msg91SmsProvider;
use App\Modules\Customers\Providers\WhatsAppCloudProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public notification delivery receipt webhooks (E4).
 * Tenant-exempt with constant-time cryptographic HMAC verification.
 */
final class CustomerWebhookController
{
    public function __construct(
        private readonly Msg91SmsProvider $msg91,
        private readonly WhatsAppCloudProvider $whatsapp,
    ) {
    }

    public function handleMsg91(Request $request): JsonResponse
    {
        $result = $this->msg91->verifyWebhook($request);

        if (! $result->valid) {
            return new JsonResponse(['error' => 'invalid_signature', 'detail' => $result->error], 401);
        }

        if ($result->providerMessageId !== null && $result->providerMessageId !== '') {
            $this->updateDeliveryStatus($result->providerMessageId, $result->status);
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

        if ($result->providerMessageId !== null && $result->providerMessageId !== '') {
            $this->updateDeliveryStatus($result->providerMessageId, $result->status);
        }

        return new JsonResponse(['status' => 'acknowledged', 'message_id' => $result->providerMessageId]);
    }

    private function updateDeliveryStatus(string $providerMessageId, string $status): void
    {
        NotificationDelivery::query()
            ->withoutGlobalScopes()
            ->where('provider_message_id', $providerMessageId)
            ->update([
                'status' => $status,
                'updated_at' => now(),
            ]);
    }
}
