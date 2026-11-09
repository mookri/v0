<?php

namespace App\Modules\Customers\Providers;

use App\Modules\Customers\Mail\NotificationEmail;
use App\Modules\Customers\Providers\Contracts\NotificationProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Email notification provider adapter (E4).
 * Wraps Laravel Mail with NotificationEmail template.
 */
final class SmtpEmailProvider implements NotificationProvider
{
    public function send(NotificationMessage $message): ProviderSendResult
    {
        try {
            $mailable = new NotificationEmail(
                kind: 'notification',
                title: $message->subject ?? 'Navdhya Notification',
                body: $message->body ?? 'You have a new ritual notification.',
                locale: 'en'
            );

            Mail::to($message->recipient)->send($mailable);

            $simId = 'email_' . Str::random(16);

            return ProviderSendResult::success($simId);
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
