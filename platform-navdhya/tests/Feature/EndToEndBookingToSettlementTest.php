<?php

namespace Tests\Feature;

use App\Modules\Booking\Application\CreateReservationDraftUseCase;
use App\Modules\Booking\Application\InitiateCheckoutHoldUseCase;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\ResourceAllocation;
use App\Modules\Booking\Models\ResourceCalendar;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Catalog\Models\CatalogPrice;
use App\Modules\Pandit\Models\PanditProfile;
use App\Modules\Pandit\Models\PanditServiceEligibility;
use App\Modules\Finance\Domain\Exceptions\MakerCheckerViolationException;
use App\Modules\Finance\Domain\FinanceDocumentService;
use App\Modules\Finance\Domain\MakerCheckerPayoutService;
use App\Modules\Finance\Models\FinanceDocument;
use App\Modules\Finance\Models\LedgerEntry;
use App\Modules\Finance\Models\LedgerJournal;
use App\Modules\Finance\Models\Payout;
use App\Modules\Finance\Models\SettlementObligation;
use App\Modules\Payment\Models\Central\ProviderOperationRoute;
use App\Modules\Payment\Models\PaymentIntent;
use App\Platform\Billing\Models\PlatformSubscriptionPlan;
use App\Platform\Billing\Models\TenantSubscription;
use App\Platform\Billing\TenantSubscriptionService;
use App\Modules\Tax\Domain\CommercialQuoteService;
use App\Modules\Tax\Models\CommercialProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

final class EndToEndBookingToSettlementTest extends TenantIntegrationTestCase
{
    private string $tenantId;

    protected function setUp(): void
    {
        parent::setUp();
        config(['payments.razorpay.webhook_secret' => 'e2e_webhook_secret_key']);
    }

