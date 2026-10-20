<?php

namespace App\Platform\Identity\Federation\Http\Controllers;

use App\Models\User;
use App\Platform\Identity\Federation\AuthEventRecorder;
use App\Platform\Identity\MfaLockedException;
use App\Platform\Identity\MfaService;
use App\Platform\Identity\Otp\OtpChallengeManager;
use App\Platform\Identity\Otp\OtpDeliveryPolicy;
use App\Platform\Identity\Otp\OtpProviderRateLimitedException;
use App\Platform\Identity\Otp\OtpProviderUnavailableException;
use App\Platform\Identity\Otp\OtpRateLimitedException;
use App\Platform\Identity\Otp\OtpVerifyStatus;
use App\Platform\Identity\Otp\StoredOtpChallenge;
use App\Platform\Identity\SessionTerminator;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Unified login at the auth subdomain (ADR-0020 D1): one ceremony, no
 * signup route — a verified possession proof auto-provisions the
 * account (`fed_*` external id, deliberately NO tenant membership: D1
 * keeps membership a business concern, bound later at the customer BFF
 * cutover).
 *
 * Phone OTP is primary (MSG91 widget: the runtime never sees the code —
 * it verifies the widget's JWT server-side), email+password second; both
 * can park mid-ceremony for TOTP MFA (202 mfa_required) and both land in
 * the same place: a web-guard session on the auth host, from which
 * Passport's authorize/consent resumes the OAuth flow (intended URL).
 *
 * Enforcement lives where it always did: OtpChallengeManager owns the
 * delivery policy (resend schedule, caps, single-use) under the sentinel
 * tenant; MfaService owns the second factor; AuthEventRecorder
 * hash-chains every allow/deny decision (D5). No identity oracle: unknown
 * vs wrong vs exhausted collapse into one 401 shape; accounts live in
 * the database, never at MSG91.
 */
final class FederationLoginController
{
    /**
     * Federation challenges ride one sentinel tenant id: the auth host
     * is the identity issuer, deliberately NOT a tenant surface (D1),
     * and OtpChallengeStore keys rows by (tenant, purpose, identifier).
     * The sentinel isolates federation rows from every customer-tenant
     * row; the dedicated purpose isolates them from every other
     * ceremony. No `tenants` row carries this id, so the store's tenant
     * row-lock is a no-op here — verify/consume still serialize on the
     * challenge row's own FOR UPDATE lock, and the rolling hourly caps
     * bound abuse regardless.
     */
    private const TENANT = 'federation';

    private const PURPOSE = 'federation_login';

    /** Password-verified, TOTP-pending login parked between requests. */
    private const PENDING_MFA = 'federation.pending_mfa';

    public function __construct(
        private readonly OtpChallengeManager $otp,
        private readonly AuthEventRecorder $audit,
        private readonly SessionTerminator $terminator,
    ) {
    }

    /**
     * The login page. An already-authenticated browser with a resume
     * target goes straight back to it (the Passport authorize bounce
     * lands here); otherwise the standalone surface renders with the
     * validated `next` carried into the JS ceremony.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $next = $this->nextTarget($request);
        $user = Auth::guard('web')->user();

        // A-SEC-004-5: a parked max_age re-auth challenge suspends the
        // signed-in shortcut AND the signed-in state below — the
        // browser must complete a fresh ceremony, never be bounced
        // straight back to the authorize request that demanded one.
        $reauth = $this->reauthPending($request);

        if ($user !== null && ! $reauth) {
            $request->session()->forget('url.intended');

            if ($next !== null) {
                return redirect()->to($next);
            }
        }

        $policy = OtpDeliveryPolicy::load();

        return view('federation.login', [
            'next' => $next,
            'user' => ($user === null || $reauth) ? null : ['name' => (string) $user->name],
            // Public-by-design widget credentials (MSG91 serves them in
            // every page that renders the widget), plus the delivery
            // policy numbers so the UI countdown never drifts from what
            // the manager will enforce.
            'widget' => [
                'widgetId' => (string) config('services.msg91.widget_id', ''),
                'tokenAuth' => (string) config('services.msg91.widget_token_auth', ''),
            ],
            'otp' => [
                'digits' => 6,
                'expiresInSeconds' => $policy->ttlSeconds,
            ],
            'resend' => [
                'max' => $policy->resendMax,
                'delaysSeconds' => $policy->resendDelays,
                'freshRetryAfterSeconds' => $policy->freshRetryAfterSeconds,
            ],
        ]);
    }

    /**
     * Whoami for a session established here — the default landing when
     * a ceremony completes without a resume target.
     */
    public function session(Request $request): JsonResponse
    {
        $user = Auth::guard('web')->user();

        return new JsonResponse([
            'data' => [
                'object' => 'federation_session',
                'authenticated' => $user !== null,
                'user' => $user === null ? null : [
                    'id' => (string) $user->external_id,
                    'name' => (string) $user->name,
                ],
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * Issue an OTP challenge. No existence pre-check by design (D1): the
     * verified identifier auto-provisions on redeem, so an unknown
     * number is not an error here — and no oracle leaks from this side
     * either way. Policy rejections keep the customer shapes: 429 +
     * Retry-After for schedule/cap limits, 503 when the provider is
     * down (typed 503 hides the provider head; report() keeps it).
     */
    public function requestOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:64'],
        ]);

        try {
            $challenge = $this->otp->issue(
                self::TENANT,
                self::PURPOSE,
                (string) $validated['phone'],
                $this->now(),
            );
        } catch (OtpRateLimitedException $e) {
            return $this->rateLimited($request, $e);
        } catch (OtpProviderRateLimitedException $e) {
            report($e);

            return $this->providerRateLimited($request);
        } catch (OtpProviderUnavailableException $e) {
            report($e);

            return $this->dependencyUnavailable($request);
        }

        return new JsonResponse([
            'data' => [
                'object' => 'otp_challenge',
                'challenge_id' => (string) $challenge->id,
                'expires_in' => OtpDeliveryPolicy::load()->ttlSeconds,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 202);
    }

    /**
     * Server-gated resend: the manager validates the compounding
     * schedule against the row BEFORE the client may call the widget's
     * retryOtp; the 202 carries the channel code for that call.
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'challenge_id' => ['required', 'string'],
        ]);

        try {
            $result = $this->otp->resend(
                self::TENANT,
                [self::PURPOSE],
                (string) $validated['challenge_id'],
                $this->now(),
            );
        } catch (OtpRateLimitedException $e) {
            return $this->rateLimited($request, $e);
        }

        if ($result === null) {
            return $this->invalidRedeem($request);
        }

        return new JsonResponse([
            'data' => [
                'object' => 'otp_resend',
                'channel' => $result->channel,
                'channels' => $result->channels,
                'resends_left' => $result->resendsLeft,
                'next_allowed_at' => $result->nextAllowedAt?->toIso8601String(),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 202);
    }

    /**
     * Two-phase OTP login. Phase 1 redeems the widget's JWT: verified
     * on a HELD row so an MFA-enrolled account can add its TOTP as
     * phase 2 — same call (totp_code present) or via a 202
     * mfa_required round-trip that keeps the row parked. Non-MFA
     * accounts consume and land the session in one call. Phase 2
     * ({challenge_id, totp_code}) finishes the held row; the second
     * factor there is mandatory — only MFA accounts ever hold a row.
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'challenge_id' => ['required', 'string'],
            'token' => ['sometimes', 'nullable', 'string', 'max:4096'],
            'totp_code' => ['sometimes', 'nullable', 'string', 'max:12'],
        ]);

        $token = (string) ($validated['token'] ?? '');
        $totp = (string) ($validated['totp_code'] ?? '');

        if ($token === '' && $totp === '') {
            return $this->invalidRedeem($request);
        }

        if ($token !== '') {
            try {
                $result = $this->otp->verify(
                    self::TENANT,
                    [self::PURPOSE],
                    (string) $validated['challenge_id'],
                    $token,
                    $this->now(),
                    holdOnMatch: true,
                );
            } catch (OtpProviderUnavailableException $e) {
                // Outages never burn attempts (the bump rolls back) and
                // never look like a wrong code: typed 503, head logged.
                report($e);

                return $this->dependencyUnavailable($request);
            }

            if ($result->status !== OtpVerifyStatus::Held) {
                // Rejections are already hash-chained by the
                // OtpChallengeRejected listener (D5).
                return $this->invalidRedeem($request);
            }

            $user = $this->findOrCreateUser((string) $result->challenge->identifier);
            if ($user === null) {
                return $this->invalidRedeem($request);
            }

            if ((bool) $user->mfa_enabled) {
                if ($totp === '') {
                    // Phase boundary: the row stays parked at
                    // token_verified; the second submit consumes it.
                    return $this->mfaRequired($request, (string) $validated['challenge_id']);
                }

                $gate = $this->totpGate($request, $user, $totp);
                if ($gate instanceof JsonResponse) {
                    // Wrong code: the row stays held so the next submit
                    // can retry the TOTP within the hold window.
                    return $gate;
                }
            }

            if (! $this->otp->consumeVerified(self::TENANT, [self::PURPOSE], (string) $validated['challenge_id'], $this->now()) instanceof StoredOtpChallenge) {
                return $this->invalidRedeem($request);
            }

            return $this->finish($request, $user, $this->methods($user, 'otp'));
        }

        // Phase 2: second factor only ({challenge_id, totp_code}) on a held row.
        $challenge = $this->otp->findVerified(self::TENANT, [self::PURPOSE], (string) $validated['challenge_id'], $this->now());
        if (! $challenge instanceof StoredOtpChallenge) {
            return $this->invalidRedeem($request);
        }

        $user = $this->findUser((string) $challenge->identifier);
        if ($user === null || ! $user->isActive()) {
            return $this->invalidRedeem($request);
        }

        $gate = $this->totpGate($request, $user, $totp);
        if ($gate instanceof JsonResponse) {
            return $gate;
        }

        if (! $this->otp->consumeVerified(self::TENANT, [self::PURPOSE], (string) $validated['challenge_id'], $this->now()) instanceof StoredOtpChallenge) {
            return $this->invalidRedeem($request);
        }

        return $this->finish($request, $user, $this->methods($user, 'otp'));
    }

    /**
     * Email+password path. MFA-enrolled accounts park the
     * password-verified identity in the session BEFORE the TOTP runs — a
     * wrong first submit must not strand the client without a pending
     * state to retry from — and no session mints until the second
     * factor passes (POST /auth/login/totp).
     */
    public function password(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string', 'max:200'],
            'totp_code' => ['sometimes', 'nullable', 'string', 'max:12'],
        ]);

        $totp = (string) ($validated['totp_code'] ?? '');
        $user = User::query()
            ->where('email', strtolower(trim((string) $validated['email'])))
            ->first();

        // Timing: hash-check against a fixed cost-10 placeholder when no
        // account matched, so unknown-email and wrong-password submits
        // cost the same — no account-existence oracle via response time.
        $valid = Hash::check(
            (string) $validated['password'],
            // Laravel's well-known bcrypt of "password" — a public constant used
            // only to equalise timing, never a credential.
            // nosemgrep: generic.secrets.security.detected-bcrypt-hash.detected-bcrypt-hash
            (string) ($user->password ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
        ) && $user !== null && $user->isActive();

        if (! $valid) {
            $this->audit->record([
                'event_type' => 'session.login',
                'decision' => 'deny',
                'subject_id' => $user === null ? null : (string) $user->getAuthIdentifier(),
                'source_audience' => 'federation',
                'risk_tier' => 'T0',
                'reason_code' => 'invalid_credentials',
            ]);

            return $this->invalidCredentials($request);
        }

        if ((bool) $user->mfa_enabled) {
            $request->session()->put(self::PENDING_MFA, [
                'user_id' => (string) $user->getAuthIdentifier(),
            ]);

            if ($totp === '') {
                return new JsonResponse([
                    'data' => [
                        'object' => 'login_challenge',
                        'status' => 'mfa_required',
                        'next_action' => 'complete_mfa',
                        'methods' => ['totp'],
                    ],
                    'meta' => ['request_id' => $this->requestId($request)],
                ], 202);
            }

            $gate = $this->totpGate($request, $user, $totp);
            if ($gate instanceof JsonResponse) {
                // Pending stays — retry via /auth/login/totp.
                return $gate;
            }
        }

        $request->session()->forget(self::PENDING_MFA);

        return $this->finish($request, $user, $this->methods($user, 'password'));
    }

    /**
     * Second factor for a password-parked login: the pending identity
     * lives server-side in the session, so a stolen challenge id is
     * useless here and the TOTP alone cannot land a session.
     */
    public function totp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'totp_code' => ['required', 'string', 'max:12'],
        ]);

        $pending = $request->hasSession() ? $request->session()->get(self::PENDING_MFA) : null;
        $userId = is_array($pending) ? ($pending['user_id'] ?? null) : null;

        if (! is_string($userId) || $userId === '') {
            return $this->invalidRedeem($request);
        }

        $user = User::query()->find($userId);
        if ($user === null || ! $user->isActive()) {
            return $this->invalidRedeem($request);
        }

        $gate = $this->totpGate($request, $user, (string) $validated['totp_code']);
        if ($gate instanceof JsonResponse) {
            return $gate; // pending stays — retry within the hold window
        }

        $request->session()->forget(self::PENDING_MFA);

        return $this->finish($request, $user, $this->methods($user, 'password'));
    }

    /**
     * End the web session server-side (invalidate, not just discard the
     * cookie) with a hash-chained session.logout — through the shared
     * SessionTerminator (A-SEC-004-5) so app logout and RP-initiated
     * logout record the same chain shape and propagate back-channel
     * notifications identically.
     */
    public function destroy(Request $request): JsonResponse
    {
        $this->terminator->terminate($request, 'app_logout');

        return new JsonResponse([
            'data' => ['object' => 'federation_session', 'signed_out' => true],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * The one landing: web session on the auth host + hash-chained
     * session.login allow + the resume target (validated `next`, else
     * the Passport bounce's intended URL) handed back for the browser
     * to follow. A null redirect means "no resume target": the page
     * swaps to its signed-in state.
     *
     * @param  list<string>  $methods
     */
    private function finish(Request $request, User $user, array $methods): JsonResponse
    {
        Auth::guard('web')->login($user);

        // Session fixation: rotate the id the moment identity lands on it.
        $request->session()->regenerate();

        // 9a (ADR-0021): OIDC ceremony facts — stashed AFTER the id
        // rotation so they bind to the post-fixation session, and read
        // by the authorize request when minting an ID token (auth_time
        // = this instant, amr in IANA registry values, acr in the same
        // aal vocabulary as the audit chain, sid a fresh ULID — never
        // the raw session id).
        $request->session()->put('oidc.ceremony', [
            'auth_time' => time(),
            'amr' => $this->amrFor($methods),
            'acr' => in_array('totp', $methods, true) ? 'aal2' : 'aal1',
            'sid' => (string) Str::ulid(),
        ]);

        // A-SEC-004-5: this fresh ceremony IS the re-authentication a
        // parked max_age challenge demanded — discharge it (the fresh
        // auth_time above is what the next authorize will check).
        $request->session()->forget('oidc.reauth');

        $redirect = $this->nextTarget($request);
        $request->session()->forget('url.intended');

        $this->audit->record([
            'event_type' => 'session.login',
            'decision' => 'allow',
            'subject_id' => (string) $user->getAuthIdentifier(),
            'session_id' => $request->session()->getId(),
            'authentication_methods' => $methods,
            'authentication_assurance' => in_array('totp', $methods, true) ? 'aal2' : 'aal1',
            'source_audience' => 'federation',
            'risk_tier' => 'T0',
            'reason_code' => 'credentials_verified',
        ]);

        return new JsonResponse([
            'data' => [
                'object' => 'federation_session',
                'authenticated' => true,
                'redirect' => $redirect,
                'actor' => ['user_id' => (string) $user->external_id],
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * Resolve the account behind a VERIFIED identifier: exact phone/
     * email match, then a bounded set of formatting variants (the same
     * number typed +91…, 91…, 0… or bare must never provision twice —
     * users.phone keeps whatever shape signup stored, so the query is a
     * candidate set, never a canonical-form scan). Absent → auto-provision
     * on verified proof (D1: possession IS the proof, no signup step).
     * Present but inactive → null: suspended accounts never land a
     * session on any path.
     */
    private function findOrCreateUser(string $identifier): ?User
    {
        $user = $this->findUser($identifier);

        if ($user !== null) {
            return $user->isActive() ? $user : null;
        }

        $isEmail = str_contains($identifier, '@');
        $user = new User();
        $user->forceFill([
            // The verified identifier is all we know — the name starts
            // as it and the profile is enriched later on the business
            // surface. No tenant membership is created here (D1).
            'name' => $identifier,
            'email' => $isEmail
                ? strtolower(trim($identifier))
                : preg_replace('/[^0-9]/', '', $identifier).'@phone.local',
            'phone' => $isEmail ? null : $identifier,
            'phone_verified_at' => $isEmail ? null : $this->now()->toDateTimeString(),
            'email_verified_at' => $isEmail ? $this->now()->toDateTimeString() : null,
            'external_id' => 'fed_'.strtolower((string) Str::ulid()),
        ]);
        // Discarded placeholder password at cost 10 — same reasoning as
        // customer signup: OTP-provisioned accounts have no password
        // path, and the `hashed` cast would reject a raw stronger hash
        // (written RAW for exactly that reason).
        $user->setRawAttributes([
            ...$user->getAttributes(),
            'password' => bcrypt(Str::random(32), ['rounds' => 10]),
        ]);

        try {
            $user->save();
        } catch (\Illuminate\Database\QueryException) {
            // Two concurrent first logins on one identifier: the loser
            // of the unique-key race adopts the winner's row instead of
            // failing the ceremony.
            $user = $this->findUser($identifier);

            if ($user === null || ! $user->isActive()) {
                return null;
            }

            return $user;
        }

        $this->audit->record([
            'event_type' => 'account.provisioned',
            'decision' => 'allow',
            'subject_id' => (string) $user->getAuthIdentifier(),
            'source_audience' => 'federation',
            'risk_tier' => 'T0',
            'reason_code' => 'verified_identifier_auto_provision',
        ]);

        return $user;
    }

    private function findUser(string $identity): ?User
    {
        $user = User::query()
            ->where('phone', $identity)
            ->orWhere('email', $identity)
            ->first();

        if ($user !== null || str_contains($identity, '@')) {
            return $user;
        }

        $variants = self::phoneVariants($identity);
        if (count($variants) === 1) {
            return null;
        }

        return User::query()->whereIn('phone', $variants)->first();
    }

    /**
     * Bounded formatting-variant set for one number: national (10-digit)
     * forms and their 91/+91/0 spellings. Anything longer than 15 digits
     * is left alone — never a table scan.
     *
     * @return list<string>
     */
    private static function phoneVariants(string $identity): array
    {
        $digits = (string) preg_replace('/\D+/', '', $identity);
        if ($digits === '' || strlen($digits) > 15) {
            return [$identity];
        }

        $variants = [$identity, $digits, '+'.$digits];

        if (strlen($digits) === 10) {
            $variants[] = '91'.$digits;
            $variants[] = '+91'.$digits;
            $variants[] = '0'.$digits;
        } elseif (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $national = substr($digits, 2);
            $variants[] = $national;
            $variants[] = '+'.$national;
            $variants[] = '0'.$national;
        }

        return array_values(array_unique($variants));
    }

    /**
     * A-SEC-004-5: is a bounded max_age re-auth challenge parked on
     * this session? Expired flags stop gating (re-authorizing re-arms
     * one), so an abandoned challenge can never lock the login page.
     */
    private function reauthPending(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        $flag = $request->session()->get('oidc.reauth');

        return is_array($flag) && (int) ($flag['until'] ?? 0) > time();
    }

    /**
     * The resume target: explicit `next` (body or query) first, else the
     * URL the framework stashed when an unauthenticated browser bounce
     * (Passport authorize) landed on the login page. Consumed either
     * way — a resume target is single-use.
     */
    private function nextTarget(Request $request): ?string
    {
        $candidate = $request->input('next');

        if (! is_string($candidate) || $candidate === '') {
            $candidate = $request->hasSession()
                ? $request->session()->pull('url.intended')
                : null;
        }

        return $this->safeNext(is_string($candidate) ? $candidate : null);
    }

    /**
     * Open-redirect fence: only this federation host may be a target.
     * Relative single-slash paths pass; protocol-relative (//host),
     * backslash tricks, foreign hosts, foreign schemes (javascript:) and
     * control characters all collapse to null — no Location handed out.
     */
    private function safeNext(?string $candidate): ?string
    {
        if (! is_string($candidate) || $candidate === '' || strlen($candidate) > 4096) {
            return null;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $candidate) === 1) {
            return null;
        }

        $parts = parse_url($candidate);
        if ($parts === false) {
            return null;
        }

        if (isset($parts['scheme']) || isset($parts['host'])) {
            // Absolute URL: only the configured federation host, and it
            // is normalized back to a RELATIVE path so the Location can
            // never carry a foreign authority (user@host, ports, ...).
            $domain = strtolower((string) config('federation.domain', ''));
            $host = strtolower((string) ($parts['host'] ?? ''));

            if ($domain === '' || $host !== $domain) {
                return null;
            }

            $target = (string) ($parts['path'] ?? '/');
            if (isset($parts['query'])) {
                $target .= '?'.$parts['query'];
            }

            return $target;
        }

        // Relative: a single leading slash only — '//…' is a
        // protocol-relative cross-origin URL and browsers normalize
        // '/\' into it.
        if (! str_starts_with($candidate, '/')
            || str_starts_with($candidate, '//')
            || str_contains($candidate, '\\')) {
            return null;
        }

        return $candidate;
    }

    /**
     * @return list<string>
     */
    private function methods(User $user, string $primary): array
    {
        return (bool) $user->mfa_enabled
            ? [$primary, 'totp']
            : [$primary];
    }

    /**
     * IANA `amr` registry values for the ceremony methods (OIDC Core
     * §2): the audit chain keeps this codebase's own method strings;
     * the ID token speaks the registry vocabulary.
     *
     * @param  list<string>  $methods
     * @return list<string>
     */
    private function amrFor(array $methods): array
    {
        $map = ['password' => 'pwd', 'otp' => 'otp', 'totp' => 'mfa'];

        $values = array_values(array_unique(array_map(
            static fn (string $method): string => $map[$method] ?? $method,
            $methods
        )));

        sort($values);

        return $values;
    }

    private function mfaRequired(Request $request, string $challengeId): JsonResponse
    {
        return new JsonResponse([
            'data' => [
                'object' => 'otp_challenge',
                'challenge_id' => $challengeId,
                'status' => 'mfa_required',
                'next_action' => 'complete_mfa',
                'methods' => ['totp'],
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 202);
    }

    /**
     * @return true|JsonResponse
     */
    private function totpGate(Request $request, User $user, string $totp): JsonResponse|bool
    {
        try {
            $verified = app(MfaService::class)->verify($user, $totp, time());
        } catch (MfaLockedException $locked) {
            return new JsonResponse([
                'error' => ['code' => 'rate_limited', 'message' => 'Too many failed attempts. Try again later.', 'request_id' => $this->requestId($request)],
            ], 429)->header('Retry-After', (string) $locked->retryAfter);
        }

        if (! $verified) {
            // A failed second factor is a denied login decision (D5) —
            // the OTP events do not cover it, so the chain records it
            // here.
            $this->audit->record([
                'event_type' => 'session.login',
                'decision' => 'deny',
                'subject_id' => (string) $user->getAuthIdentifier(),
                'source_audience' => 'federation',
                'risk_tier' => 'T0',
                'reason_code' => 'mfa_failed',
            ]);

            return $this->invalidRedeem($request);
        }

        return true;
    }

    /**
     * Typed failure envelopes (customer-auth shapes): a dependency
     * outage is a 503, a provider back-off a 429 — neither is ever
     * folded into the uniform 401 set.
     */
    private function dependencyUnavailable(Request $request): JsonResponse
    {
        return new JsonResponse([
            'error' => ['code' => 'dependency_unavailable', 'message' => 'Verification is temporarily unavailable. Try again shortly.', 'request_id' => $this->requestId($request)],
        ], 503);
    }

    private function providerRateLimited(Request $request): JsonResponse
    {
        return new JsonResponse([
            'error' => ['code' => 'rate_limited', 'message' => 'Too many attempts.', 'request_id' => $this->requestId($request)],
        ], 429);
    }

    /**
     * Policy-shaped rate limit (resend schedule, fresh gate, hourly
     * cap): 429 with the manager's exact Retry-After so client
     * countdowns match the server window.
     */
    private function rateLimited(Request $request, OtpRateLimitedException $e): JsonResponse
    {
        $response = new JsonResponse([
            'error' => ['code' => 'rate_limited', 'message' => 'Too many attempts. Retry later.', 'request_id' => $this->requestId($request)],
        ], 429);

        if ($e->retryAfterSeconds !== null && $e->retryAfterSeconds > 0) {
            $response->header('Retry-After', (string) $e->retryAfterSeconds);
        }

        return $response;
    }

    /**
     * The single 401 every credential-shaped failure shares: unknown/
     * foreign/expired/exhausted/superseded challenge, token MSG91
     * rejected, held row gone, TOTP wrong — indistinguishable by design
     * (no oracle).
     */
    private function invalidRedeem(Request $request): JsonResponse
    {
        return new JsonResponse([
            'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
        ], 401);
    }

    private function invalidCredentials(Request $request): JsonResponse
    {
        return new JsonResponse([
            'error' => ['code' => 'unauthenticated', 'message' => 'Invalid email or password.', 'request_id' => $this->requestId($request)],
        ], 401);
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::parse(gmdate('Y-m-d\TH:i:s.v\Z'));
    }
}
