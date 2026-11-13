<?php

namespace App\Http\Controllers\Ops;

use App\Domain\Verification\Models\VerificationTaxRegistration;
use App\Platform\Api\CodedHttpException;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantScopeGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Purpose-audited Tax register ops view (T-AP04-06).
 * Fails closed without an explicit audit `purpose` (>= 5 chars) and
 * never returns unmasked tax identifiers.
 */
final class TaxController
{
    public function __construct(private readonly AuditWriter $audit)
    {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            'purpose' => ['nullable', 'string', 'max:200'],
        ]);

        $tenantId = (string) $validated['tenant_id'];
        TenantScopeGuard::assertSameTenant($request, $tenantId);

        $purpose = trim((string) ($validated['purpose'] ?? ''));
        if (mb_strlen($purpose) < 5) {
            throw new CodedHttpException(
                422,
                'tax_purpose_required',
                'An explicit compliance or filing purpose (>= 5 characters) is required to view the tax register.',
            );
        }

        $tenant = Tenant::query()->find($tenantId);
        if ($tenant === null || $tenant->state !== Tenant::STATE_ACTIVE) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Tenant unavailable.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $this->audit->record(
            'ops.tax.register_viewed',
            ['module' => 'ops', 'resource_type' => 'tax_register', 'resource_id' => $tenantId],
            null,
            ['purpose' => $purpose],
        );

        $registrations = VerificationTaxRegistration::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(static fn (VerificationTaxRegistration $reg): array => $reg->toMaskedArray())
            ->values()
            ->all();

        return new JsonResponse([
            'data' => [
                'registrations' => $registrations,
                'audit_purpose' => $purpose,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}

