<?php

namespace App\Modules\Finance\Domain;

use App\Domain\Verification\Contracts\VerifiesPayoutOwnership;
use App\Modules\Finance\Domain\Exceptions\MakerCheckerViolationException;
use App\Modules\Finance\Models\Payout;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use Carbon\CarbonImmutable;
use RuntimeException;

final class MakerCheckerPayoutService
{
    public function __construct(
        private readonly AuditWriter $audit,
        private readonly Outbox $outbox,
        private readonly ?VerifiesPayoutOwnership $payoutGuard = null,
    ) {
    }

    /**
     * Maker initiates a payout proposal.
     */
    public function initiatePayout(
        string $tenantId,
        string $beneficiaryId,
        int $amountMinor,
        string $currency,
        string $makerId,
        ?string $batchId = null
    ): Payout {
        $payout = new Payout();
        $payout->forceFill([
            'tenant_id' => $tenantId,
            'payout_batch_id' => $batchId,
            'beneficiary_id' => $beneficiaryId,
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'status' => Payout::STATUS_PENDING_APPROVAL,
            'maker_id' => $makerId,
        ]);
        $payout->save();

        return $payout;
    }

    /**
     * Checker approves payout — strictly enforces maker != checker and payout-ownership invariant (G-FISCAL-1).
     */
    public function approvePayout(
        string $tenantId,
        string $payoutId,
        string $checkerId,
        ?string $instrumentId = null,
        bool $requireVerifiedInstrument = false,
    ): Payout {
        /** @var Payout|null $payout */
        $payout = Payout::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $payoutId)
            ->lockForUpdate()
            ->first();

        if ($payout === null) {
            throw new RuntimeException('Payout not found.');
        }

        if ($payout->status !== Payout::STATUS_PENDING_APPROVAL) {
            throw new RuntimeException("Cannot approve payout in state {$payout->status}.");
        }

        // Maker/Checker dual custody: maker cannot approve their own payout
        if ((string) $payout->maker_id === (string) $checkerId) {
            throw new MakerCheckerViolationException('Maker/Checker violation: Payout creator cannot approve their own payout.');
        }

        $guard = $this->payoutGuard ?? (app()->bound(VerifiesPayoutOwnership::class) ? app(VerifiesPayoutOwnership::class) : null);
        if (
            $guard !== null
            && ($instrumentId !== null || $requireVerifiedInstrument || $guard->subjectHasVerificationState($tenantId, (string) $payout->beneficiary_id))
        ) {
            $guard->assertEligible($tenantId, (string) $payout->beneficiary_id, $instrumentId);
        }

        $now = CarbonImmutable::now('UTC');
        $payout->status = Payout::STATUS_APPROVED;
        $payout->checker_id = $checkerId;
        $payout->approved_at = $now;
        $payout->save();

        $this->outbox->append(
            $tenantId,
            'payout',
            (string) $payout->getKey(),
            'finance.payout.approved',
            [
                'payout_id' => (string) $payout->getKey(),
                'beneficiary_id' => $payout->beneficiary_id,
                'amount_minor' => $payout->amount_minor,
                'currency' => $payout->currency,
                'maker_id' => $payout->maker_id,
                'checker_id' => $checkerId,
            ]
        );

        $this->audit->record(
            'finance.payout.approved',
            ['module' => 'finance', 'resource_type' => 'payout', 'resource_id' => (string) $payout->getKey()],
            ['status' => Payout::STATUS_PENDING_APPROVAL],
            ['status' => Payout::STATUS_APPROVED, 'maker_id' => $payout->maker_id, 'checker_id' => $checkerId]
        );

        return $payout;
    }
}
