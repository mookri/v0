<?php

namespace Tests\Modules\Customers;

use App\Modules\Customers\Auth\CustomerOtpService;
use App\Modules\Customers\Models\CustomerOtpChallenge;
use App\Platform\Identity\Otp\Msg91WidgetProvider;
use App\Platform\Identity\Otp\OtpVerifyStatus;
use Carbon\CarbonImmutable;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * Verification Fabric wiring: issue() records provenance on the row
 * (provider msg91_widget / status sent / null code_hash), and verify()
 * attests through MSG91 token verification.
 */
final class CustomerOtpPortTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeMsg91();
    }

    private function service(): CustomerOtpService
    {
        return new CustomerOtpService();
    }

    public function test_issue_stamps_msg91_widget_provenance_on_the_row(): void
    {
        $tid = (string) $this->makeTenant()->getKey();

        $challenge = $this->service()->issue(
            $tid,
            CustomerOtpChallenge::PURPOSE_LOGIN,
            '+911234567890',
            CarbonImmutable::now(),
        );

        $fresh = CustomerOtpChallenge::query()->find($challenge->getKey());
        $this->assertSame(Msg91WidgetProvider::NAME, $fresh->provider);
        $this->assertSame('sent', $fresh->status);
        $this->assertNull($fresh->code_hash);
        $this->assertSame(0, (int) $fresh->resend_count);
        $this->assertNotNull($fresh->last_sent_at);
    }

    public function test_verify_attests_matching_token(): void
    {
        $tid = (string) $this->makeTenant()->getKey();
        $phone = '+911234567890';
        $now = CarbonImmutable::now();

        $challenge = $this->service()->issue(
            $tid,
            CustomerOtpChallenge::PURPOSE_LOGIN,
            $phone,
            $now,
        );

        $token = $this->msg91TokenFor($phone);

        $result = $this->service()->verify(
            $tid,
            CustomerOtpChallenge::PURPOSE_LOGIN,
            (string) $challenge->getKey(),
            $token,
            $now->addSeconds(5),
        );

        $this->assertSame(OtpVerifyStatus::Match, $result->status);
    }

    public function test_mismatch_token_returns_mismatch_status(): void
    {
        $tid = (string) $this->makeTenant()->getKey();
        $phone = '+911234567890';
        $now = CarbonImmutable::now();

        $challenge = $this->service()->issue(
            $tid,
            CustomerOtpChallenge::PURPOSE_LOGIN,
            $phone,
            $now,
        );

        $wrongToken = $this->msg91TokenFor('+919999999999');

        $result = $this->service()->verify(
            $tid,
            CustomerOtpChallenge::PURPOSE_LOGIN,
            (string) $challenge->getKey(),
            $wrongToken,
            $now->addSeconds(5),
        );

        $this->assertSame(OtpVerifyStatus::Mismatch, $result->status);
    }
}
