<?php

namespace App\Modules\Customers\Providers;

use App\Modules\Customers\Providers\Contracts\NotificationProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * WhatsApp Cloud API provider adapter (E4).
 * Communicates via Meta Graph API for template message delivery.
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
            return ProviderSendResult::success('wa_sim_' . Str::random(16), ['mode' => 'simulated']);
        }

        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";

        // Format parameters into WhatsApp template parameters array
        $parameters = [];
        foreach ($message->variables as $val) {
            $parameters[] = ['type' => 'text', 'text' => (string) $val];
        }

        try {
            $response = Http::withToken($this->accessToken)->post($url, [
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
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $msgId = (string) ($body['messages'][0]['id'] ?? Str::random(16));
                return ProviderSendResult::success($msgId, $body);
            }

            return ProviderSendResult::failure("WhatsApp API error: {$response->body()}", $response->json());
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

            if ($mode === 'subscribe' && $token === $expectedToken) {
                return WebhookVerificationResult::matched('handshake', 'verified');
            }

            return WebhookVerificationResult::invalid('Invalid Meta verify token');
        }

        // POST status receipt verification
        if ($this->appSecret !== '') {
            $signature = (string) $request->header('X-Hub-Signature-256', '');
            $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $this->appSecret);
            if (! hash_equals($expected, $signature)) {
                return WebhookVerificationResult::invalid('Invalid WhatsApp HMAC signature');
            }
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
