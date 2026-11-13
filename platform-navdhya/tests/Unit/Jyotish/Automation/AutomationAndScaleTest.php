<?php

declare(strict_types=1);

namespace Tests\Unit\Jyotish\Automation;

use App\Domain\Jyotish\Support\CanonicalHmacCache;
use App\Domain\Loyalty\LoyaltyLedgerService;
use Tests\TenantIntegrationTestCase;

final class AutomationAndScaleTest extends TenantIntegrationTestCase
{
    public function test_loyalty_ledger_cryptographic_hash_chain_and_reconciliation(): void
    {
        $service = new LoyaltyLedgerService();
        $userId = 'usr_test_devotee_' . bin2hex(random_bytes(4));

        // Entry 1: Birth profile Kundli completed (+50 points)
        $e1 = $service->appendEntry($userId, 'jyotish.chart.completed', 50, ['chart_type' => 'D1']);
        $this->assertSame(50, $e1['new_balance']);
        $this->assertNotEmpty($e1['entry_hash']);

        // Entry 2: Puja booking completed (+200 points)
        $e2 = $service->appendEntry($userId, 'puja.booking.completed', 200, ['puja_code' => 'RUDRA_ABHISHEK']);
        $this->assertSame(250, $e2['new_balance']);

        // Entry 3: Redemptions (-100 points)
        $e3 = $service->appendEntry($userId, 'loyalty.points.redeemed', -100, ['reward_code' => 'DISCOUNT_VOUCHER']);
        $this->assertSame(150, $e3['new_balance']);

        // Verify cryptographic hash integrity and balance reconciliation
        $audit = $service->verifyAndReconcile($e1['account_id']);
        $this->assertTrue($audit['is_valid'], 'Cryptographic SHA-256 hash chain must be strictly valid.');
        $this->assertSame(150, $audit['reconciled_balance']);
        $this->assertSame(3, $audit['total_entries']);
    }

    public function test_loyalty_ledger_rejects_fear_based_dosha_points_accrual(): void
    {
        $service = new LoyaltyLedgerService();
        $userId = 'usr_dosha_test_' . bin2hex(random_bytes(4));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Points accrual is strictly prohibited for fear-based Dosha facts.');

        $service->appendEntry($userId, 'jyotish.kaal_sarp_dosha.detected', 500);
    }

    public function test_canonical_hmac_cache_key_generation_is_order_invariant(): void
    {
        $paramA = ['latitude' => 28.6139, 'longitude' => 77.2090, 'date' => '2026-11-13'];
        $paramB = ['date' => '2026-11-13', 'longitude' => 77.2090, 'latitude' => 28.6139];

        $keyA = CanonicalHmacCache::generateKey('panchang', $paramA);
        $keyB = CanonicalHmacCache::generateKey('panchang', $paramB);

        $this->assertSame($keyA, $keyB, 'Canonical HMAC key must be deterministic and key-order invariant.');
    }
}
