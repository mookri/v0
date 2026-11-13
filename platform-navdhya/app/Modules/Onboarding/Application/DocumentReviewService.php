<?php

namespace App\Modules\Onboarding\Application;

use App\Domain\Verification\Decisions\DecisionService;
use App\Domain\Verification\Evidence\EvidenceIntegrityVerifier;
use App\Modules\Onboarding\Models\ApplicationDocument;
use App\Modules\Onboarding\Models\PartnerApplication;
use App\Platform\Api\CodedHttpException;
use App\Platform\Audit\AuditWriter;
use DateTimeInterface;

/**
 * Separates storage binary integrity (`integrity_state`) from compliance review
 * judgment (`status`: PENDING -> VERIFIED | REJECTED) (T-TR02-04, Closes T-12).
 */
final class DocumentReviewService
{
    public const REJECT_REASONS = [
        'MIME_NOT_ALLOWED',
        'TOO_LARGE',
        'HASH_MISMATCH',
        'EXPIRED_EVIDENCE',
        'ILLEGIBLE',
        'UNVERIFIABLE',
        'TAMPERED',
    ];

    public function __construct(
        private readonly EvidenceIntegrityVerifier $integrity,
        private readonly DecisionService $decisions,
        private readonly AuditWriter $audit,
    ) {
    }

    public function verify(
        ApplicationDocument $document,
        PartnerApplication $application,
        string $reviewerId,
        ?DateTimeInterface $expiresAt = null,
    ): ApplicationDocument {
        $this->decisions->assertSegregationFromApplicant(
            (string) $application->getKey(),
            (string) $application->identity_id,
            $reviewerId,
            'document:verify',
        );

        if ($document->status !== 'PENDING') {
            throw new CodedHttpException(409, 'state_forbidden', 'Document is no longer pending review.');
        }

        if ($document->quarantine_status !== 'clean') {
            throw new CodedHttpException(409, 'document_quarantined', 'Quarantined document cannot be verified.');
        }

        $integrityResult = $this->integrity->verifyDocument($document);
        if (! $integrityResult['ok']) {
            throw new CodedHttpException(409, 'evidence_integrity_failed', 'Document storage integrity check failed.');
        }

        $before = ['status' => $document->status, 'integrity_state' => $document->integrity_state];

        $document->forceFill([
            'status' => 'VERIFIED',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now()->utc(),
            'expires_at' => $expiresAt,
            'rejection_reason_code' => null,
        ])->save();

        $this->audit->record(
            'onboarding.document.reviewed',
            ['module' => 'onboarding', 'resource_type' => 'application_document', 'resource_id' => (string) $document->getKey()],
            $before,
            ['status' => 'VERIFIED', 'reviewed_by' => $reviewerId, 'application_id' => (string) $application->getKey()],
        );

        return $document;
    }

    public function reject(
        ApplicationDocument $document,
        PartnerApplication $application,
        string $reviewerId,
        string $reasonCode,
    ): ApplicationDocument {
        $this->decisions->assertSegregationFromApplicant(
            (string) $application->getKey(),
            (string) $application->identity_id,
            $reviewerId,
            'document:reject',
        );

        if (! in_array($reasonCode, self::REJECT_REASONS, true)) {
            throw new CodedHttpException(422, 'validation_failed', 'Invalid document rejection reason code.');
        }

        if ($document->status !== 'PENDING') {
            throw new CodedHttpException(409, 'state_forbidden', 'Document is no longer pending review.');
        }

        $before = ['status' => $document->status];

        $document->forceFill([
            'status' => 'REJECTED',
            'rejection_reason_code' => $reasonCode,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now()->utc(),
        ])->save();

        $this->audit->record(
            'onboarding.document.reviewed',
            ['module' => 'onboarding', 'resource_type' => 'application_document', 'resource_id' => (string) $document->getKey()],
            $before,
            ['status' => 'REJECTED', 'reason_code' => $reasonCode, 'reviewed_by' => $reviewerId, 'application_id' => (string) $application->getKey()],
        );

        return $document;
    }
}
