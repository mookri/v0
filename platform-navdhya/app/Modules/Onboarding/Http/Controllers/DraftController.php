<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Domain\Verification\Evidence\MagicByteValidator;
use App\Domain\Verification\Fiscal\FiscalIntakeService;
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

        if (!in_array($application->status, [PartnerApplication::STATUS_DRAFT, PartnerApplication::STATUS_UNDER_REVIEW], true)) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Application is not editable.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $track = TrackCatalog::find($application->track);
        $stepIds = array_column($track['steps'] ?? [], 'id');
        if (!in_array($step, $stepIds, true)) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => 'Unknown step.', 'details' => ['fields' => ['step' => ['unknown_step']]], 'request_id' => $this->requestId($request)],
            ], 422);
        }

        $evidence = $application->evidence ?? [];
        $evidence[$step] = $request->json('value');

        // Evidence cap: check size BEFORE field validation so oversized payloads fail fast with 413.
        if (strlen((string) json_encode($evidence)) > 65536) {
            return new JsonResponse([
                'error' => ['code' => 'payload_too_large', 'message' => 'Draft evidence exceeds 64KB; move large payloads to documents.', 'request_id' => $this->requestId($request)],
            ], 413);
        }

        $fieldErrors = StepValidators::validate($step, $request->json('value'));
        if ($fieldErrors !== []) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => 'Invalid step data.', 'details' => ['fields' => $fieldErrors], 'request_id' => $this->requestId($request)],
            ], 422);
        }

        $storedValue = $request->json('value');
        if ($step === 'business' && is_array($storedValue)) {
            $storedValue = $this->sanitizeAndEncryptBusinessStep($application, $storedValue);
        }
        $evidence[$step] = $storedValue;

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
                'documents' => $documents->map(static fn(ApplicationDocument $document): array => [
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
        $realPath = (string) $file->getRealPath();

        // T-TR01-03: Magic-byte & polyglot sniffing before storing bytes
        $sniff = (new MagicByteValidator())->inspect($realPath, $file->getMimeType());
        if (!$sniff['valid']) {
            return new JsonResponse([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => 'Uploaded file failed binary signature verification.',
                    'details' => ['fields' => ['file' => [$sniff['reason_code'] ?? 'invalid_file_signature']]],
                    'request_id' => $this->requestId($request),
                ],
            ], 422);
        }

        $sha256 = hash_file('sha256', $realPath);
        $key = "onboarding/{$application->getKey()}/{$validated['kind']}/{$sha256}";
        Storage::disk((string) config('filesystems.default', 'local'))->put($key, (string) file_get_contents($realPath));

        $document = new ApplicationDocument();
        $document->forceFill([
            'application_id' => (string) $application->getKey(),
            'kind' => $validated['kind'],
            'status' => ApplicationDocument::STATUS_PENDING,
            'object_key' => $key,
            'sha256' => $sha256,
            'integrity_state' => ApplicationDocument::INTEGRITY_VERIFIED,
            'integrity_last_checked_at' => now(),
            'quarantine_status' => ApplicationDocument::QUARANTINE_CLEAN,
        ]);
        $document->save();

        return new JsonResponse([
            'data' => [
                'id' => (string) $document->getKey(),
                'object' => 'application_document',
                'kind' => $document->kind,
                'status' => $document->status,
                'integrity_state' => $document->integrity_state,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    /**
     * Route plaintext fiscal keys (gstin, bankAccount, ifsc) into encrypted storage tables,
     * leaving only masked tails and opaque IDs inside partner_applications.evidence (T-10).
     *
     * @param  array<string, mixed>  $business
     * @return array<string, mixed>
     */
    private function sanitizeAndEncryptBusinessStep(PartnerApplication $application, array $business): array
    {
        $intake = app(FiscalIntakeService::class);
        $subjectType = match ($application->track) {
            'temple', 'temple-trust' => 'temple',
            'vendor', 'partner-vendor' => 'vendor',
            default => 'pandit',
        };
        $declaredName = (string) ($application->evidence['identity']['name'] ?? $business['legal_name'] ?? 'Partner');
        $gstStatus = (string) ($business['gst_status'] ?? $business['gstStatus'] ?? 'registered');

        $rawGstin = isset($business['gstin']) && is_string($business['gstin']) ? trim($business['gstin']) : null;
        if ($rawGstin !== null && $rawGstin !== '' || in_array($gstStatus, ['exempt_below_threshold', 'unregistered_individual'], true)) {
            $tax = $intake->recordTaxRegistration(
                tenantId: (string) $application->tenant_id,
                subjectType: $subjectType,
                subjectId: (string) $application->identity_id,
                gstStatus: $gstStatus,
                rawGstin: $rawGstin,
                legalName: is_string($business['legal_name'] ?? null) ? $business['legal_name'] : $declaredName,
                applicationId: (string) $application->getKey(),
                actorId: (string) $application->identity_id,
            );
            $business['tax_registration_id'] = (string) $tax['registration']->getKey();
            if ($tax['registration']->gstin_masked !== null) {
                $business['gstin_masked'] = $tax['registration']->gstin_masked;
            }
        }

        $rawBank = $business['bankAccount'] ?? $business['bank_account'] ?? null;
        if (is_string($rawBank) && trim($rawBank) !== '') {
            $cleanDigits = preg_replace('/\s+/', '', trim($rawBank)) ?? '';
            $rawIfsc = is_string($business['ifsc'] ?? null) && trim($business['ifsc']) !== ''
                ? trim($business['ifsc'])
                : 'SBIN0000001';
            if (preg_match('/^[0-9]{9,18}$/', $cleanDigits) === 1) {
                $bankRes = $intake->recordBankInstrument(
                    tenantId: (string) $application->tenant_id,
                    subjectType: $subjectType,
                    subjectId: (string) $application->identity_id,
                    accountNumber: $cleanDigits,
                    ifsc: $rawIfsc,
                    accountHolderName: is_string($business['account_holder_name'] ?? null) ? $business['account_holder_name'] : $declaredName,
                    declaredEntityName: $declaredName,
                    accountType: is_string($business['account_type'] ?? null) ? $business['account_type'] : 'individual',
                    applicationId: (string) $application->getKey(),
                );
                $business['bank_instrument_id'] = (string) $bankRes['instrument']->getKey();
                $business['account_last4'] = $bankRes['instrument']->account_last4;
            } else {
                $business['account_last4'] = substr($cleanDigits, -4);
            }
        }

        unset($business['gstin'], $business['bankAccount'], $business['bank_account'], $business['ifsc']);

        return $business;
    }
}
