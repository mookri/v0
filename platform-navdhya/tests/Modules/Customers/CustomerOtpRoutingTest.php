<?php

namespace Tests\Modules\Customers;

use App\Modules\Customers\Auth\CustomerOtpService;
use App\Modules\Customers\Models\CustomerOtpChallenge;
use App\Platform\Identity\Otp\Msg91WidgetProvider;
use App\Platform\Identity\Otp\OtpAudienceRouter;
use App\Platform\Identity\Otp\OtpPurposeUnknownException;
use Carbon\CarbonImmutable;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * Routing contract: audience router maps customer purpose to msg91_widget
 * by default, and unknown purposes fail closed before any provider call.
 */
final class CustomerOtpRoutingTest extends TenantIntegrationTestCase
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

    public function test_default_config_routes_to_msg91_widget(): void
    {
        $this->assertSame(Msg91WidgetProvider::NAME, OtpAudienceRouter::providerName('customer'));

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
    }

    public function test_unknown_purpose_fails_closed(): void
    {
        $tid = (string) $this->makeTenant()->getKey();

        $this->expectException(OtpPurposeUnknownException::class);
        $this->service()->issue(
            $tid,
            'transfer_ownership',
            '+911234567890',
            CarbonImmutable::now(),
        );
    }
}
