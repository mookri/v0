<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Modules\Onboarding\Models\ApplicationDocument;
use App\Modules\Onboarding\Models\PartnerApplication;
use App\Modules\Onboarding\StepValidators;
use App\Modules\Onboarding\TrackCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Draft evidence, status and document intake for onboarding applications.
 * Ownership + tenant scope enforced per route; step names validated
 * against the version-pinned track catalog (unknown steps 422, never
 * stored). Uploaded bytes land on the local disk (object-storage port
 * selects the backend later); metadata is the tenant-scoped record.
 */
final class DraftController
{
    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    private function ownApplication(Request $request, string $id): PartnerApplication|JsonResponse
    {
        $application = PartnerApplication::query()->find($id);

        if ($application === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Application not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        if ((string) $application->identity_id !== (string) $request->user()->getAuthIdentifier()) {
            return new JsonResponse([
                'error' => ['code' => 'permission_missing', 'message' => 'Not allowed.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        return $application;
    }

    public function putStep(Request $request, string $id, string $step): JsonResponse
    {
        $found = $this->ownApplication($request, $id);
        if ($found instanceof JsonResponse) {
            return $found;
        }
        $application = $found;

        if (! in_array($application->status, [PartnerApplication::STATUS_DRAFT, PartnerApplication::STATUS_UNDER_REVIEW], true)) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Application is not editable.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $track = TrackCatalog::find($application->track);
        $stepIds = array_column($track['steps'] ?? [], 'id');
        if (! in_array($step, $stepIds, true)) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => 'Unknown step.', 'details' => ['fields' => ['step' => ['unknown_step']]], 'request_id' => $this->requestId($request)],
            ], 422);
        }

        $fieldErrors = StepValidators::validate($step, $request->json('value'));
        if ($fieldErrors !== []) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => 'Invalid step data.', 'details' => ['fields' => $fieldErrors], 'request_id' => $this->requestId($request)],
            ], 422);
        }

        $evidence = $application->evidence ?? [];
        $evidence[$step] = $request->json('value');

        // Evidence cap: one application carries at most 64KB of draft
        // evidence (per-application bound, not per step — clients chunk
        // large payloads into documents, never into drafts).
        if (strlen((string) json_encode($evidence)) > 65536) {
            return new JsonResponse([
                'error' => ['code' => 'payload_too_large', 'message' => 'Draft evidence exceeds 64KB; move large payloads to documents.', 'request_id' => $this->requestId($request)],
            ], 413);
        }

        $application->forceFill(['evidence' => $evidence])->save();

        return new JsonResponse([
            'data' => ['id' => (string) $application->getKey(), 'object' => 'partner_application', 'step' => $step, 'saved' => true],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function status(Request $request, string $id): JsonResponse
    {
        $found = $this->ownApplication($request, $id);
        if ($found instanceof JsonResponse) {
            return $found;
        }
        $application = $found;

        $documents = ApplicationDocument::query()->where('application_id', $application->getKey())->get();

        return new JsonResponse([
            'data' => [
                'id' => (string) $application->getKey(),
                'object' => 'partner_application',
                'status' => $application->status,
                'rejection_code' => $application->rejection_code,
                'documents' => $documents->map(static fn (ApplicationDocument $document): array => [
                    'id' => (string) $document->getKey(),
                    'kind' => $document->kind,
                    'status' => $document->status,
                    'reason_code' => $document->reason_code,
                ])->all(),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function uploadDocument(Request $request, string $id): JsonResponse
    {
        $found = $this->ownApplication($request, $id);
        if ($found instanceof JsonResponse) {
            return $found;
        }
        $application = $found;

        $validated = $request->validate([
            'kind' => ['required', 'string', 'in:id-proof,address-proof,certification,bank-proof,photo'],
            'file' => ['required', 'file', 'max:10240', 'mimetypes:image/jpeg,image/png,application/pdf'],
        ]);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $validated['file'];
        $sha256 = hash_file('sha256', $file->getRealPath());
        $key = "onboarding/{$application->getKey()}/{$validated['kind']}/{$sha256}";
        Storage::put($key, file_get_contents($file->getRealPath()));

        $document = new ApplicationDocument();
        $document->forceFill([
            'application_id' => (string) $application->getKey(),
            'kind' => $validated['kind'],
            'status' => ApplicationDocument::STATUS_PENDING,
            'object_key' => $key,
            'sha256' => $sha256,
        ]);
        $document->save();

        return new JsonResponse([
            'data' => [
                'id' => (string) $document->getKey(),
                'object' => 'application_document',
                'kind' => $document->kind,
                'status' => $document->status,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }
}
