<?php

namespace App\Platform\Identity;

use App\Platform\Identity\Models\ActorSession;
use Illuminate\Http\Request;

/**
 * Session rotation (I15): every freshly minted actor token invalidates
 * the session the caller presented, and MFA completion (privilege
 * elevation) hands back a rotated replacement. Only a presented, validly
 * signed predecessor belonging to the SAME actor is ever revoked — a
 * foreign bearer can never revoke someone else's session.
 */
final class ActorSessionRotation
{
    /**
     * Revoke the presented predecessor session (login / mint rotation).
     * Returns the revoked session id, or null when nothing valid was
     * presented (public login, web-session step-up, foreign bearer).
     */
    public static function revokePredecessor(Request $request, string $externalUserId): ?string
    {
        $claims = self::presentedClaims($request);

        if ($claims === null || $claims['user_id'] !== $externalUserId) {
            return null;
        }

        ActorSession::revoke($claims['session_id']);

        return $claims['session_id'];
    }

    /**
     * Rotation on MFA completion: revoke the predecessor and mint its
     * replacement in the login envelope shape ({token, expires_at,
     * actor}) so clients adopt it exactly like a login. Null when no
     * predecessor was presented (nothing to rotate).
     *
     * @return array{token: string, expires_at: string, actor: array{user_id: string, session_id: string}}|null
     */
    public static function rotate(Request $request, string $externalUserId): ?array
    {
        $secret = (string) config('identity.hmac_secret', '');

        if ($secret === '') {
            return null;
        }

        $claims = self::presentedClaims($request);

        if ($claims === null || $claims['user_id'] !== $externalUserId) {
            return null;
        }

        ActorSession::revoke($claims['session_id']);

        $minted = ActorTokenIssuer::mint(
            $secret,
            $externalUserId,
            ActorTokenIssuer::TOKEN_TTL_SECONDS,
            gmdate('Y-m-d\TH:i:s.v\Z'),
            [], // no roles carried in rotation — inherits from predecessor
            null,
            null,
            $request->userAgent(),
            $request->ip(),
        );

        return [
            'token' => $minted['token'],
            'expires_at' => $minted['expires_at'],
            'actor' => ['user_id' => $externalUserId, 'session_id' => $minted['session_id']],
        ];
    }

    /**
     * @return array{user_id: string, session_id: string}|null
     */
    private static function presentedClaims(Request $request): ?array
    {
        $header = $request->header('Authorization', '');

        if (! is_string($header) || ! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = substr($header, 7);

        if (substr_count($token, '.') !== 1) {
            // HMAC actor tokens are two segments (payload.signature);
            // anything else — notably three-segment Passport JWTs —
            // rotates through its own revocation store.
            return null;
        }

        $secret = (string) config('identity.hmac_secret', '');
        $claims = ActorTokenVerifier::verify($secret, $token, gmdate('Y-m-d\TH:i:s.v\Z'));

        return $claims === null ? null : [
            'user_id' => $claims['user_id'],
            'session_id' => $claims['session_id'],
        ];
    }
}
