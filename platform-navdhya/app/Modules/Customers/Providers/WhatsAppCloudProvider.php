<?php

namespace App\Modules\Customers\Providers;

use App\Domain\Delivery\Exceptions\ProviderNotConfiguredException;
use App\Modules\Customers\Providers\Contracts\NotificationProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * WhatsApp Cloud API provider adapter (E4).
 * Communicates via Meta Graph API for template message delivery.
 * Hardened for fail-closed configuration, connect/read timeouts, and message ID integrity.
 */
final class WhatsAppCloudProvider implements NotificationProvider
{
    private string $phoneNumberId;
    private string $accessToken;
    private string $appSecret;
    private string $apiVersion;

    public function __construct(
        ?string $phoneNumberId = null,
        ?string $accessToken = null,
        ?string $appSecret = null,
        ?string $apiVersion = null
    ) {
        $this->phoneNumberId = $phoneNumberId ?? (string) config('notifications.whatsapp.phone_number_id', '');
        $this->accessToken = $accessToken ?? (string) config('notifications.whatsapp.access_token', '');
        $this->appSecret = $appSecret ?? (string) config('notifications.whatsapp.app_secret', '');
        $this->apiVersion = $apiVersion ?? (string) config('notifications.whatsapp.api_version', 'v21.0');
    }

    public function send(NotificationMessage $message): ProviderSendResult
    {
        if (empty($this->accessToken) || empty($this->phoneNumberId)) {
            throw new ProviderNotConfiguredException('whatsapp.access_token_or_phone_number_id');
        }

        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";

        // Format parameters into WhatsApp template parameters array
        $parameters = [];
        foreach ($message->variables as $val) {
            $parameters[] = ['type' => 'text', 'text' => (string) $val];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $message->recipient,
            'type' => 'template',
            'template' => [
                'name' => $message->template,
                'language' => ['code' => 'en_US'],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => $parameters,
                    ],
                ],
            ],
        ];

        if ($message->idempotencyKey !== null && $message->idempotencyKey !== '') {
            $payload['biz_opaque_callback_data'] = $message->idempotencyKey;
        }

        try {
            $response = Http::timeout(5)
                ->connectTimeout(3)
                ->withToken($this->accessToken)
                ->post($url, $payload);

            if ($response->successful()) {
                $body = $response->json();
                $msgId = (string) ($body['messages'][0]['id'] ?? '');

                if ($msgId === '') {
                    return ProviderSendResult::failure('WhatsApp API response missing messages[0].id', is_array($body) ? $body : ['raw' => $response->body()]);
                }

                return ProviderSendResult::success($msgId, is_array($body) ? $body : []);
            }

            if ($response->status() === 504) {
                return ProviderSendResult::ambiguous("WhatsApp API 504 Gateway Timeout: {$response->body()}", $response->json());
            }

            return ProviderSendResult::failure("WhatsApp API error: {$response->body()}", $response->json());
        } catch (ConnectionException $e) {
            $isTimeout = str_contains(strtolower($e->getMessage()), 'timed out') || str_contains(strtolower($e->getMessage()), 'timeout');
            if ($isTimeout) {
                return ProviderSendResult::ambiguous("WhatsApp connection timeout: {$e->getMessage()}");
            }

            return ProviderSendResult::failure("WhatsApp connection failure: {$e->getMessage()}");
        } catch (\Throwable $e) {
            return ProviderSendResult::failure($e->getMessage());
        }
    }

    public function verifyWebhook(Request $request): WebhookVerificationResult
    {
        // Meta webhook hub challenge verification (GET handshake)
        if ($request->isMethod('GET')) {
            $mode = $request->query('hub_mode');
            $token = $request->query('hub_verify_token');
            $expectedToken = config('notifications.whatsapp.verify_token');

            if ($expectedToken === null || $expectedToken === '') {
                return WebhookVerificationResult::invalid('Meta verify token unconfigured (fail-closed)');
            }

            if ($mode === 'subscribe' && $token === $expectedToken) {
                return WebhookVerificationResult::matched('handshake', 'verified');
            }

            return WebhookVerificationResult::invalid('Invalid Meta verify token');
        }

        // POST status receipt verification
        $secret = $this->appSecret !== '' ? $this->appSecret : (string) config('notifications.whatsapp.app_secret', '');
        if ($secret === '') {
            return WebhookVerificationResult::invalid('WhatsApp app secret is unconfigured (fail-closed)');
        }

        $signature = (string) $request->header('X-Hub-Signature-256', '');
        if ($signature === '') {
            return WebhookVerificationResult::invalid('Missing X-Hub-Signature-256 header');
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);
        if (! hash_equals($expected, $signature)) {
            return WebhookVerificationResult::invalid('Invalid WhatsApp HMAC signature');
        }

        $entry = $request->input('entry.0.changes.0.value.statuses.0');
        if (! is_array($entry)) {
            return WebhookVerificationResult::invalid('No status entry found in webhook payload');
        }

        $msgId = (string) ($entry['id'] ?? '');
        $statusStr = strtolower((string) ($entry['status'] ?? ''));

        $status = match ($statusStr) {
            'delivered' => 'delivered',
            'read' => 'read',
            'failed' => 'failed',
            default => 'sent',
        };

        return WebhookVerificationResult::matched($msgId, $status, $request->all());
    }
}
