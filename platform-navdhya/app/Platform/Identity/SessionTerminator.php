<?php

namespace App\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\Federation\AuthEventRecorder;
use App\Platform\Identity\OAuth\Backchannel\BackchannelLogoutDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Passport;

/**
 * OP-session termination (SEC-004 / A-SEC-004-5): the ONE path that
 * ends a federation web session — app logout (FederationLoginController
 * ::destroy) and RP-initiated logout (EndSessionController) share it so
 * audit and back-channel propagation can never diverge between the two
 * exits.
 *
 * Order is deliberate:
 *
 *  1. capture the ceremony facts (sid) and subject BEFORE logout — the
 *     session data is the only place they exist,
 *  2. invalidate the server-side session (the decision itself),
 *  3. record session.logout fail-closed (D5: the chain always gets the
 *     decision, reason_code distinguishing the exits),
 *  4. dispatch back-channel notifications — in a finally so an audit
 *     outage can never silently skip propagation to other RPs.
 */
final class SessionTerminator
{
    public function __construct(
        private readonly AuthEventRecorder $audit,
        private readonly BackchannelLogoutDispatcher $backchannel,
    ) {
    }

    /**
     * @param  'app_logout'|'rp_initiated'  $reasonCode
     * @return array{user: User|null, sid: string|null}
     */
    public function terminate(Request $request, string $reasonCode): array
    {
        $user = Auth::guard('web')->user();
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;

        // Ceremony facts before invalidation: the OP-session sid (never
        // the raw session id) is what RPs know from their ID tokens.
        $ceremony = $request->hasSession() ? $request->session()->get('oidc.ceremony') : null;
        $sid = is_array($ceremony) && is_string($ceremony['sid'] ?? null) && $ceremony['sid'] !== ''
            ? $ceremony['sid']
            : null;

        if ($sid === null && $request->hasSession()) {
            $stored = $request->session()->get('oidc.sid');
            $sid = is_string($stored) && $stored !== '' ? $stored : null;
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // A-SEC-004-6: the session's token lineage dies with it — every
        // access token minted under this sid is revoked (the resource
        // server and introspection refuse it on the next check) and
        // their refresh tokens with them, so neither a live cookie nor
        // a kept refresh token survives the logout. Fail-closed: no
        // catch here on purpose — the session is already gone, and a
        // loud failure beats silently leaving the lineage alive.
        if ($sid !== null) {
            $tokenIds = Passport::token()->newQuery()->where('sid', $sid)->pluck('id');

            if ($tokenIds->isNotEmpty()) {
                Passport::token()->newQuery()->whereIn('id', $tokenIds)->update(['revoked' => true]);
                Passport::refreshToken()->newQuery()->whereIn('access_token_id', $tokenIds)->update(['revoked' => true]);
            }
        }

        if ($user === null) {
            // Guest termination (idempotent logout paths): nothing to
            // audit, nothing bound — no session facts existed.
            return ['user' => null, 'sid' => $sid];
        }

        $sub = $user->external_id !== null && $user->external_id !== ''
            ? (string) $user->external_id
            : (string) $user->getAuthIdentifier();

        try {
            $this->audit->record([
                'event_type' => 'session.logout',
                'decision' => 'allow',
                'subject_id' => (string) $user->getAuthIdentifier(),
                'session_id' => $sessionId,
                'source_audience' => 'federation',
                'risk_tier' => 'T0',
                'reason_code' => $reasonCode,
            ]);
        } finally {
            // Propagation is not conditional on the audit write: every
            // RP bound to this session learns the session ended even if
            // the chain write then fails loudly on the way out.
            if ($sid !== null) {
                $this->backchannel->dispatchSession($sid, $sub);
            }
        }

        return ['user' => $user, 'sid' => $sid];
    }
}
