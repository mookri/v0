<?php

namespace App\Modules\Customers\Mail;

use App\Modules\Customers\NotificationEmailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * Customer notification email (E-CM-06): the delivery body behind the
 * dispatcher's email leg. Sacred Ember brand tokens, multipart text alternative,
 * and deliverability headers.
 */
final class NotificationEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $kind,
        public readonly string $title,
        public readonly ?string $body,
        string $locale,
    ) {
        $this->locale = $locale;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: NotificationEmailTemplates::subject($this->kind, $this->locale),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.notification-email',
            text: 'mail.notification-email-text',
            with: [
                'title' => $this->title,
                'body' => $this->body,
                'locale' => $this->locale,
            ],
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Entity-Ref-ID' => $this->kind,
                'X-Auto-Response-Suppress' => 'OOF, AutoReply',
            ]
        );
    }
}
