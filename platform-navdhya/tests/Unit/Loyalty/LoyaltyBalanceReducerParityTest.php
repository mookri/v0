<?php

declare(strict_types=1);

namespace Tests\Unit\Loyalty;

use App\Domain\Loyalty\LoyaltyLedgerService;
use PHPUnit\Framework\TestCase;

/**
 * Validates exact reducer parity between PHP LoyaltyLedgerService and TS rebuildPointsBalance (T-JYRW-02).
 */
final class LoyaltyBalanceReducerParityTest extends TestCase
{
    public function test_rebuild_points_balance_matches_ts_contract(): void
    {
        $entries = [
            ['type' => 'EARN', 'points' => 100],
            ['type' => 'EARN', 'points' => 50],
            ['type' => 'REDEEM', 'points' => 30],
            ['type' => 'ADJUST', 'points' => 20],
            ['type' => 'EXPIRE', 'points' => 10],
            ['type' => 'REVERSE', 'points' => 15],
        ];

        // 100 + 50 - 30 + 20 - 10 - 15 = 115
        $balance = LoyaltyLedgerService::rebuildPointsBalance($entries);
        $this->assertSame(115, $balance);
    }
}

