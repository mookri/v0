<?php

namespace App\Http\Controllers\Ops;

use App\Modules\Finance\Models\FinanceDocument;
use App\Platform\Tenancy\TenantScopeGuard;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Isolated SaaS tenant billing surface (W2 / T-AP04-07 / G-BILL-1):
 * reports the configured tenant's plan, currency, and SaaS subscription
 * invoice documents without exposing partner settlement payouts or gateway
 * clearing ledger lines. Capability `platform:billing:view` gates reads.
 */
final class BillingController
{
    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate(['tenant_id' => ['required', 'string']]);

        TenantScopeGuard::assertSameTenant($request, (string) $validated['tenant_id']);

        $tenant = Tenant::query()->find($validated['tenant_id']);
        if ($tenant === null || $tenant->state !== Tenant::STATE_ACTIVE) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Tenant unavailable.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $tenantId = (string) $tenant->getKey();
        $invoices = FinanceDocument::query()
            ->where('tenant_id', $tenantId)
            ->where('document_type', FinanceDocument::TYPE_INVOICE)
            ->whereNull('booking_id')
            ->orderByDesc('issued_at')
            ->limit(50)
            ->get()
            ->map(static fn (FinanceDocument $doc): array => [
                'id' => (string) $doc->getKey(),
                'document_number' => $doc->document_number,
                'status' => $doc->status,
                'currency' => $doc->currency,
                'subtotal_minor' => $doc->subtotal_minor,
                'tax_minor' => $doc->tax_minor,
                'total_minor' => $doc->total_minor,
                'issued_at' => $doc->issued_at?->toJSON(),
            ])
            ->values()
            ->all();

        return new JsonResponse([
            'data' => [
                'plan_code' => $tenant->plan_code,
                'status' => $tenant->status,
                'base_currency' => $tenant->base_currency,
                'invoices' => $invoices,
            ],
            'meta' => [
                'request_id' => $this->requestId($request),
                'boundary' => 'saas_tenant_billing_only',
            ],
        ]);
    }
}
