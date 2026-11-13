<?php

namespace Tests\Feature\Ops;

use App\Modules\Finance\Models\FinanceDocument;
use App\Modules\Finance\Models\Payout;
use App\Modules\Payment\Gateways\FakeGateway;
use App\Modules\Payment\Models\PaymentIntent;
use App\Platform\Audit\AuditLog;
use App\Platform\Identity\Models\MfaStepUp;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

final class DashboardOpsControlSurfacesTest extends TenantIntegrationTestCase
{
    public function test_payout_approval_requires_step_up_idempotency_maker_checker_and_beneficiary_match(): void
    {
        $tenant = $this->makeTenant();
        [$maker] = $this->actingAsMembership($tenant, ['platform:ops:view', 'platform:billing:view']);
        [$checker] = $this->actingAsMembership($tenant, ['platform:ops:view', 'platform:billing:view']);

        $headers = $this->tenantHeaders($tenant);
        $tid = (string) $tenant->getKey();

        // 1. Maker initiates payout proposal.
        $created = $this->actingAs($maker)->postJson('/api/v1/platform/ops/payouts', [
            'tenant_id' => $tid,
            'beneficiary_id' => 'pandit_ben_001',
            'amount_minor' => 250000,
            'currency' => 'INR',
        ], $headers)->assertCreated();

        $payoutId = (string) $created->json('data.id');
        $this->assertSame(Payout::STATUS_PENDING_APPROVAL, $created->json('data.status'));

        // 2. Without MFA step-up -> RequiresAcr blocks with 403 step_up_required.
        $this->actingAs($checker)->postJson("/api/v1/platform/ops/payouts/{$payoutId}:approve", [
            'tenant_id' => $tid,
            'checklist_confirmed' => true,
        ], array_merge($headers, ['Idempotency-Key' => 'idem-payout-1']))
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'step_up_required');

        // Satisfy MFA step-up for both maker and checker.
        $maker->forceFill(['mfa_enabled' => true])->save();
        $checker->forceFill(['mfa_enabled' => true])->save();
        MfaStepUp::create(['user_id' => $maker->getAuthIdentifier(), 'verified_at' => now()]);
        MfaStepUp::create(['user_id' => $checker->getAuthIdentifier(), 'verified_at' => now()]);

