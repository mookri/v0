<?php

declare(strict_types=1);

namespace App\Modules\Loyalty\Handlers;

use App\Domain\Loyalty\LoyaltyLedgerService;
use App\Platform\Outbox\OutboxEvent;

/**
 * Outbox loyalty points accrual consumer (T-JYRW-03).
 *
 * Listens to platform facts and awards points to the user's loyalty account.
 * Strict ethical constraint: ZERO points accrual for fear-based Dosha facts (doc 01 §3).
 */
final class LoyaltyAccrualHandler
{
    public function __construct(
        private readonly LoyaltyLedgerService $ledger
    ) {
    }

    public function handle(OutboxEvent $event): void
    {
        $payload = $event->payload ?? [];
        $userId = (string) ($payload['user_id'] ?? $event->aggregate_id);

        if ($userId === '') {
            return;
        }

        // Strict ethical guardrail: zero accrual for Dosha facts
        if (str_contains(strtolower($event->event_type), 'dosha') || isset($payload['dosha_type'])) {
            return;
        }

        $pointsDelta = match ($event->event_type) {
            'jyotish.chart.completed' => 50,
            'customers.reminder.fired' => 10,
            'referral.qualified' => 200,
            'puja.booking.completed' => 500,
            default => 0,
        };

        if ($pointsDelta > 0) {
            $this->ledger->appendEntry($userId, $event->event_type, $pointsDelta, [
                'outbox_event_id' => (string) $event->getKey(),
                'source' => 'outbox_accrual',
            ]);
        }
    }
}

