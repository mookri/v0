<?php

namespace App\Platform\Identity\Otp\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email OTP delivery mail (Verification Fabric S48): the body behind
 * MailerEmailOtpDelivery. Sent inline (never queued on its own) by the
 * call site that owns its own failure semantics. Provider stays
 * Laravel's MAIL_* config — provider-agnostic like N3's channel.
 */
final class OtpCodeEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly int $minutes,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your verification code');
    }

    public function content(): Content
    {
        // Inline HTML string — no view file to drift. e() escapes the
        // code, so it can never inject markup however generated.
        return new Content(
            htmlString: sprintf(
                '<p>Your verification code is <strong>%s</strong>.</p>'
                ."<p>It expires in %d minutes. If you didn't request it, "
                . "ignore this email — no action is needed and you're not logged in.</p>",
                e($this->code),
                $this->minutes,
            ),
        );
    }
}
