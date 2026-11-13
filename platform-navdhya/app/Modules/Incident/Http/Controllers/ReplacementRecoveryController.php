<?php

declare(strict_types=1);

namespace App\Modules\Incident\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Modules\Incident\Application\EmergencyUnavailabilityUseCase;
use App\Modules\Incident\Application\ProposeAndConfirmReplacementUseCase;
use App\Modules\Incident\Models\IncidentReplacement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ceremony Service Recovery & Replacement API Controller (E-TS-06 / TS-13).
 */
final class ReplacementRecoveryController
{
    public function __construct(
        private readonly EmergencyUnavailabilityUseCase $emergency,
        private readonly ProposeAndConfirmReplacementUseCase $replacements,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    public function reportEmergency(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse(['error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)]], 401);
        }

        // Explicitly pass through any forbidden medical fields to test & enforce rejection in UseCase
        $validated = $request->validate([
            'booking_id' => ['required', 'string', 'max:26'],
            'reason_category' => ['required', 'string', 'max:40'],
            'pandit_id' => ['sometimes', 'nullable', 'string', 'max:64'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:500'],
            'diagnosis' => ['sometimes', 'nullable', 'string'],
            'medical_certificate' => ['sometimes', 'nullable', 'string'],
        ]);

        try {
            $incident = $this->emergency->execute([
                ...$validated,
                'actor_id' => (string) $user->getAuthIdentifier(),
            ]);
        } catch (\InvalidArgumentException $e) {
            $code = str_contains($e->getMessage(), 'privacy_medical_forbidden') ? 'privacy_medical_forbidden' : 'validation_failed';

            return new JsonResponse(['error' => ['code' => $code, 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)]], 422);
        }

        return new JsonResponse([
            'data' => [
                'incident_id' => (string) $incident->getKey(),
                'incident_reference' => $incident->incident_reference,
                'type' => $incident->type,
                'attributed_cause' => $incident->attributed_cause,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function propose(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => ['required', 'string', 'max:26'],
            'preferred_tradition' => ['sometimes', 'nullable', 'string', 'max:64'],
            'preferred_language' => ['sometimes', 'nullable', 'string', 'max:64'],
        ]);

        $proposals = $this->replacements->propose(
            $id,
            $validated['booking_id'],
            $validated['preferred_tradition'] ?? null,
            $validated['preferred_language'] ?? null,
        );

        return new JsonResponse([
            'data' => array_map(fn (IncidentReplacement $r): array => $this->serialize($r), $proposals),
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function reserve(Request $request, string $replacementId): JsonResponse
    {
        try {
            $reserved = $this->replacements->reserveHold($replacementId, (int) $request->input('hold_minutes', 15));
        } catch (\OverflowException $e) {
            return new JsonResponse(['error' => ['code' => 'concurrent_hold_conflict', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)]], 409);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => ['code' => 'no_candidate', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)]], 422);
        }

        return new JsonResponse([
            'data' => $this->serialize($reserved),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function confirm(Request $request, string $replacementId): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'devotee_consented' => ['required', 'boolean'],
            'compensation_minor' => ['sometimes', 'integer', 'min:0'],
        ]);

        try {
            $confirmed = $this->replacements->confirmWithDevoteeConsent(
                $replacementId,
                (string) ($user?->getAuthIdentifier() ?? 'system'),
                (bool) $validated['devotee_consented'],
                (int) ($validated['compensation_minor'] ?? 0),
            );
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => ['code' => 'devotee_consent_required', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)]], 422);
        }

        return new JsonResponse([
            'data' => $this->serialize($confirmed),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(IncidentReplacement $r): array
    {
        return [
            'id' => (string) $r->getKey(),
            'incident_id' => $r->incident_id,
            'booking_id' => $r->booking_id,
            'candidate_pandit_id' => $r->candidate_pandit_id,
            'score' => $r->score,
            'eligibility_gates_passed' => $r->eligibility_gates_passed,
            'gate_disclosure_status' => $r->gate_disclosure_status,
            'status' => $r->status,
            'fallback_action' => $r->fallback_action,
            'offer_expires_at' => $r->offer_expires_at?->toJSON(),
            'devotee_consented_at' => $r->devotee_consented_at?->toJSON(),
        ];
    }
}
