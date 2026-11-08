<?php

namespace Tests\Platform\Billing;

use App\Platform\Billing\Models\PlatformInvoice;
use App\Platform\Billing\Models\PlatformSubscriptionPlan;
use App\Platform\Billing\Models\TenantSubscription;
use App\Platform\Billing\TenantSubscriptionService;
use App\Platform\Tenancy\Models\Tenant;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

final class TenantSubscriptionTest extends TenantIntegrationTestCase
{
    public function test_tenant_subscription_lifecycle_entitlements_and_central_isolation(): void
    {
        $tenant = $this->makeTenant();
        $tenantId = (string) $tenant->getKey();
        $this->resolveContext($tenant);

        try {
            // 1. Seed central subscription plans
            $growthPlan = PlatformSubscriptionPlan::query()->create([
                'code' => 'GROWTH',
                'name' => 'Growth Plan',
                'billing_interval' => 'MONTHLY',
                'price_minor' => 499900, // ₹4,999.00 / month
                'currency' => 'INR',
                'features_json' => [
                    'advanced_analytics' => true,
                    'custom_domain' => true,
                    'multi_pandit_dispatch' => true,
                    'white_label_portal' => false,
                ],
                'is_active' => true,
            ]);

            /** @var TenantSubscriptionService $service */
            $service = app(TenantSubscriptionService::class);

            // 2. Subscribe tenant to Growth plan
            $sub = $service->subscribe($tenantId, 'GROWTH');
            $this->assertSame(TenantSubscription::STATUS_ACTIVE, $sub->status);
            $this->assertSame((string) $growthPlan->getKey(), $sub->plan_id);

            // 3. Verify Entitlements
            $this->assertTrue($service->isEntitled($tenantId, 'advanced_analytics'));
            $this->assertTrue($service->isEntitled($tenantId, 'multi_pandit_dispatch'));
            $this->assertFalse($service->isEntitled($tenantId, 'white_label_portal')); // Not in Growth
            $this->assertFalse($service->isEntitled($tenantId, 'non_existent_feature'));

            // 4. Verify Central Connection Isolation:
            // Exists in central database, NOT in tenant_shared!
            $this->assertDatabaseHas('platform_subscription_plans', [
                'code' => 'GROWTH',
            ], 'central');

            $this->assertDatabaseHas('tenant_subscriptions', [
                'tenant_id' => $tenantId,
                'status' => 'ACTIVE',
            ], 'central');

            // 5. Test Past-Due Grace Period Handling:
            // Set to PAST_DUE with period end 3 days ago (within 7-day grace period)
            $sub->status = TenantSubscription::STATUS_PAST_DUE;
            $sub->current_period_end = CarbonImmutable::now('UTC')->subDays(3);
            $sub->save();

            $this->assertTrue($service->isEntitled($tenantId, 'advanced_analytics'), 'Should allow access during 7-day grace period.');

            // Set period end to 10 days ago (past 7-day grace period)
            $sub->current_period_end = CarbonImmutable::now('UTC')->subDays(10);
            $sub->save();

            $this->assertFalse($service->isEntitled($tenantId, 'advanced_analytics'), 'Should deny access after 7-day grace period.');

            // 6. Test Gapless Platform SaaS Invoice Generation
            $inv1 = $service->recordInvoice($tenantId, (string) $sub->getKey(), 499900, 89982, 'INR');
            $inv2 = $service->recordInvoice($tenantId, (string) $sub->getKey(), 499900, 89982, 'INR');

            $this->assertMatchesRegularExpression('/^NAV-SAAS-\d{4}-000001$/', $inv1->invoice_number);
            $this->assertMatchesRegularExpression('/^NAV-SAAS-\d{4}-000002$/', $inv2->invoice_number);

            $this->assertDatabaseHas('platform_invoices', [
                'id' => (string) $inv1->getKey(),
                'tenant_id' => $tenantId,
                'status' => 'PAID',
            ], 'central');
        } finally {
            $this->clearContext();
        }
    }
}
