<?php

namespace Tests\Modules\Finance;

use App\Modules\Finance\Domain\Exceptions\MakerCheckerViolationException;
use App\Modules\Finance\Domain\MakerCheckerPayoutService;
use App\Modules\Finance\Domain\SettlementObligationService;
use App\Modules\Finance\Models\Payout;
use App\Modules\Finance\Models\SettlementObligation;
use App\Platform\Tenancy\Models\Tenant;
use Tests\TenantIntegrationTestCase;

final class SettlementAndPayoutTest extends TenantIntegrationTestCase
{
    public function test_settlement_obligation_computes_tds_withholding(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        try {
            /** @var SettlementObligationService $service */
            $service = app(SettlementObligationService::class);

            // Gross: 100,000 (₹1,000.00), Commission: 15,000 (₹150.00)
            // Taxable base = 85,000
            // TDS @ 1% (100 bps) = 850 (₹8.50)
            // Net Payable = 85,000 - 850 = 84,150 (₹841.50)
            $obligation = $service->createObligation(
                tenantId: (string) $tenant->getKey(),
                beneficiaryType: SettlementObligation::BENEFICIARY_PANDIT,
                beneficiaryId: 'pandit_rajesh_shastri',
                bookingId: 'booking_settle_101',
                grossAmountMinor: 100000,
                commissionMinor: 15000,
                tdsRateBps: 100,
                currency: 'INR'
            );

            $this->assertSame(100000, $obligation->gross_amount_minor);
            $this->assertSame(15000, $obligation->commission_minor);
            $this->assertSame(850, $obligation->tax_withheld_minor);
            $this->assertSame(84150, $obligation->net_payable_minor);
            $this->assertSame('PENDING', $obligation->status);
        } finally {
            $this->clearContext();
        }
    }

    public function test_maker_checker_payout_enforces_dual_custody(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        try {
            /** @var MakerCheckerPayoutService $service */
            $service = app(MakerCheckerPayoutService::class);

            $makerId = 'finance_maker_user_1';
            $checkerId = 'finance_checker_user_2';

            // 1. Maker initiates payout proposal
            $payout = $service->initiatePayout(
                tenantId: (string) $tenant->getKey(),
                beneficiaryId: 'pandit_rajesh_shastri',
                amountMinor: 84150,
                currency: 'INR',
                makerId: $makerId
            );

            $this->assertSame(Payout::STATUS_PENDING_APPROVAL, $payout->status);
            $this->assertSame($makerId, $payout->maker_id);

            // 2. Maker attempts to approve their own payout -> rejected!
            $selfApprovalFailed = false;
            try {
                $service->approvePayout((string) $tenant->getKey(), (string) $payout->getKey(), $makerId);
            } catch (MakerCheckerViolationException $e) {
                $selfApprovalFailed = true;
            }
            $this->assertTrue($selfApprovalFailed, 'Expected maker self-approval to be rejected with MakerCheckerViolationException.');

            // 3. Checker approves payout -> succeeds!
            $approved = $service->approvePayout((string) $tenant->getKey(), (string) $payout->getKey(), $checkerId);

            $this->assertSame(Payout::STATUS_APPROVED, $approved->status);
            $this->assertSame($checkerId, $approved->checker_id);
            $this->assertNotNull($approved->approved_at);

            // Verify Outbox Event
            $this->assertDatabaseHas('outbox_events', [
                'tenant_id' => (string) $tenant->getKey(),
                'event_type' => 'finance.payout.approved',
            ], 'tenant_shared');
        } finally {
            $this->clearContext();
        }
    }
}

