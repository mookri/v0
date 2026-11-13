<?php

namespace App\Modules\Customers\Providers;

use App\Modules\Customers\Mail\NotificationEmail;
use App\Modules\Customers\Providers\Contracts\NotificationProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Email notification provider adapter (E4 / E-CM-06).
 * Wraps Laravel Mail with NotificationEmail template. Corrects kind-specific subject labeling (C-17).
 */
final class SmtpEmailProvider implements NotificationProvider
{
    public function send(NotificationMessage $message): ProviderSendResult
    {
        try {
            $kind = $message->template !== '' ? $message->template : 'notification';
            $title = $message->subject !== null && $message->subject !== ''
                ? $message->subject
                : ($message->variables['title'] ?? 'Navdhya Notification');

            $body = $message->body !== null && $message->body !== ''
                ? $message->body
                : ($message->variables['body'] ?? 'You have a new ritual notification.');

            $mailable = new NotificationEmail(
                kind: $kind,
                title: $title,
                body: $body,
                locale: 'en'
            );

            Mail::to($message->recipient)->send($mailable);

            $msgId = 'email_' . Str::random(16);

            return ProviderSendResult::success($msgId);
        } catch (\Throwable $e) {
            return ProviderSendResult::failure($e->getMessage());
        }
    }

    public function verifyWebhook(Request $request): WebhookVerificationResult
    {
        return WebhookVerificationResult::matched(
            (string) $request->input('message_id', Str::random(16)),
            'delivered',
            $request->all()
        );
    }
}