        // 3. Missing Idempotency-Key -> fail closed 400.
        $this->actingAs($checker)->postJson("/api/v1/platform/ops/payouts/{$payoutId}:approve", [
            'tenant_id' => $tid,
            'checklist_confirmed' => true,
        ], $headers)
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'idempotency_key_required');

        // 4. Beneficiary substitution guard -> 409 beneficiary_changed.
        $this->actingAs($checker)->postJson("/api/v1/platform/ops/payouts/{$payoutId}:approve", [
            'tenant_id' => $tid,
            'expected_beneficiary_id' => 'pandit_ben_999',
            'checklist_confirmed' => true,
        ], array_merge($headers, ['Idempotency-Key' => 'idem-payout-subst']))
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'beneficiary_changed');

        // 5. Same maker attempting approval -> 403 maker_checker_violation.
        $this->actingAs($maker)->postJson("/api/v1/platform/ops/payouts/{$payoutId}:approve", [
            'tenant_id' => $tid,
            'expected_beneficiary_id' => 'pandit_ben_001',
            'checklist_confirmed' => true,
        ], array_merge($headers, ['Idempotency-Key' => 'idem-payout-self']))
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'maker_checker_violation');

        // 6. Distinct checker + fresh MFA + preserved Idempotency-Key -> APPROVED & idempotent replay.
        $idemKey = 'idem-payout-approve-'.Str::uuid();
        $this->actingAs($checker)->postJson("/api/v1/platform/ops/payouts/{$payoutId}:approve", [
            'tenant_id' => $tid,
            'expected_beneficiary_id' => 'pandit_ben_001',
            'checklist_confirmed' => true,
        ], array_merge($headers, ['Idempotency-Key' => $idemKey]))
            ->assertOk()
            ->assertJsonPath('data.status', Payout::STATUS_APPROVED)
            ->assertJsonPath('data.checker_id', (string) $checker->getAuthIdentifier());

        // Replaying with same Idempotency-Key returns same APPROVED response cleanly.
        $this->actingAs($checker)->postJson("/api/v1/platform/ops/payouts/{$payoutId}:approve", [
            'tenant_id' => $tid,
            'expected_beneficiary_id' => 'pandit_ben_001',
            'checklist_confirmed' => true,
        ], array_merge($headers, ['Idempotency-Key' => $idemKey]))
            ->assertOk()
            ->assertJsonPath('data.status', Payout::STATUS_APPROVED);
    }

    public function test_tax_register_requires_purpose_and_records_audit_without_exposing_raw_refs(): void
    {
        $tenant = $this->makeTenant();
        [$ops] = $this->actingAsMembership($tenant, ['platform:ops:view']);
        $headers = $this->tenantHeaders($tenant);
        $tid = (string) $tenant->getKey();

        // Missing purpose fails closed with 422 tax_purpose_required.
        $this->actingAs($ops)
            ->getJson("/api/v1/platform/ops/tax?tenant_id={$tid}", $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'tax_purpose_required');

        // Explicit purpose returns 200 and records ops.tax.register_viewed audit entry.
        $this->actingAs($ops)
            ->getJson("/api/v1/platform/ops/tax?tenant_id={$tid}&purpose=".urlencode('Q3 GSTR-1 reconciliation audit'), $headers)
            ->assertOk()
            ->assertJsonPath('data.audit_purpose', 'Q3 GSTR-1 reconciliation audit');

        $this->resolveContext($tenant);
        try {
            $this->assertTrue(
                AuditLog::query()->where('tenant_id', $tid)->where('action', 'ops.tax.register_viewed')->exists(),
            );
        } finally {
            $this->clearContext();
        }
    }

    public function test_billing_surface_isolates_saas_invoices_from_partner_payout_settlements_g_bill_1(): void
    {
        $tenant = $this->makeTenant();
        [$billingViewer] = $this->actingAsMembership($tenant, ['platform:billing:view']);
        $headers = $this->tenantHeaders($tenant);
        $tid = (string) $tenant->getKey();

        $this->resolveContext($tenant);
        try {
            Payout::create([
                'tenant_id' => $tid,
                'beneficiary_id' => 'secret_pandit_beneficiary',
                'amount_minor' => 99900,
                'currency' => 'INR',
                'status' => Payout::STATUS_PENDING_APPROVAL,
                'maker_id' => 'maker_1',
            ]);

            FinanceDocument::create([
                'tenant_id' => $tid,
                'document_number' => 'SAAS-INV-2026-0001',
                'document_type' => FinanceDocument::TYPE_INVOICE,
                'booking_id' => null,
                'currency' => 'INR',
                'subtotal_minor' => 49900,
                'tax_minor' => 8982,
                'total_minor' => 58882,
                'status' => FinanceDocument::STATUS_PAID,
                'issued_at' => now(),
            ]);

            // Booking invoice should NOT leak onto SaaS tenant billing surface.
            FinanceDocument::create([
                'tenant_id' => $tid,
                'document_number' => 'BK-INV-2026-0002',
                'document_type' => FinanceDocument::TYPE_INVOICE,
                'booking_id' => '01JBOOKING0000000000000001',
                'currency' => 'INR',
                'subtotal_minor' => 100000,
                'tax_minor' => 18000,
                'total_minor' => 118000,
                'status' => FinanceDocument::STATUS_ISSUED,
                'issued_at' => now(),
            ]);
        } finally {
            $this->clearContext();
        }

        $resp = $this->actingAs($billingViewer)
            ->getJson("/api/v1/platform/billing?tenant_id={$tid}", $headers)
            ->assertOk();

        $resp->assertJsonCount(1, 'data.invoices');
        $resp->assertJsonPath('data.invoices.0.document_number', 'SAAS-INV-2026-0001');
        $resp->assertJsonPath('meta.boundary', 'saas_tenant_billing_only');

        $raw = json_encode($resp->json(), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('secret_pandit_beneficiary', $raw);
        $this->assertStringNotContainsString('BK-INV-2026-0002', $raw);
    }

    public function test_stats_reconciliation_and_dispatch_surfaces_respond_with_structured_contracts(): void
    {
        $tenant = $this->makeTenant();
        [$ops] = $this->actingAsMembership($tenant, ['platform:ops:view']);
        $headers = $this->tenantHeaders($tenant);
        $tid = (string) $tenant->getKey();

        $ops->forceFill(['mfa_enabled' => true])->save();
        MfaStepUp::create(['user_id' => $ops->getAuthIdentifier(), 'verified_at' => now()]);

        $this->actingAs($ops)
            ->getJson("/api/v1/platform/stats?tenant_id={$tid}", $headers)
            ->assertOk()
            ->assertJsonStructure(['data' => ['metrics' => [['key', 'label', 'value', 'unit', 'numerator', 'denominator', 'formula', 'as_of']]]]);

        $this->actingAs($ops)
            ->getJson("/api/v1/platform/ops/dispatch?tenant_id={$tid}", $headers)
            ->assertOk()
            ->assertJsonStructure(['data' => ['active_assignments', 'unassigned_bookings']]);

        FakeGateway::resetTestOutcomes();
        $intent = PaymentIntent::create([
            'tenant_id' => $tid,
            'booking_id' => '01JBOOKING0000000000000099',
            'user_id' => $ops->getAuthIdentifier(),
            'provider' => 'fake',
            'provider_order_id' => 'order_unknown_1',
            'amount_minor' => 25000,
            'currency' => 'INR',
            'status' => PaymentIntent::STATUS_PAYMENT_UNKNOWN,
        ]);

        $this->actingAs($ops)
            ->getJson("/api/v1/platform/ops/reconciliation?tenant_id={$tid}", $headers)
            ->assertOk()
            ->assertJsonPath('data.uncertain_intents.0.id', (string) $intent->getKey());

        $this->actingAs($ops)
            ->postJson("/api/v1/platform/ops/reconciliation/{$intent->getKey()}:retry", ['tenant_id' => $tid], $headers)
            ->assertOk()
            ->assertJsonPath('data.outcome', 'deferred');
    }
}
