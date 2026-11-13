<?php

namespace Tests\Feature\Communication;

use App\Modules\Customers\Mail\NotificationEmail;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM06-02:
 * Proves that NotificationEmail renders both HTML and plain-text multipart alternatives.
 */
final class MultipartEmailTest extends TenantIntegrationTestCase
{
    public function test_notification_email_registers_html_and_plain_text_parts(): void
    {
        $mailable = new NotificationEmail(
            kind: 'booking_confirmed',
            title: 'Puja Confirmed',
            body: 'Your puja is confirmed.',
            locale: 'en'
        );

        $content = $mailable->content();

        $this->assertSame('mail.notification-email', $content->view);
        $this->assertSame('mail.notification-email-text', $content->text);

        // Render both views to confirm syntax validity
        $htmlOutput = view($content->view, $content->with)->render();
        $textOutput = view($content->text, $content->with)->render();

        $this->assertNotEmpty($htmlOutput);
        $this->assertNotEmpty($textOutput);

        $this->assertStringContainsString('Puja Confirmed', $htmlOutput);
        $this->assertStringContainsString('Puja Confirmed', $textOutput);
        $this->assertStringContainsString('NAVDHYA SACRED RITUAL SERVICES', $textOutput);
    }
}

