<?php

namespace App\Modules\Customers\Providers;

use App\Modules\Customers\Providers\Contracts\NotificationProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * In-memory test spy provider (E4).
 * Default provider in local/test/CI environments.
 */
final class FakeNotificationProvider implements NotificationProvider
{
    /** @var list<NotificationMessage> */
    public array $sentMessages = [];

    public function send(NotificationMessage $message): ProviderSendResult
    {
        $this->sentMessages[] = $message;
        $mockId = 'msg_fake_' . Str::random(16);

        return ProviderSendResult::success($mockId, ['fake' => true]);
    }

    public function verifyWebhook(Request $request): WebhookVerificationResult
    {
        $id = $request->input('message_id', 'msg_fake_123');
        $status = $request->input('status', 'delivered');

        return WebhookVerificationResult::matched($id, $status, $request->all());
    }

    public function count(): int
    {
        return count($this->sentMessages);
    }

    public function reset(): void
    {
        $this->sentMessages = [];
    }
}
