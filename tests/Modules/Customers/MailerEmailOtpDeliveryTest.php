<?php

namespace Tests\Modules\Customers;

use App\Platform\Identity\Otp\EmailOtpDelivery;
use App\Platform\Identity\Otp\EmailOtpDeliveryProvider;
use App\Platform\Identity\Otp\MailerEmailOtpDelivery;
use App\Platform\Identity\Otp\Mail\OtpCodeEmail;
use App\Platform\Identity\Otp\NullEmailOtpDelivery;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * S48 email channel: MailerEmailOtpDelivery sends the code to the
 * address through Laravel's mailer (Mail::fake assertion — the code
 * exists only in the mail body, never in logs), and the container
 * binding follows config with the silent null as default.
 */
final class MailerEmailOtpDeliveryTest extends TestCase
{
    public function test_deliver_sends_the_code_to_the_address(): void
    {
        Mail::fake();

        (new MailerEmailOtpDelivery())->deliver(new EmailOtpDelivery(
            to: 'user@example.com',
            code: '123456',
            challengeId: '01TEST',
            purpose: 'verify_email',
            expiresAt: gmdate('Y-m-d H:i:s', time() + 600),
        ));

        Mail::assertSent(OtpCodeEmail::class, function (OtpCodeEmail $mail): bool {
            return $mail->hasTo('user@example.com')
                && $mail->envelope()->subject === 'Your verification code'
                && str_contains($mail->render(), '123456');
        });
    }

    public function test_email_delivery_binding_defaults_to_null(): void
    {
        $this->assertInstanceOf(NullEmailOtpDelivery::class, $this->app->make(EmailOtpDeliveryProvider::class));
    }

    public function test_mailer_channel_selects_the_mailer_provider(): void
    {
        config()->set('otp-providers.email_delivery', 'mailer');
        $this->app->forgetInstance(EmailOtpDeliveryProvider::class);

        $this->assertInstanceOf(MailerEmailOtpDelivery::class, $this->app->make(EmailOtpDeliveryProvider::class));
        $this->assertSame('mailer', $this->app->make(EmailOtpDeliveryProvider::class)->name());
    }
}
