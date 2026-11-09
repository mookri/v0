<?php

namespace App\Modules\Customers\Providers;

use App\Modules\Customers\Providers\Contracts\NotificationProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Msg91 SMS provider adapter (E4).
 * Supports DLT template variables, Indian telecom sender headers, and delivery webhooks.
 */
final class Msg91SmsProvider implements NotificationProvider
{
    private string $authKey;
    private string $senderId;
    private string $apiBase;
    private ?string $webhookSecret;

    public function __construct(
        ?string $authKey = null,
        ?string $senderId = null,
        ?string $apiBase = null,
        ?string $webhookSecret = null
    ) {
        $this->authKey = $authKey ?? (string) config('notifications.msg91.auth_key', '');
        $this->senderId = $senderId ?? (string) config('notifications.msg91.sender_id', 'NAVDHY');
        $this->apiBase = $apiBase ?? (string) config('notifications.msg91.api_base', 'https://api.msg91.com/api/v5');
        $this->webhookSecret = $webhookSecret ?? config('notifications.msg91.webhook_secret');
    }

    public function send(NotificationMessage $message): ProviderSendResult
    {
        if (empty($this->authKey)) {
            // Unconfigured credentials fail safely into simulated id
            return ProviderSendResult::success('msg91_sim_' . Str::random(12), ['mode' => 'simulated']);
        }

        $templateId = config("notifications.msg91.dlt_templates.{$message->template}", $message->template);

        try {
            $response = Http::withHeaders([
                'authkey' => $this->authKey,
                'content-type' => 'application/json',
            ])->post("{$this->apiBase}/flow/", [
                'template_id' => $templateId,
                'sender' => $this->senderId,
                'short_url' => '0',
                'mobiles' => $message->recipient,
                'variables' => $message->variables,
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $msgId = (string) ($body['message'] ?? $body['request_id'] ?? Str::random(16));
                return ProviderSendResult::success($msgId, $body);
            }

            return ProviderSendResult::failure("Msg91 API error: {$response->body()}", $response->json());
        } catch (\Throwable $e) {
            return ProviderSendResult::failure($e->getMessage());
        }
    }

    public function verifyWebhook(Request $request): WebhookVerificationResult
    {
        if ($this->webhookSecret !== null && $this->webhookSecret !== '') {
            $signature = (string) $request->header('X-Msg91-Signature', '');
            $expected = hash_hmac('sha256', $request->getContent(), $this->webhookSecret);
            if (! hash_equals($expected, $signature)) {
                return WebhookVerificationResult::invalid('Invalid Msg91 webhook signature');
            }
        }

        $msgId = (string) $request->input('request_id', $request->input('requestId', ''));
        $statusStr = strtolower((string) $request->input('status', ''));

        $status = match ($statusStr) {
            'delivered' => 'delivered',
            'read' => 'read',
            'failed', 'rejected' => 'failed',
            default => 'sent',
        };

        return WebhookVerificationResult::matched($msgId, $status, $request->all());
    }
}
