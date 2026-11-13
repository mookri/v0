<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Domain\Verification\Fiscal\FiscalIntakeService;
use App\Domain\Verification\Fiscal\FreezePayoutOnDiscrepancyListener;
use App\Domain\Verification\Identity\NameMatcher;
use App\Domain\Verification\Models\VerificationBankInstrument;
use App\Domain\Verification\Models\VerificationTaxRegistration;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FiscalVerificationController
{
    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ok(Request $request, array $data, int $status = 200): JsonResponse
    {
        return new JsonResponse([
            'data' => $data,
            'meta' => ['request_id' => $this->requestId($request)],
        ], $status);
    }

    public function showInstrument(string $instrumentId, Request $request, TenantContext $context): JsonResponse
    {
        $inst = VerificationBankInstrument::query()
            ->where('tenant_id', $context->tenantId())
            ->findOrFail($instrumentId);

        return $this->ok($request, ['instrument' => $inst->toMaskedArray()]);
    }

    public function showTax(string $taxId, Request $request, TenantContext $context): JsonResponse
    {
        $tax = VerificationTaxRegistration::query()
            ->where('tenant_id', $context->tenantId())
            ->findOrFail($taxId);

        return $this->ok($request, ['tax_registration' => $tax->toMaskedArray()]);
    }

    public function verifyBankInstrument(
        string $instrumentId,
        Request $request,
        TenantContext $context,
        FiscalIntakeService $intake,
    ): JsonResponse {
        $data = $request->validate([
            'countersigned_by' => ['nullable', 'string'],
            'policy_exception_note' => ['nullable', 'string'],
        ]);

        $inst = $intake->verifyBankInstrumentManually(
            tenantId: $context->tenantId(),
            instrumentId: $instrumentId,
            reviewerId: (string) $request->user()?->getKey(),
            countersignedBy: $data['countersigned_by'] ?? null,
            policyExceptionNote: $data['policy_exception_note'] ?? null,
        );

        return $this->ok($request, ['instrument' => $inst->toMaskedArray()]);
    }

    public function matchNames(Request $request, NameMatcher $matcher): JsonResponse
    {
        $data = $request->validate([
            'left' => ['required', 'string'],
            'right' => ['required', 'string'],
        ]);

        $res = $matcher->compare($data['left'], $data['right']);

        return $this->ok($request, [
            'score' => $res->score,
            'band' => $res->band->value,
            'normalized_left' => $res->normalizedLeft,
            'normalized_right' => $res->normalizedRight,
            'advisory_only' => $res->advisoryOnly,
        ]);
    }

    public function freezeDiscrepancy(
        Request $request,
        TenantContext $context,
        FreezePayoutOnDiscrepancyListener $freezer,
    ): JsonResponse {
        $data = $request->validate([
            'subject_type' => ['required', 'string'],
            'subject_id' => ['required', 'string'],
            'reason' => ['required', 'string', 'min:3'],
        ]);

        $count = $freezer->handle(
            tenantId: $context->tenantId(),
            subjectType: $data['subject_type'],
            subjectId: $data['subject_id'],
            actorId: (string) $request->user()?->getKey(),
            reason: $data['reason'],
        );

        return $this->ok($request, ['frozen_count' => $count]);
    }
}

