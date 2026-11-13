<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Modules\Onboarding\Application\DocumentReviewService;
use App\Modules\Onboarding\Models\ApplicationDocument;
use App\Modules\Onboarding\Models\PartnerApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DocumentReviewController
{
    public function __construct(
        private readonly DocumentReviewService $reviews,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    public function verify(Request $request, string $id, string $docId): JsonResponse
    {
        $validated = $request->validate([
            'expires_at' => ['nullable', 'date'],
        ]);

        [$application, $doc] = $this->resolvePair($id, $docId);
        if ($application === null || $doc === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Application or document not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $expiresAt = isset($validated['expires_at']) ? new \DateTimeImmutable((string) $validated['expires_at']) : null;
        $updated = $this->reviews->verify($doc, $application, (string) $request->user()->getAuthIdentifier(), $expiresAt);

        return new JsonResponse([
            'data' => [
                'id' => (string) $updated->getKey(),
                'application_id' => (string) $updated->application_id,
                'status' => $updated->status,
                'integrity_state' => $updated->integrity_state,
                'reviewed_by' => $updated->reviewed_by,
                'reviewed_at' => $updated->reviewed_at?->toJSON(),
                'expires_at' => $updated->expires_at?->toJSON(),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function reject(Request $request, string $id, string $docId): JsonResponse
    {
        $validated = $request->validate([
            'reason_code' => ['required', 'string'],
        ]);

        [$application, $doc] = $this->resolvePair($id, $docId);
        if ($application === null || $doc === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Application or document not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $updated = $this->reviews->reject($doc, $application, (string) $request->user()->getAuthIdentifier(), (string) $validated['reason_code']);

        return new JsonResponse([
            'data' => [
                'id' => (string) $updated->getKey(),
                'application_id' => (string) $updated->application_id,
                'status' => $updated->status,
                'integrity_state' => $updated->integrity_state,
                'rejection_reason_code' => $updated->rejection_reason_code,
                'reviewed_by' => $updated->reviewed_by,
                'reviewed_at' => $updated->reviewed_at?->toJSON(),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * @return array{0: ?PartnerApplication, 1: ?ApplicationDocument}
     */
    private function resolvePair(string $applicationId, string $docId): array
    {
        $application = PartnerApplication::query()->find($applicationId);
        if ($application === null) {
            return [null, null];
        }

        $doc = ApplicationDocument::query()
            ->where('application_id', (string) $application->getKey())
            ->where('id', $docId)
            ->first();

        return [$application, $doc];
    }
}
