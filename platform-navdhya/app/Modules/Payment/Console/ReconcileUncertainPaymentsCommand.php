<?php

declare(strict_types=1);

namespace App\Modules\Payment\Console;

use App\Modules\Payment\Domain\PaymentReconciliationService;
use Illuminate\Console\Command;

/**
 * Reconciles PaymentIntent rows stuck in PAYMENT_UNKNOWN (T-AP02-02).
 */
final class ReconcileUncertainPaymentsCommand extends Command
{
    protected $signature = 'payments:reconcile-unknown {--tenant= : Scope to a specific tenant ID}';

    protected $description = 'Poll payment gateway for PAYMENT_UNKNOWN intents and reconcile bookings or enqueue refund compensation';

    public function handle(PaymentReconciliationService $reconciler): int
    {
        $tenantId = $this->option('tenant');
        $summary = $reconciler->reconcileAllUnknown(is_string($tenantId) && $tenantId !== '' ? $tenantId : null);

        $this->info(sprintf(
            'Checked=%d Confirmed=%d Compensated=%d Failed=%d Deferred=%d',
            $summary['checked'],
            $summary['confirmed'],
            $summary['compensated'],
            $summary['failed'],
            $summary['deferred'],
        ));

        return self::SUCCESS;
    }
}

