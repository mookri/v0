<?php

namespace App\Modules\Customers\Mail;

use App\Modules\Customers\NotificationEmailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Customer notification email (N3): the delivery body behind the
 * dispatcher's email leg. Subject renders per kind through
 * NotificationEmailTemplates (locale carried from CustomerSetting);
 * the body renders the notification's own title/body through the Blade
 * template — `{{ }}` escapes, so producer copy can never inject markup.
 *
 * Sent inline by the dispatch job (never queued on its own): the job's
 * try/backoff owns retries and the delivery-row claim owns idempotency.
 * Provider stays Laravel's mailer config (MAIL_* — log in dev, SMTP or
 * an API transport in production): provider-agnostic by construction.
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
        // Mailable declares $locale (view translation locale) — assign it
        // rather than redeclare; the envelope reads it too.
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
            with: [
                'title' => $this->title,
                'body' => $this->body,
                'locale' => $this->locale,
            ],
        );
    }
}
