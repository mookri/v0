<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Modules\Onboarding\Models\PartnerApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Onboarding application intake (stage 1-5 port from the Next.js BFF):
 * version-pinned track start + draft read. Tenant + ownership enforced by
 * middleware + scope; submit/review/decision land next.
 */
final class ApplicationController
{
    private const TRACK_VERSIONS = [
        'pandit' => 'pandit-v1',
        'partner-vendor' => 'partner-vendor-v1',
        'partner-individual' => 'partner-individual-v1',
    ];

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate(['track' => ['required', 'string']]);

        if (! isset(self::TRACK_VERSIONS[$validated['track']])) {
            return new JsonResponse([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => 'Unknown track.',
                    'details' => ['fields' => ['track' => ['unknown_track']]],
                    'request_id' => (string) $request->attributes->get('request_id', ''),
                ],
            ], 422);
        }

        $application = new PartnerApplication();
        $application->forceFill([
            'identity_id' => (string) $request->user()->getAuthIdentifier(),
            'track' => $validated['track'],
            'track_version' => self::TRACK_VERSIONS[$validated['track']],
            'status' => PartnerApplication::STATUS_DRAFT,
        ]);
        $application->save();

        return new JsonResponse([
            'data' => [
                'id' => (string) $application->getKey(),
                'object' => 'partner_application',
                'track' => $application->track,
                'track_version' => $application->track_version,
                'status' => $application->status,
            ],
            'meta' => ['request_id' => (string) $request->attributes->get('request_id', '')],
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $application = PartnerApplication::query()->find($id);

        if ($application === null) {
            return new JsonResponse([
                'error' => [
                    'code' => 'not_found',
                    'message' => 'Application not found.',
                    'request_id' => (string) $request->attributes->get('request_id', ''),
                ],
            ], 404);
        }

        if ((string) $application->identity_id !== (string) $request->user()->getAuthIdentifier()) {
            return new JsonResponse([
                'error' => [
                    'code' => 'permission_missing',
                    'message' => 'Not allowed.',
                    'request_id' => (string) $request->attributes->get('request_id', ''),
                ],
            ], 403);
        }

        return new JsonResponse([
            'data' => [
                'id' => (string) $application->getKey(),
                'object' => 'partner_application',
                'track' => $application->track,
                'track_version' => $application->track_version,
                'status' => $application->status,
            ],
            'meta' => ['request_id' => (string) $request->attributes->get('request_id', '')],
        ]);
    }
}
