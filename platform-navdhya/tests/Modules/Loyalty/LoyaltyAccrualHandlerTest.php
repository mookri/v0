<?php

declare(strict_types=1);

namespace Tests\Modules\Loyalty;

use App\Domain\Loyalty\LoyaltyLedgerService;
use App\Modules\Loyalty\Handlers\LoyaltyAccrualHandler;
use App\Modules\Loyalty\Models\LoyaltyAccount;
use App\Platform\Outbox\OutboxEvent;
use Tests\TenantIntegrationTestCase;

/**
 * Validates outbox loyalty accrual handler and ethical guardrails (T-JYRW-03).
 */
final class LoyaltyAccrualHandlerTest extends TenantIntegrationTestCase
{
    public function test_chart_completed_accrues_points_while_dosha_accrues_zero(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();
        $userId = 'usr_1009';

        $handler = new LoyaltyAccrualHandler(new LoyaltyLedgerService());

        // 1. Chart completed -> +50 points
        $chartEvent = new OutboxEvent();
        $chartEvent->forceFill([
            'tenant_id' => $tenantId,
            'aggregate_type' => 'Chart',
            'aggregate_id' => $userId,
            'event_type' => 'jyotish.chart.completed',
            'payload' => ['user_id' => $userId],
        ]);

        $handler->handle($chartEvent);

        $account = LoyaltyAccount::query()->where('user_id', $userId)->first();
        $this->assertNotNull($account);
        $this->assertSame(50, $account->points_balance);

        // 2. Dosha event -> strictly ignored / 0 points
        $doshaEvent = new OutboxEvent();
        $doshaEvent->forceFill([
            'tenant_id' => $tenantId,
            'aggregate_type' => 'Dosha',
            'aggregate_id' => $userId,
            'event_type' => 'jyotish.dosha.detected',
            'payload' => ['user_id' => $userId, 'dosha_type' => 'kaal_sarp'],
        ]);

        $handler->handle($doshaEvent);

        $account->refresh();
        $this->assertSame(50, $account->points_balance, 'Dosha events must strictly accrue 0 points');
    }
}

