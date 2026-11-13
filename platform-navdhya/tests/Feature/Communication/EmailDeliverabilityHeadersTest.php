<?php

namespace Tests\Feature\Communication;

use App\Modules\Customers\Mail\NotificationEmail;
use App\Modules\Customers\Providers\NotificationMessage;
use App\Modules\Customers\Providers\SmtpEmailProvider;
use Illuminate\Support\Facades\Mail;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM06-03 (Closes C-17):
 * Validates deliverability suppression headers and proves SmtpEmailProvider
 * maps template kinds into specific email subjects rather than a generic hardcoded label.
 */
final class EmailDeliverabilityHeadersTest extends TenantIntegrationTestCase
{
    public function test_notification_email_carries_deliverability_headers(): void
    {
        $mailable = new NotificationEmail(
            kind: 'booking_confirmed',
            title: 'Puja Confirmed',
            body: 'Your puja is confirmed.',
            locale: 'en'
        );

        $headers = $mailable->headers();
        $this->assertNotNull($headers);
        $textHeaders = $headers->text ?? [];

        $this->assertSame('booking_confirmed', $textHeaders['X-Entity-Ref-ID'] ?? null);
        $this->assertSame('OOF, AutoReply', $textHeaders['X-Auto-Response-Suppress'] ?? null);
    }

    public function test_smtp_email_provider_uses_message_template_for_kind_specific_subject(): void
    {
        Mail::fake();

        $provider = new SmtpEmailProvider();

        $msg = new NotificationMessage(
            userId: 9001,
            recipient: 'devotee@example.com',
            category: 'BOOKING',
            channel: 'EMAIL',
            template: 'booking_confirmed',
            variables: ['title' => 'Ritual Scheduled', 'body' => 'Your pandit is booked.'],
            subject: 'Custom Sacred Subject'
        );

        $result = $provider->send($msg);

        $this->assertTrue($result->ok);
        $this->assertNotNull($result->providerMessageId);

        Mail::assertSent(NotificationEmail::class, function (NotificationEmail $mail) {
            return $mail->kind === 'booking_confirmed'
                && $mail->title === 'Custom Sacred Subject';
        });
    }
}

