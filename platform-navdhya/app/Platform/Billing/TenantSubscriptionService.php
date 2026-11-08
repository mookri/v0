<?php

namespace App\Platform\Billing;

use App\Platform\Audit\AuditWriter;
use App\Platform\Billing\Models\PlatformInvoice;
use App\Platform\Billing\Models\PlatformSubscriptionPlan;
use App\Platform\Billing\Models\TenantSubscription;
use Carbon\CarbonImmutable;
use RuntimeException;

final class TenantSubscriptionService
{
    public function __construct(
        private readonly AuditWriter $audit,
    ) {
    }

    /**
     * Subscribes a tenant to a platform subscription plan.
     */
    public function subscribe(
        string $tenantId,
        string $planCode,
        ?string $providerSubId = null
    ): TenantSubscription {
        /** @var PlatformSubscriptionPlan|null $plan */
        $plan = PlatformSubscriptionPlan::query()
            ->where('code', $planCode)
            ->where('is_active', true)
            ->first();

        if ($plan === null) {
            throw new RuntimeException("Plan {$planCode} not found or inactive.");
        }

        $now = CarbonImmutable::now('UTC');
        $periodEnd = $plan->billing_interval === 'YEARLY'
            ? $now->addYear()
            : $now->addMonth();

        $subscription = TenantSubscription::query()
            ->where('tenant_id', $tenantId)
            ->first();

        if ($subscription === null) {
            $subscription = new TenantSubscription();
            $subscription->tenant_id = $tenantId;
        }

        $subscription->forceFill([
            'plan_id' => (string) $plan->getKey(),
            'status' => TenantSubscription::STATUS_ACTIVE,
            'current_period_start' => $now,
            'current_period_end' => $periodEnd,
            'cancel_at_period_end' => false,
            'provider_subscription_id' => $providerSubId,
        ]);
        $subscription->save();

        $this->audit->record(
            'platform.subscription.created',
            ['module' => 'platform_billing', 'resource_type' => 'tenant_subscription', 'resource_id' => (string) $subscription->getKey()],
            null,
            ['tenant_id' => $tenantId, 'plan' => $planCode]
        );

        return $subscription;
    }

    /**
     * Verifies if a tenant is entitled to a feature based on subscription status and grace period.
     */
    public function isEntitled(string $tenantId, string $featureKey): bool
    {
        /** @var TenantSubscription|null $subscription */
        $subscription = TenantSubscription::query()
            ->where('tenant_id', $tenantId)
            ->first();

        if ($subscription === null) {
            return false;
        }

        $now = CarbonImmutable::now('UTC');

        // Active or trialing within period
        if (in_array($subscription->status, [TenantSubscription::STATUS_ACTIVE, TenantSubscription::STATUS_TRIALING], true)) {
            if ($now > $subscription->current_period_end) {
                // Period expired
                return false;
            }
        } elseif ($subscription->status === TenantSubscription::STATUS_PAST_DUE) {
            // 7-day grace period for past due accounts
            $gracePeriodEnd = CarbonImmutable::parse($subscription->current_period_end)->addDays(7);
            if ($now > $gracePeriodEnd) {
                return false;
            }
        } else {
            return false;
        }

        /** @var PlatformSubscriptionPlan|null $plan */
        $plan = $subscription->plan;
        if ($plan === null) {
            return false;
        }

        $features = $plan->features_json ?? [];

        return (bool) ($features[$featureKey] ?? false);
    }

    /**
     * Cancels subscription either at period end or immediately.
     */
    public function cancel(string $tenantId, bool $immediately = false): TenantSubscription
    {
        /** @var TenantSubscription|null $subscription */
        $subscription = TenantSubscription::query()
            ->where('tenant_id', $tenantId)
            ->first();

        if ($subscription === null) {
            throw new RuntimeException("No subscription found for tenant {$tenantId}.");
        }

        if ($immediately) {
            $subscription->status = TenantSubscription::STATUS_CANCELLED;
        } else {
            $subscription->cancel_at_period_end = true;
        }
        $subscription->save();

        return $subscription;
    }

    /**
     * Records a gapless sequential platform SaaS invoice on central connection.
     */
    public function recordInvoice(
        string $tenantId,
        ?string $subscriptionId,
        int $subtotalMinor,
        int $taxMinor,
        string $currency = 'INR'
    ): PlatformInvoice {
        $year = (int) CarbonImmutable::now('UTC')->format('Y');

        $latestInvoice = PlatformInvoice::query()
            ->where('invoice_number', 'LIKE', "NAV-SAAS-{$year}-%")
            ->orderByDesc('invoice_number')
            ->first();

        $nextNum = 1;
        if ($latestInvoice !== null) {
            $parts = explode('-', (string) $latestInvoice->invoice_number);
            $lastIndex = (int) end($parts);
            $nextNum = $lastIndex + 1;
        }

        $invoiceNumber = sprintf('NAV-SAAS-%d-%06d', $year, $nextNum);

        $invoice = new PlatformInvoice();
        $invoice->forceFill([
            'tenant_id' => $tenantId,
            'subscription_id' => $subscriptionId,
            'invoice_number' => $invoiceNumber,
            'subtotal_minor' => $subtotalMinor,
            'tax_minor' => $taxMinor,
            'total_minor' => $subtotalMinor + $taxMinor,
            'currency' => $currency,
            'status' => PlatformInvoice::STATUS_PAID,
            'paid_at' => now(),
        ]);
        $invoice->save();

        return $invoice;
    }
}

