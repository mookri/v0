<?php

namespace App\Domain\Verification\Lifecycle;

/**
 * Formats recorded verification/rejection/scope codes into plain-language applicant explanations
 * and actionable next steps (§10.4 / T-TR08-04).
 */
final class DecisionExplanationService
{
    /**
     * @param  array<string, mixed>  $context
     * @return array{headline: string, explanation: string, next_action: string, appealable: bool}
     */
    public function explain(?string $reasonCode, string $applicationStatus, array $context = []): array
    {
        return match ($reasonCode) {
            'DOC_ILLEGIBLE', 'INVALID_FILE_SIGNATURE', 'HASH_MISMATCH' => [
                'headline' => 'Document clarification required',
                'explanation' => 'An uploaded verification document could not be read or failed binary integrity checks.',
                'next_action' => 'Upload a clear original PDF or unedited photo of the document, or submit an appeal with replacement files.',
                'appealable' => true,
            ],
            'ISSUER_NO_RESPONSE_MANUAL_FALLBACK' => [
                'headline' => 'Reviewed manually (institution offline / no response)',
                'explanation' => 'Your issuing pathashala or institution did not respond within the outreach window. This is not treated as negative; your claim was reviewed via manual evidence.',
                'next_action' => 'No rejection occurred. You may attach supplementary peer or sampradaya attestations anytime.',
                'appealable' => true,
            ],
            'ISSUER_DENIED_CREDENTIAL' => [
                'headline' => 'Issuing institution could not confirm credential',
                'explanation' => 'The listed institution indicated it could not locate or confirm the submitted certificate reference.',
                'next_action' => 'Submit an appeal with corrected roll number, award year, or alternate institutional proof.',
                'appealable' => true,
            ],
            'CONFLICTING_TRUSTEE_CLAIM' => [
                'headline' => 'Institutional authority hold (conflicting claim)',
                'explanation' => 'Another verified representative currently holds an active authority grant for this temple or trust.',
                'next_action' => 'Provide an updated trustee board resolution co-signed by managing trustees via clarification or appeal.',
                'appealable' => true,
            ],
            default => [
                'headline' => match ($applicationStatus) {
                        'APPROVED', 'APPROVED_PROVISIONAL' => 'Application approved with active verification claims',
                        'REJECTED', 'REVOKED' => 'Application requires clarification or formal appeal',
                        'APPEALED' => 'Appeal under independent review',
                        default => 'Verification in progress',
                    },
                'explanation' => $context['note'] ?? 'Each verification claim displays its exact evidence mode (official, financial, manual, or native) and validity window.',
                'next_action' => in_array($applicationStatus, ['REJECTED', 'REVOKED'], true)
                    ? 'Review missing or flagged items below and file an appeal for independent review.'
                    : 'Track claim statuses and upcoming expiry dates directly from this status view.',
                'appealable' => in_array($applicationStatus, ['REJECTED', 'REVOKED'], true),
            ],
        };
    }
}

