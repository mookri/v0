<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Domain\Verification\Evidence\DocumentRetrievalSigner;
use App\Domain\Verification\Evidence\EvidenceIntegrityVerifier;
use App\Domain\Verification\Evidence\MagicByteValidator;
use App\Modules\Onboarding\Models\ApplicationDocument;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Capability-checked, short-lived signed, integrity-verified & audited
 * evidence streaming controller (SPEC §5 / Closes T-05, T-04, T-TR01-01..03).
 */
final class DocumentContentController
{
    public function __construct(
        private readonly AuditWriter $audit,
        private readonly TenantContext $context,
        private readonly DocumentRetrievalSigner $signer,
        private readonly EvidenceIntegrityVerifier $integrity,
        private readonly MagicByteValidator $magicBytes,
    ) {
    }

    public function accessLink(Request $request, string $id): JsonResponse
    {
        $document = ApplicationDocument::query()->find($id);
        if ($document === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Document not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $tenantId = (string) $this->context->tenantId();
        $viewerId = (string) $request->user()->getAuthIdentifier();
        $expiresAt = time() + DocumentRetrievalSigner::MAX_TTL_SECONDS;
        $signature = $this->signer->sign($tenantId, (string) $document->getKey(), $viewerId, $expiresAt, $this->signingKey());

        $contentUrl = "/api/v1/onboarding/documents/{$document->getKey()}/content?expires={$expiresAt}&signature={$signature}";

        return new JsonResponse([
            'data' => [
                'document_id' => (string) $document->getKey(),
                'kind' => $document->kind,
                'status' => $document->status,
                'integrity_state' => $document->integrity_state,
                'content_url' => $contentUrl,
                'expires_at' => gmdate('c', $expiresAt),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function content(Request $request, string $id): Response|JsonResponse
    {
        $document = ApplicationDocument::query()->find($id);
        if ($document === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Document not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $tenantId = (string) $this->context->tenantId();
        $viewerId = (string) $request->user()->getAuthIdentifier();
        $expiresAt = (int) $request->query('expires', '0');
        $signature = (string) $request->query('signature', '');

        if (! $this->signer->verify($tenantId, (string) $document->getKey(), $viewerId, $expiresAt, $signature, $this->signingKey())) {
            return new JsonResponse([
                'error' => ['code' => 'invalid_signature', 'message' => 'Signed access link is missing, expired, or invalid.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        if ($document->quarantine_status !== ApplicationDocument::QUARANTINE_CLEAN) {
            return new JsonResponse([
                'error' => ['code' => 'document_quarantined', 'message' => 'Document has not cleared quarantine checks.', 'request_id' => $this->requestId($request)],
            ], 423);
        }

        // Recompute SHA-256 against stored hash before returning any bytes (T-04)
        $integrityResult = $this->integrity->verifyDocument($document, auditFailures: true);
        if (! $integrityResult['ok']) {
            return new JsonResponse([
                'error' => [
                    'code' => 'integrity_mismatch',
                    'message' => 'Stored evidence failed integrity verification.',
                    'integrity_state' => $integrityResult['state'],
                    'request_id' => $this->requestId($request),
                ],
            ], 409);
        }

        $disk = Storage::disk((string) config('filesystems.default', 'local'));
        $bytes = (string) $disk->get((string) $document->object_key);
        $tempPath = tempnam(sys_get_temp_dir(), 'nav_evid_');
        if ($tempPath === false) {
            return new JsonResponse(['error' => ['code' => 'io_error', 'message' => 'Unable to inspect content.', 'request_id' => $this->requestId($request)]], 500);
        }

        try {
            file_put_contents($tempPath, $bytes);
            $sniff = $this->magicBytes->inspect($tempPath);
        } finally {
            @unlink($tempPath);
        }

        $mime = $sniff['detected_mime'] ?? 'application/octet-stream';
        if (! $sniff['valid']) {
            return new JsonResponse([
                'error' => ['code' => 'unsafe_content', 'message' => 'Evidence payload blocked by content safety rules.', 'request_id' => $this->requestId($request)],
            ], 422);
        }

        // Audit every successful reviewer document access (T-TR01-01 / AC-2)
        $this->audit->record(
            'onboarding.document.accessed',
            [
                'module' => 'onboarding',
                'resource_type' => 'application_document',
                'resource_id' => (string) $document->getKey(),
            ],
            null,
            [
                'application_id' => $document->application_id,
                'purpose' => (string) $request->query('purpose', 'verification_review'),
                'ip_hash' => hash('sha256', (string) ($request->ip() ?? 'unknown')),
                'mime' => $mime,
            ]
        );

        $ext = match ($mime) {
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            default => 'bin',
        };

        // PDFs MUST NEVER inline-render as active content (SPEC §5.4)
        $disposition = $mime === 'application/pdf'
            ? "attachment; filename=\"evidence-{$document->getKey()}.{$ext}\""
            : "inline; filename=\"evidence-{$document->getKey()}.{$ext}\"";

        $headers = [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            'Pragma' => 'no-cache',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ];

        return new Response($bytes, 200, $headers);
    }

    private function signingKey(): string
    {
        $configured = (string) config('verification.document_signing_key', '');
        if ($configured !== '') {
            return $configured;
        }

        return (string) config('app.key', 'navdhya-fallback-dev-signing-key');
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }
}