    public function test_complete_end_to_end_booking_tax_quote_checkout_payment_finance_and_payout_lifecycle(): void
    {
        $tenant = $this->makeTenant();
        $this->tenantId = (string) $tenant->getKey();
        $this->resolveContext($tenant);

        // -------------------------------------------------------------
        // Step 1: Commercial Profile & Catalog Item Setup
        // -------------------------------------------------------------
        CommercialProfile::query()->create([
            'tenant_id' => $this->tenantId,
            'selling_model' => CommercialProfile::MODEL_PRINCIPAL,
            'default_currency' => 'INR',
            'commission_rate_bps' => 1500, // 15%
            'platform_fee_minor' => 5000,  // ₹50.00
        ]);

        CatalogItem::query()->create([
            'sku' => 'RITUAL-GANESH-PUJA',
            'name' => 'Ganesh Puja Sacred Ritual',
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        CatalogPrice::query()->create([
            'sku' => 'RITUAL-GANESH-PUJA',
            'currency' => 'INR',
            'market' => 'in',
            'price_minor' => 100000, // ₹1,000.00
            'effective_from' => now()->subDay(),
        ]);

        // -------------------------------------------------------------
        // Step 2: Authoritative Commercial Quote Computation (Tax & Invariants)
        // -------------------------------------------------------------
        $quoteService = app(CommercialQuoteService::class);
        $quote = $quoteService->createQuote([
            'tenant_id' => $this->tenantId,
            'currency' => 'INR',
            'jurisdiction' => 'IN',
            'supplier_state' => 'MH',
            'customer_state' => 'MH', // Intra-state (18% GST: 9% CGST + 9% SGST)
            'lines' => [
                [
                    'sku' => 'RITUAL-GANESH-PUJA',
                    'quantity' => 1,
                    'discount_minor' => 0,
                ],
            ],
        ], CarbonImmutable::now('UTC'));

        $this->assertTrue($quote['ok']);
        $this->assertSame(100000, $quote['summary']['subtotal_minor']);
        $this->assertSame(18000, $quote['summary']['tax_total_minor']); // 18% of ₹1,000 = ₹180.00
        $this->assertSame(5000, $quote['summary']['platform_fee_minor']); // ₹50.00
        $this->assertSame(123000, $quote['summary']['total_minor']); // 100,000 + 18,000 + 5,000 = 123,000 minor units (₹1,230.00)
        $this->assertNotEmpty($quote['quote_hash']);

        // Invariant: sum(lines.total) + platform_fee == orderTotal
        $lineTotal = $quote['lines'][0]['total_minor'];
        $this->assertSame($quote['summary']['total_minor'], $lineTotal + $quote['summary']['platform_fee_minor']);

        // -------------------------------------------------------------
        // Step 3: Draft Booking & Concurrency Slot Locking
        // -------------------------------------------------------------
        PanditProfile::factory()->create([
            'id' => 'pandit_e2e_001',
            'tenant_id' => $this->tenantId,
            'verification_status' => PanditProfile::VERIFICATION_VERIFIED,
            'status' => PanditProfile::STATUS_ACTIVE,
        ]);

        PanditServiceEligibility::query()->create([
            'tenant_id' => $this->tenantId,
            'pandit_id' => 'pandit_e2e_001',
            'service_sku' => 'RITUAL-GANESH-PUJA',
            'is_qualified' => true,
            'qualification_level' => 'PRIMARY',
        ]);

        $calendar = new ResourceCalendar();
        $calendar->forceFill([
            'tenant_id' => $this->tenantId,
            'resource_type' => 'PANDIT',
            'resource_id' => 'pandit_e2e_001',
            'timezone' => 'Asia/Kolkata',
            'version' => 1,
        ]);
        $calendar->save();

        $createDraftUseCase = app(CreateReservationDraftUseCase::class);
        $scheduledAt = CarbonImmutable::now('UTC')->addDays(3);
        $booking = $createDraftUseCase->execute(
            $this->tenantId,
            '1001',
            'idemp_draft_' . Str::random(8),
            [
                'service_sku' => 'RITUAL-GANESH-PUJA',
                'scheduled_at' => $scheduledAt->toIso8601String(),
                'duration_minutes' => 120,
                'address_snapshot' => ['line1' => '123 Temple Road', 'city' => 'Bengaluru', 'state' => 'KA', 'country' => 'IN'],
                'selected_pandit_id' => 'pandit_e2e_001',
            ]
        );

        $this->assertSame(BookingLifecycle::STATUS_DRAFT, $booking->status);
        $this->assertNotNull($booking->current_revision_id);

        // Initiate Checkout Hold
        $initiateHoldUseCase = app(InitiateCheckoutHoldUseCase::class);
        $heldBooking = $initiateHoldUseCase->execute(
            $this->tenantId,
            (string) $booking->getKey(),
            'user:1001',
            15 // 15 mins
        );

        $this->assertSame(BookingLifecycle::STATUS_PENDING_PAYMENT, $heldBooking->status);
        $this->assertNotNull($heldBooking->checkout_expires_at);

        // Verify allocation is in HELD state
        $allocation = ResourceAllocation::query()
            ->where('tenant_id', $this->tenantId)
            ->where('booking_id', (string) $booking->getKey())
            ->first();
        $this->assertNotNull($allocation);
        $this->assertSame(ResourceAllocation::STATUS_HELD, $allocation->status);

        // -------------------------------------------------------------
        // Step 4: Durable Payment Intent & Central Route Registration
        // -------------------------------------------------------------
        $providerOrderId = 'order_e2e_' . Str::random(12);
        $providerPaymentId = 'pay_e2e_' . Str::random(12);

        $intent = new PaymentIntent();
        $intent->forceFill([
            'tenant_id' => $this->tenantId,
            'booking_id' => (string) $booking->getKey(),
            'user_id' => 1001,
            'amount_minor' => $quote['summary']['total_minor'],
            'currency' => 'INR',
            'status' => PaymentIntent::STATUS_PENDING,
            'provider' => 'razorpay',
            'provider_order_id' => $providerOrderId,
            'quote_snapshot' => $quote,
        ]);
        $intent->save();

        // Register route centrally in ProviderOperationRoute
        $route = new ProviderOperationRoute();
        $route->forceFill([
            'provider_code' => 'RAZORPAY',
            'provider_operation_id' => $providerOrderId,
            'tenant_id' => $this->tenantId,
            'connection_name' => 'tenant_shared',
        ]);
        $route->save();

        // -------------------------------------------------------------
        // Step 5: Webhook Capture Lands with Central Inbox & Raw HMAC
        // -------------------------------------------------------------
        $webhookEventId = 'evt_e2e_' . Str::random(12);
        $webhookBody = json_encode([
            'event' => 'payment.captured',
            'event_id' => $webhookEventId,
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => $providerPaymentId,
                        'order_id' => $providerOrderId,
                        'amount' => $quote['summary']['total_minor'],
                        'currency' => 'INR',
                        'status' => 'captured',
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha256', $webhookBody, 'e2e_webhook_secret_key');

        $response = $this->call(
            'POST',
            '/api/v1/payment/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
                'HTTP_X_RAZORPAY_EVENT_ID' => $webhookEventId,
            ],
            $webhookBody
        );

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->json();
        $this->assertSame('processed', $responseData['data']['status']);

        // Verify Intent transitioned to SUCCEEDED
        $intent->refresh();
        $this->assertSame(PaymentIntent::STATUS_SUCCEEDED, $intent->status);

        // Verify Booking transitioned to CONFIRMED
        $booking->refresh();
        $this->assertSame(BookingLifecycle::STATUS_CONFIRMED, $booking->status);

        // Verify ResourceAllocation transitioned to COMMITTED
        $allocation->refresh();
        $this->assertSame(ResourceAllocation::STATUS_COMMITTED, $allocation->status);

        // Replay of same webhook is deduplicated idempotently
        $replayResponse = $this->call(
            'POST',
            '/api/v1/payment/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
                'HTTP_X_RAZORPAY_EVENT_ID' => $webhookEventId,
            ],
            $webhookBody
        );
        $this->assertSame(200, $replayResponse->getStatusCode());
        $this->assertSame('ignored', $replayResponse->json()['data']['status']);
        $this->assertSame('replay', $replayResponse->json()['data']['reason']);

        // -------------------------------------------------------------
        // Step 6: Gapless Legal Invoice Issuance & Double-Entry Subledger
        // -------------------------------------------------------------
        $this->resolveContext($tenant);
        $financeDocService = app(FinanceDocumentService::class);
        $panditCompensationMinor = 85000; // ₹850.00 compensation for pandit
        $invoice = $financeDocService->issueBookingInvoice(
            tenantId: $this->tenantId,
            bookingId: (string) $booking->getKey(),
            paymentOperationId: (string) $intent->getKey(),
            buyerId: 1001,
            currency: 'INR',
            subtotalMinor: $quote['summary']['subtotal_minor'],
            taxMinor: $quote['summary']['tax_total_minor'],
            totalMinor: $quote['summary']['total_minor'],
            lines: [
                [
                    'sku' => 'RITUAL-GANESH-PUJA',
                    'description' => 'Ganesh Puja Sacred Ritual Service',
                    'quantity' => 1,
                    'unit_price_minor' => 100000,
                    'tax_rate_bps' => 1800,
                    'tax_minor' => 18000,
                    'total_minor' => 118000,
                ],
            ],
            beneficiaryType: 'PANDIT',
            beneficiaryId: 'pandit_e2e_001',
            beneficiaryPayoutMinor: $panditCompensationMinor,
            tdsRateBps: 100 // 1% TDS under Section 194C
        );

        $this->assertStringStartsWith('INV-' . date('Y') . '-', $invoice->document_number);
        $this->assertSame(FinanceDocument::STATUS_ISSUED, $invoice->status);
        $this->assertSame($quote['summary']['total_minor'], $invoice->total_minor);

        // Verify balanced double-entry ledger journal
        $journal = LedgerJournal::query()
            ->where('tenant_id', $this->tenantId)
            ->where('reference_type', 'finance_document')
            ->where('reference_id', (string) $invoice->getKey())
            ->first();

        $this->assertNotNull($journal);
        $this->assertStringStartsWith('JRN-' . date('Y') . '-', $journal->journal_number);

        $entries = LedgerEntry::query()
            ->where('tenant_id', $this->tenantId)
            ->where('journal_id', (string) $journal->getKey())
            ->get();

        $debitTotal = $entries->where('entry_direction', LedgerEntry::DIRECTION_DEBIT)->sum('amount_minor');
        $creditTotal = $entries->where('entry_direction', LedgerEntry::DIRECTION_CREDIT)->sum('amount_minor');

        // Absolute double-entry invariant: Total Debits == Total Credits
        $this->assertSame($quote['summary']['total_minor'], $debitTotal);
        $this->assertSame($quote['summary']['total_minor'], $creditTotal);
        $this->assertSame($debitTotal, $creditTotal);

        // -------------------------------------------------------------
        // Step 7: Settlement Obligation & Maker-Checker Dual Custody Payout
        // -------------------------------------------------------------
        $obligation = SettlementObligation::query()
            ->where('tenant_id', $this->tenantId)
            ->where('booking_id', (string) $booking->getKey())
            ->first();

        $this->assertNotNull($obligation);
        $this->assertSame(85000, $obligation->gross_amount_minor);
        $this->assertSame(850, $obligation->tax_withheld_minor); // 1% of 85,000 = 850 paise
        $this->assertSame(84150, $obligation->net_payable_minor); // 85,000 - 850 = 84,150 paise (₹841.50)

        // Maker initiates payout proposal
        $payoutService = app(MakerCheckerPayoutService::class);
        $makerId = 'operator_maker_01';
        $checkerId = 'operator_checker_02';

        $payout = $payoutService->initiatePayout(
            tenantId: $this->tenantId,
            beneficiaryId: 'pandit_e2e_001',
            amountMinor: $obligation->net_payable_minor,
            currency: 'INR',
            makerId: $makerId
        );

        $this->assertSame(Payout::STATUS_PENDING_APPROVAL, $payout->status);
        $this->assertSame($makerId, $payout->maker_id);

        // Segregation of Duties: Maker attempting to approve their own payout is strictly rejected
        $caughtViolation = false;
        try {
            $payoutService->approvePayout($this->tenantId, (string) $payout->getKey(), $makerId);
        } catch (MakerCheckerViolationException $e) {
            $caughtViolation = true;
            $this->assertStringContainsString('Maker/Checker violation', $e->getMessage());
        }
        $this->assertTrue($caughtViolation, 'Maker should not be allowed to approve their own payout.');

        // Independent Checker approves payout
        $approvedPayout = $payoutService->approvePayout($this->tenantId, (string) $payout->getKey(), $checkerId);
        $this->assertSame(Payout::STATUS_APPROVED, $approvedPayout->status);
        $this->assertSame($checkerId, $approvedPayout->checker_id);
        $this->assertNotNull($approvedPayout->approved_at);
    }

    public function test_tenant_platform_saas_billing_isolation(): void
    {
        $tenant = $this->makeTenant();
        $tenantId = (string) $tenant->getKey();
        $this->resolveContext($tenant);

        // -------------------------------------------------------------
        // Step 8: Platform SaaS Subscriptions in Central Context
        // -------------------------------------------------------------
        $growthPlan = PlatformSubscriptionPlan::query()->create([
            'code' => 'GROWTH_E2E',
            'name' => 'Growth Plan',
            'billing_interval' => 'MONTHLY',
            'price_minor' => 499900, // ₹4,999.00 / month
            'currency' => 'INR',
            'features_json' => [
                'advanced_analytics' => true,
                'custom_domain' => true,
            ],
            'is_active' => true,
        ]);

        /** @var TenantSubscriptionService $subService */
        $subService = app(TenantSubscriptionService::class);
        $sub = $subService->subscribe($tenantId, 'GROWTH_E2E');

        $this->assertSame(TenantSubscription::STATUS_ACTIVE, $sub->status);

        // Generate platform SaaS invoice
        $saasInvoice = $subService->recordInvoice(
            $tenantId,
            (string) $sub->getKey(),
            499900,
            89982,
            'INR'
        );

        $this->assertStringStartsWith('NAV-SAAS-' . date('Y') . '-', $saasInvoice->invoice_number);
        $this->assertSame('central', $saasInvoice->getConnectionName());
        $this->assertSame($tenantId, $saasInvoice->tenant_id);

        // Assert platform SaaS invoice lives on central, isolated from tenant operational documents
        $tenantDocsCount = FinanceDocument::query()
            ->where('tenant_id', $tenantId)
            ->where('document_number', $saasInvoice->invoice_number)
            ->count();
        $this->assertSame(0, $tenantDocsCount);
    }
}
