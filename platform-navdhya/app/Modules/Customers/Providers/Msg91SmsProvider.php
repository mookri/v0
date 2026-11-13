<?php

namespace App\Modules\Customers\Providers;

use App\Domain\Delivery\Exceptions\ProviderNotConfiguredException;
use App\Modules\Customers\Providers\Contracts\NotificationProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Msg91 SMS provider adapter (E4).
 * Supports DLT template variables, Indian telecom sender headers, and delivery webhooks.
 * Hardened for fail-closed configuration, connect/read timeouts, and message ID integrity.
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
            throw new ProviderNotConfiguredException('msg91.auth_key');
        }

        $templateId = config("notifications.msg91.dlt_templates.{$message->template}", $message->template);

        $headers = [
            'authkey' => $this->authKey,
            'content-type' => 'application/json',
        ];

        if ($message->idempotencyKey !== null && $message->idempotencyKey !== '') {
            $headers['X-Idempotency-Key'] = $message->idempotencyKey;
        }

        try {
            $response = Http::timeout(5)
                ->connectTimeout(3)
                ->withHeaders($headers)
                ->post("{$this->apiBase}/flow/", [
                    'template_id' => $templateId,
                    'sender' => $this->senderId,
                    'short_url' => '0',
                    'mobiles' => $message->recipient,
                    'variables' => $message->variables,
                ]);

            if ($response->successful()) {
                $body = $response->json();
                $msgId = (string) ($body['message'] ?? $body['request_id'] ?? '');

                if ($msgId === '') {
                    return ProviderSendResult::failure('Msg91 API response missing message/request_id', is_array($body) ? $body : ['raw' => $response->body()]);
                }

                return ProviderSendResult::success($msgId, is_array($body) ? $body : []);
            }

            if ($response->status() === 504) {
                return ProviderSendResult::ambiguous("Msg91 API 504 Gateway Timeout: {$response->body()}", $response->json());
            }

            return ProviderSendResult::failure("Msg91 API error: {$response->body()}", $response->json());
        } catch (ConnectionException $e) {
            // Ambiguity check: if read timed out after connect, state may be ambiguous
            $isTimeout = str_contains(strtolower($e->getMessage()), 'timed out') || str_contains(strtolower($e->getMessage()), 'timeout');
            if ($isTimeout) {
                return ProviderSendResult::ambiguous("Msg91 connection timeout: {$e->getMessage()}");
            }

            return ProviderSendResult::failure("Msg91 connection failure: {$e->getMessage()}");
        } catch (\Throwable $e) {
            return ProviderSendResult::failure($e->getMessage());
        }
    }

    public function verifyWebhook(Request $request): WebhookVerificationResult
    {
        $secret = $this->webhookSecret ?? config('notifications.msg91.webhook_secret');
        if ($secret === null || $secret === '') {
            return WebhookVerificationResult::invalid('Msg91 webhook secret is unconfigured (fail-closed)');
        }

        $signature = (string) $request->header('X-Msg91-Signature', '');
        if ($signature === '') {
            return WebhookVerificationResult::invalid('Missing X-Msg91-Signature header');
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        if (! hash_equals($expected, $signature)) {
            return WebhookVerificationResult::invalid('Invalid Msg91 webhook signature');
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
