<?php

namespace Tests\Modules\Pandit;

use App\Models\User;
use App\Modules\Finance\Models\Payout;
use App\Modules\Finance\Models\SettlementObligation;
use App\Modules\Pandit\Models\PanditProfile;
use Tests\TenantIntegrationTestCase;

final class PanditEarningsLedgerTest extends TenantIntegrationTestCase
{
    public function test_earnings_endpoint_reads_subledger_obligations_with_tds_and_payouts(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->actingAs($user);
        $headers = $this->tenantHeaders($tenant);

        $this->postJson('/api/v1/pandit/apply', ['track' => 'pandit'], $headers)->assertCreated();
        $panditId = (string) PanditProfile::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', (string) $tenant->getKey())
            ->firstOrFail()
            ->getKey();

        $this->resolveContext($tenant);
        try {
            SettlementObligation::create([
                'tenant_id' => (string) $tenant->getKey(),
                'beneficiary_type' => SettlementObligation::BENEFICIARY_PANDIT,
                'beneficiary_id' => $panditId,
                'gross_amount_minor' => 500000,
                'commission_minor' => 50000,
                'tax_withheld_minor' => 50000,
                'net_payable_minor' => 400000,
                'currency' => 'INR',
                'status' => SettlementObligation::STATUS_APPROVED,
            ]);

            Payout::create([
                'tenant_id' => (string) $tenant->getKey(),
                'beneficiary_id' => $panditId,
                'amount_minor' => 250000,
                'currency' => 'INR',
                'status' => Payout::STATUS_SUCCEEDED,
                'maker_id' => 'maker-1',
                'checker_id' => 'checker-1',
                'payout_reference' => 'UTR-TEST-001',
            ]);
        } finally {
            $this->clearContext();
        }

        $res = $this->getJson('/api/v1/pandit/earnings', $headers)->assertOk();
        $res->assertJsonPath('data.source', 'subledger')
            ->assertJsonPath('data.ledger.gross_amount_minor', 500000)
            ->assertJsonPath('data.ledger.commission_minor', 50000)
            ->assertJsonPath('data.ledger.tax_withheld_minor', 50000)
            ->assertJsonPath('data.ledger.net_payable_minor', 400000)
            ->assertJsonPath('data.ledger.paid_out_minor', 250000)
            ->assertJsonPath('data.ledger.headline_net_minor', 400000)
            ->assertJsonPath('data.payouts.0.payout_reference', 'UTR-TEST-001');
    }
}

