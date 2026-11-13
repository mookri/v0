<?php

declare(strict_types=1);

namespace App\Modules\Support\Domain\Grievance;

use App\Modules\Support\Models\SupportCase;
use App\Modules\Support\Models\SupportExternalReferral;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;

/**
 * External Grievance Referral / NCH Convergence Port & Manual Fallback (A-4 / G-NCH-0).
 *
 * Intentionally assumes NO public NCH API; ships a working manual fallback
 * (export packet + portal reference tracking) and config-gated partner adapter hook.
 */
final class NchReferralService
{
    public function __construct(
        private readonly AuditWriter $audit,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function prepareManualPacket(SupportCase $case): array
    {
        return [
            'case_reference' => $case->case_reference,
            'subject' => $case->subject,
            'category' => $case->category,
            'opened_at' => $case->created_at?->toJSON(),
            'channel' => 'NCH',
            'adapter_mode' => 'manual_fallback',
            'instructions' => 'Submit this reference packet through the authorized NCH Convergence portal and record the returned docket number via recordExternalReference().',
        ];
    }

    public function recordExternalReference(
        string $caseId,
        string $externalReference,
        string $channel = 'NCH',
        string $adapterMode = 'manual_fallback',
    ): SupportExternalReferral {
        if (trim($externalReference) === '') {
            throw new \InvalidArgumentException('External NCH reference cannot be empty.');
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $case = SupportCase::query()->where('tenant_id', $tenantId)->where('id', $caseId)->firstOrFail();

        /** @var SupportExternalReferral $ref */
        $ref = SupportExternalReferral::query()->create([
            'tenant_id' => $tenantId,
            'case_id' => $caseId,
            'channel' => strtoupper($channel),
            'external_reference' => trim($externalReference),
            'adapter_mode' => $adapterMode,
            'referred_at' => CarbonImmutable::now('UTC')->toDateTimeString(),
            'external_status' => 'SUBMITTED',
            'payload_snapshot' => $this->prepareManualPacket($case),
        ]);

        $this->audit->record(
            'support.external_referral.recorded',
            ['module' => 'support', 'resource_type' => 'support_case', 'resource_id' => $caseId],
            null,
            ['channel' => strtoupper($channel), 'external_reference' => trim($externalReference), 'mode' => $adapterMode]
        );

        return $ref;
    }

    public function reconcileStatus(string $referralId, string $externalStatus): SupportExternalReferral
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $ref = SupportExternalReferral::query()->where('tenant_id', $tenantId)->where('id', $referralId)->firstOrFail();

        $ref->forceFill([
            'external_status' => $externalStatus,
            'last_reconciled_at' => CarbonImmutable::now('UTC')->toDateTimeString(),
        ])->save();

        return $ref;
    }
}

