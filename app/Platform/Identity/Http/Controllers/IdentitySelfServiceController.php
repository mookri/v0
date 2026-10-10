<?php

namespace App\Platform\Identity\Http\Controllers;

use App\Platform\Api\WebMutations;
use App\Platform\Identity\Models\ActorSession;
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
        $externalId = (string) $user->external_id;

        // The "current" session is the one whose actor_sessions.id matches
        // the actor_sessions.id the caller presented (bearer path) or the
        // web session id (cookie path). We resolve the presented actor session
        // id from the request attributes (AuthenticateAny stamps it).
        $presented = $request->attributes->get('actor');
        $presentedId = is_array($presented) && is_string($presented['session_id'] ?? null)
            ? $presented['session_id']
            : ($request->hasSession() ? $request->session()->getId() : null);

        $rows = ActorSession::query()
            ->where('user_id', $externalId)
            ->whereNull('revoked_at')
            ->orderByDesc('issued_at')
            ->get();

        return new JsonResponse([
            'data' => $rows->map(static fn ($row): array => [
                'id' => $row->id,
                'object' => 'user_session',
                'current' => $row->id === $presentedId,
                'ip_address' => $row->ip_address,
                'user_agent' => $row->user_agent ? mb_substr($row->user_agent, 0, 120) : null,
                'label' => $row->label,
                'issued_at' => $row->issued_at?->toIso8601String(),
                'expires_at' => $row->expires_at?->toIso8601String(),
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

        // M5/TOKEN-001: "revoke others" also revokes every HMAC actor
        // session (bearer jtis) — the web-session count above stays the
        // response's only number, but the bearer half dies with it.
        // The session the caller PRESENTED is spared: on the bearer
        // path (BFF) AuthenticateAny stamps the verified actor onto the
        // request (same attribute logout reads), so excluding it keeps
        // "others" honest instead of logging the caller out of its own
        // request (F-SMOKE3). The cookie path presents no actor session
        // (session user wins auth precedence), so every actor session
        // there belongs to another device and the full sweep stands.
        $externalId = (string) $user->external_id;
        if ($externalId !== '') {
            $presented = $request->attributes->get('actor');
            $presentedId = is_array($presented) && is_string($presented['session_id'] ?? null)
                ? $presented['session_id']
                : null;

            if ($presentedId !== null) {
                ActorSession::query()
                    ->where('user_id', $externalId)
                    ->whereNull('revoked_at')
                    ->where('id', '!=', $presentedId)
                    ->update(['revoked_at' => gmdate('Y-m-d H:i:s')]);
            } else {
                ActorSession::revokeForUser($externalId);
            }
        }

        return new JsonResponse([
            'data' => ['object' => 'session_revocation', 'revoked' => $revoked],
            'meta' => ['request_id' => (string) $request->attributes->get('request_id', '')],
        ]);
    }

    /**
     * I1: revoke a specific actor session by id (selective revoke-by-id).
     * The caller must own the session (matching external_id). The presented
     * session cannot be revoked here — use logout for that.
     */
    public function revokeSession(Request $request, string $id): JsonResponse|RedirectResponse
    {
        return WebMutations::negotiate(
            $request,
            fn (): JsonResponse => $this->revokeSessionJson($request, $id),
            static function (JsonResponse $result): string {
                return 'Session revoked.';
            },
        );
    }

    private function revokeSessionJson(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $externalId = (string) $user->external_id;

        // Find the session and verify ownership
        $session = Models\ActorSession::query()
            ->where('id', $id)
            ->where('user_id', $externalId)
            ->whereNull('revoked_at')
            ->first();

        if ($session === null) {
            return new JsonResponse([
                'error' => [
                    'code' => 'not_found',
                    'message' => 'Session not found or already revoked.',
                    'request_id' => (string) $request->attributes->get('request_id', ''),
                ],
            ], 404);
        }

        // Prevent revoking the currently presented session — use logout for that
        $presented = $request->attributes->get('actor');
        $presentedId = is_array($presented) && is_string($presented['session_id'] ?? null)
            ? $presented['session_id']
            : null;

        if ($presentedId !== null && $session->id === $presentedId) {
            return new JsonResponse([
                'error' => [
                    'code' => 'cannot_revoke_current',
                    'message' => 'Cannot revoke the active session. Use logout instead.',
                    'request_id' => (string) $request->attributes->get('request_id', ''),
                ],
            ], 422);
        }

        $session->revoked_at = gmdate('Y-m-d H:i:s');
        $session->save();

        return new JsonResponse([
            'data' => ['object' => 'session_revocation', 'revoked' => 1],
            'meta' => ['request_id' => (string) $request->attributes->get('request_id', '')],
        ]);
    }
}
