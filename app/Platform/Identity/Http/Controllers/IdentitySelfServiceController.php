<?php

namespace App\Platform\Identity\Http\Controllers;

use App\Platform\Api\WebMutations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Identity self-service (api-platform §platform surfaces): own profile
 * read/update and web-session inventory with remote revoke.
 *
 * Token-platform sessions (/mfa, OAuth clients) land with the token
 * program; this surface covers database web sessions only. Unknown
 * request fields are rejected (api-platform §3.1 strict validation).
 */
final class IdentitySelfServiceController
{
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return new JsonResponse([
            'data' => [
                'id' => $user->getAuthIdentifier(),
                'object' => 'user_profile',
                'name' => $user->name,
                'email' => $user->email,
                'email_verified' => $user->email_verified_at !== null,
            ],
            'meta' => ['request_id' => (string) $request->attributes->get('request_id', '')],
        ]);
    }

    // Shared actions (doc 06 §7.1-7.2): the security page's forms and
    // /api/v1/identity point here — web posts redirect back with a coded
    // flash, API envelopes stay byte-identical.
    public function updateProfile(Request $request): JsonResponse|RedirectResponse
    {
        return WebMutations::negotiate(
            $request,
            fn (): JsonResponse => $this->updateProfileJson($request),
            'Profile updated.',
        );
    }

    private function updateProfileJson(Request $request): JsonResponse
    {
        $allowed = ['name'];
        // `_token` is web CSRF bookkeeping, not a client field: it never
        // travels on /api/v1 (bearer posture) and must not read as an
        // unknown field when the security page posts its form.
        $unknown = array_diff(array_keys($request->except(['_token'])), $allowed);
        if ($unknown !== []) {
            return new JsonResponse([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => 'Unknown fields: '.implode(', ', $unknown).'.',
                    'details' => ['fields' => array_fill_keys($unknown, ['unknown_field'])],
                    'request_id' => (string) $request->attributes->get('request_id', ''),
                ],
            ], 422);
        }

        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);

        $user = $request->user();
        $user->forceFill(['name' => $validated['name']])->save();

        return new JsonResponse([
            'data' => [
                'id' => $user->getAuthIdentifier(),
                'object' => 'user_profile',
                'name' => $user->name,
                'email' => $user->email,
                'email_verified' => $user->email_verified_at !== null,
            ],
            'meta' => ['request_id' => (string) $request->attributes->get('request_id', '')],
        ]);
    }

    public function sessions(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentId = $request->hasSession() ? $request->session()->getId() : null;
        $rows = DB::connection(config('database.default'))->table('sessions')
            ->where('user_id', $user->getAuthIdentifier())
            ->orderByDesc('last_activity')
            ->get();

        return new JsonResponse([
            'data' => $rows->map(static fn ($row): array => [
                'id' => $row->id,
                'object' => 'user_session',
                'current' => $row->id === $currentId,
                'ip_address' => $row->ip_address,
                'user_agent' => $row->user_agent ? mb_substr($row->user_agent, 0, 120) : null,
                'last_activity_at' => gmdate('Y-m-d\TH:i:s.v\Z', (int) $row->last_activity),
            ])->values()->all(),
            'meta' => ['request_id' => (string) $request->attributes->get('request_id', '')],
        ]);
    }

    public function revokeOtherSessions(Request $request): JsonResponse|RedirectResponse
    {
        return WebMutations::negotiate(
            $request,
            fn (): JsonResponse => $this->revokeOtherSessionsJson($request),
            static function (JsonResponse $result): string {
                $revoked = WebMutations::data($result)['revoked'] ?? 0;

                return 'Other sessions revoked: '.(is_int($revoked) ? $revoked : 0);
            },
        );
    }

    private function revokeOtherSessionsJson(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $request->hasSession()) {
            return new JsonResponse([
                'error' => [
                    'code' => 'session_unknown',
                    'message' => 'Current session is unknown; no sessions were revoked.',
                    'request_id' => (string) $request->attributes->get('request_id', ''),
                ],
            ], 400);
        }
        $currentId = $request->session()->getId();

        $revoked = DB::connection(config('database.default'))->table('sessions')
            ->where('user_id', $user->getAuthIdentifier())
            ->where('id', '!=', $currentId)
            ->delete();

        return new JsonResponse([
            'data' => ['object' => 'session_revocation', 'revoked' => $revoked],
            'meta' => ['request_id' => (string) $request->attributes->get('request_id', '')],
        ]);
    }
}
