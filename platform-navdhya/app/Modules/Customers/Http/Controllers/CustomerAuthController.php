<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Models\User;
use App\Modules\Customers\CustomerActivityRecorder;
use App\Modules\Customers\Models\BuyerProfile;
use App\Modules\Customers\Models\CustomerOtpChallenge;
use App\Modules\Customers\Models\ReferralAttribution;
use App\Modules\Customers\Models\ReferralCode;
use App\Platform\Audit\AuditWriter;
use App\Platform\Identity\ActorSessionRotation;
use App\Platform\Identity\ActorTokenIssuer;
use App\Platform\Identity\MfaLockedException;
use App\Platform\Identity\MfaService;
use App\Platform\Identity\Models\ActorSession;
use App\Platform\Identity\Otp\Msg91Identifier;
use App\Platform\Identity\Otp\OtpChallengeManager;
use App\Platform\Identity\Otp\OtpDeliveryPolicy;
use App\Platform\Identity\Otp\OtpProviderRateLimitedException;
use App\Platform\Identity\Otp\OtpProviderUnavailableException;
use App\Platform\Identity\Otp\OtpRateLimitedException;
use App\Platform\Identity\Otp\OtpVerifyStatus;
use App\Platform\Identity\Otp\StoredOtpChallenge;
use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Customer authentication: OTP request/resend, OTP-before-account
 * signup, signup verification (201 + HMAC actor token), two-phase
 * login (200 + token), and logout (revokes the presented session
 * server-side, M5/TOKEN-001).
 *
 * otp/request/resend/signup/verify/login are public by design
 * (withoutMiddleware in Routes/api.php + throttle); logout rides the
 * module's auth.any + tenant group middleware.
 *
 * OTP is the shared subsystem (OtpChallengeManager + MSG91 widget):
 * codes are delivered and judged by MSG91 in the browser; the runtime
 * never sees one. The client redeems the widget's JWT access token
 * here, Laravel verifies it server-side against MSG91, and the
 * canonical identifier decides the account. No-oracle rule: unknown
 * identities, wrong/expired/exhausted/replayed tokens and purpose
 * mismatches all collapse to identical 202s (request) or 401s
 * (redeem). Tokens are backend-minted HMAC actor tokens
 * (ActorTokenIssuer, 24 h absolute TTL, interim until Passport) with
 * u = the user's external_id so AuthenticateAny resolves them with
 * zero auth-chain changes.
 *
 * Login is two-phase for MFA-enrolled accounts: the token verifies to
 * a HELD row (status token_verified), answered 202 mfa_required; the
 * TOTP arrives as a second submit that consumes the held row and
 * mints. Non-MFA accounts consume and mint in the same call.
 */
final class CustomerAuthController
{
    public function __construct(
        private readonly OtpChallengeManager $otp,
        private readonly AuditWriter $audit,
        private readonly CustomerActivityRecorder $activity,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::parse(gmdate('Y-m-d\TH:i:s.v\Z'));
    }

    private function activeTenant(Request $request, string $tenantId): Tenant|JsonResponse
    {
        $tenant = Tenant::query()->find($tenantId);

        if ($tenant === null || $tenant->state !== Tenant::STATE_ACTIVE) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Tenant unavailable.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return $tenant;
    }

    private function findUser(string $identity): ?User
    {
        return User::query()
            ->where('phone', $identity)
            ->orWhere('email', $identity)
            ->first();
    }

    private function membership(Request $request, string $tenantId, User $user): ?JsonResponse
    {
        $membership = TenantMembership::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->getAuthIdentifier())
            ->where('state', TenantMembership::STATE_ACTIVE)
            ->first();

        if ($membership === null) {
            return new JsonResponse([
                'error' => ['code' => 'membership_inactive', 'message' => 'This account is deactivated.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        return null;
    }

    /**
     * @return array{tenant: Tenant, ownsContext: bool, context: TenantContext}
     */
    private function resolveTenantScope(Tenant $tenant): array
    {
        $context = app(TenantContext::class);
        $ownsContext = ! $context->isResolved();

        if ($ownsContext) {
            $context->set(
                (string) $tenant->getKey(),
                null,
                null,
                (new ConnectionResolver())->cachedFor($tenant),
                ['system' => 'customer-auth']
            );
        }

        return ['tenant' => $tenant, 'ownsContext' => $ownsContext, 'context' => $context];
    }

    /**
     * @return array{token: string, expires_at: string, session_id: string}|JsonResponse
     */
    private function mintToken(Request $request, User $user): array|JsonResponse
    {
        $secret = (string) config('identity.hmac_secret', '');

        if ($secret === '' || $user->external_id === null || $user->external_id === '') {
            return new JsonResponse([
                'error' => ['code' => 'internal_error', 'message' => 'Sign-in is temporarily unavailable.', 'request_id' => $this->requestId($request)],
            ], 500);
        }

        // M5 / I15 rotation: the moment a new token is minted, the
        // session the caller presented (validly signed, same actor) is
        // revoked — every login/identity change supersedes its
        // predecessor instead of leaving it alive for 24 h.
        ActorSessionRotation::revokePredecessor($request, (string) $user->external_id);

        $minted = ActorTokenIssuer::mint(
            $secret,
            (string) $user->external_id,
            ActorTokenIssuer::TOKEN_TTL_SECONDS,
            gmdate('Y-m-d\TH:i:s.v\Z'),
            ['customer'],
            null,
            null,
            $request->userAgent(),
            $request->ip(),
        );

        return [
            'token' => $minted['token'],
            'expires_at' => $minted['expires_at'],
            'session_id' => $minted['session_id'],
        ];
    }

    /**
     * Public widget config (the only bootstrap a client needs): the
     * MSG91 widget credentials are public-by-design (MSG91 serves them
     * in every page that renders the widget) and the delivery-policy
     * numbers mirror the server so the UI countdown never drifts from
     * what the manager will enforce.
     */
    public function otpConfig(Request $request): JsonResponse
    {
        $policy = OtpDeliveryPolicy::load();

        return new JsonResponse([
            'data' => [
                'object' => 'otp_config',
                'provider' => 'msg91_widget',
                'widget_id' => (string) config('services.msg91.widget_id', ''),
                'token_auth' => (string) config('services.msg91.widget_token_auth', ''),
                'otp' => [
                    'digits' => 6,
                    'expires_in_seconds' => $policy->ttlSeconds,
                ],
                'resend' => [
                    'max' => $policy->resendMax,
                    'delays_seconds' => $policy->resendDelays,
                    'fresh_retry_after_seconds' => $policy->freshRetryAfterSeconds,
                    'channels' => $policy->resendChannels,
                ],
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * Server-gated resend: the manager validates the compounding
     * schedule (60s → 180s → 540s, max 3) against the row BEFORE the
     * client is allowed to call the widget's retryOtp; the 202 carries
     * the channel code for that call. 429 + Retry-After otherwise.
     */
    public function otpResend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            'challenge_id' => ['required', 'string'],
        ]);

        $tenant = $this->activeTenant($request, $validated['tenant_id']);
        if ($tenant instanceof JsonResponse) {
            return $tenant;
        }

        try {
            $result = $this->otp->resend(
                (string) $tenant->getKey(),
                CustomerOtpChallenge::PURPOSES,
                $validated['challenge_id'],
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

    public function requestOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            // verify_*/change_* purposes unlock with their confirm
            // endpoints; only login + signup challenges are issued here.
            'purpose' => ['nullable', 'string', 'in:login,signup'],
            'identity' => ['required_without:phone', 'string', 'max:64'],
            'phone' => ['required_without:identity', 'string', 'max:64'],
        ]);
        $identity = (string) ($validated['identity'] ?? $validated['phone']);
        $purpose = (string) ($validated['purpose'] ?? CustomerOtpChallenge::PURPOSE_LOGIN);

        $tenant = $this->activeTenant($request, $validated['tenant_id']);
        if ($tenant instanceof JsonResponse) {
            return $tenant;
        }
        $tenantId = (string) $tenant->getKey();

        // No oracle: an unknown login identity is issued a challenge
        // against a DECOY identifier, so the delivery policy's
        // live-challenge gate and its hourly cap apply identically to
        // known and unknown identities. The response shape is the same
        // 202 either way, and the decoy can never verify: the row
        // carries a hash of the identity, not the identity, so a token
        // attested by MSG91 for the real number never matches it.
        //
        // Without this, an unknown identity always answered 202 while a
        // known one answered 429 on its second request - a working
        // account-enumeration oracle.
        $known = $purpose === CustomerOtpChallenge::PURPOSE_LOGIN
            && $this->findUser($identity) !== null;
        $issueIdentifier = $known ? $identity : 'decoy:'.hash('sha256', $identity);

        try {
            $challenge = $this->otp->issue(
                $tenantId,
                $purpose,
                $issueIdentifier,
                $this->now(),
            );
        } catch (OtpRateLimitedException $e) {
            return $this->rateLimited($request, $e);
        } catch (OtpProviderRateLimitedException $e) {
            // Provider-side back-off on this identifier: retryable, so
            // the same 429 shape our local caps use - not a 503
            // implying the whole channel is down. The envelope hides
            // the provider head, the log keeps it.
            report($e);

            return $this->providerRateLimited($request);
        } catch (OtpProviderUnavailableException $e) {
            // The typed 503 hides the provider's symbolic error from
            // the client, so record it server-side. Provider exception
            // messages carry only symbolic codes - never identifiers -
            // so report() is PII-safe.
            report($e);

            return $this->dependencyUnavailable($request);
        }

        if (! $known) {
            // The decoy row exists (so the rate-limit state is real and
            // identical to a known identity), but the id handed back is
            // a throwaway: the challenge it names can never verify, and
            // the redeem path answers the same 401 it answers for a
            // wrong token.
            return new JsonResponse([
                'data' => [
                    'object' => 'otp_challenge',
                    'challenge_id' => (string) Str::ulid(),
                    'expires_in' => OtpDeliveryPolicy::load()->ttlSeconds,
                ],
                'meta' => ['request_id' => $this->requestId($request)],
            ], 202);
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

    public function signup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{6,15}$/'],
            'name' => ['required', 'string', 'max:190'],
            'email' => ['nullable', 'email', 'max:190'],
            'locale' => ['nullable', 'string', 'max:8'],
            // Carried in the challenge payload, attributed after the
            // phone verifies.
            'referral_code' => ['nullable', 'string', 'max:64'],
        ]);

        $tenant = $this->activeTenant($request, $validated['tenant_id']);
        if ($tenant instanceof JsonResponse) {
            return $tenant;
        }

        try {
            $challenge = $this->otp->issue(
                (string) $tenant->getKey(),
                CustomerOtpChallenge::PURPOSE_SIGNUP,
                $validated['phone'],
                $this->now(),
                [
                    'name' => $validated['name'],
                    'phone' => $validated['phone'],
                    'email' => $validated['email'] ?? null,
                    'locale' => $validated['locale'] ?? 'en',
                    'referral_code' => $validated['referral_code'] ?? null,
                ],
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

    public function verifySignup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            'challenge_id' => ['required', 'string'],
            'token' => ['required', 'string', 'max:4096'],
        ]);

        $tenant = $this->activeTenant($request, $validated['tenant_id']);
        if ($tenant instanceof JsonResponse) {
            return $tenant;
        }
        $tenantId = (string) $tenant->getKey();

        try {
            $result = $this->otp->verify(
                $tenantId,
                [CustomerOtpChallenge::PURPOSE_SIGNUP],
                $validated['challenge_id'],
                $validated['token'],
                $this->now(),
            );
        } catch (OtpProviderUnavailableException $e) {
            report($e);

            return $this->dependencyUnavailable($request);
        }

        if ($result->status !== OtpVerifyStatus::Match) {
            return $this->invalidRedeem($request);
        }

        $challenge = $result->challenge;
        $payload = $challenge->payload ?? [];
        $phone = (string) ($payload['phone'] ?? '');

        // The verified identifier and the payload phone must be the
        // same number (payload rows are written at issue time, so a
        // divergence is tampering) — and the account must not already
        // exist. Both fold into the same 409.
        if ($phone === ''
            || Msg91Identifier::canonical((string) $challenge->identifier) !== Msg91Identifier::canonical($phone)
            || User::query()->where('phone', $phone)->exists()) {
            return new JsonResponse([
                'error' => ['code' => 'conflict', 'message' => 'An account already exists for this phone number. Use login.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $user = new User();
        $user->forceFill([
            'name' => (string) ($payload['name'] ?? $phone),
            'email' => is_string($payload['email'] ?? null) && $payload['email'] !== ''
                ? $payload['email']
                : preg_replace('/[^0-9]/', '', $phone).'@phone.local',
            'phone' => $phone,
            'phone_verified_at' => $this->now()->toDateTimeString(),
            'external_id' => 'cust_'.strtolower((string) Str::ulid()),
        ]);
        // The password is a discarded placeholder (OTP-only accounts have no
        // password-login path), so cost 10 instead of the framework default
        // 12 — ~160 ms less per signup. It is written as a RAW attribute:
        // the `hashed` cast verifies pre-hashes against configured
        // BCRYPT_ROUNDS and rejects stronger ones (tests run at 4), while
        // Hash::check only ever verifies the algorithm — and the plaintext
        // random exists only inside this expression.
        $user->setRawAttributes([
            ...$user->getAttributes(),
            'password' => bcrypt(Str::random(32), ['rounds' => 10]),
        ]);
        $user->save();

        TenantMembership::query()->firstOrCreate(
            ['tenant_id' => $tenantId, 'user_id' => $user->getAuthIdentifier()],
            ['state' => TenantMembership::STATE_ACTIVE]
        );

        $scoped = $this->resolveTenantScope($tenant);
        try {
            BuyerProfile::query()->firstOrCreate(
                ['user_id' => $user->getAuthIdentifier()],
                ['display_name' => (string) ($payload['name'] ?? $phone), 'status' => BuyerProfile::STATUS_ACTIVE]
            );

            $this->audit->record(
                'customers.account.signed_up',
                ['module' => 'customers', 'resource_type' => 'buyer_profile', 'resource_id' => (string) $user->getAuthIdentifier()],
                null,
                ['tenant_id' => $tenantId, 'user_id' => (string) $user->getAuthIdentifier()]
            );
            $this->activity->record((string) $user->getAuthIdentifier(), 'signup', 'Account created');

            $this->issueReferralCode($tenantId, (string) $user->getAuthIdentifier());
            $this->attributeReferral($request, $tenantId, (string) $user->getAuthIdentifier(), (string) ($payload['referral_code'] ?? ''));
        } finally {
            if ($scoped['ownsContext']) {
                $scoped['context']->clear();
            }
        }

        $token = $this->mintToken($request, $user->fresh());
        if ($token instanceof JsonResponse) {
            return $token;
        }

        return new JsonResponse([
            'data' => [
                'object' => 'customer_session',
                'token' => $token['token'],
                'expires_at' => $token['expires_at'],
                'tenant_id' => $tenantId,
                'actor' => ['user_id' => (string) $user->external_id, 'session_id' => $token['session_id']],
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    /**
     * Two-phase login. Phase 1 redeems the widget's JWT: verified on a
     * HELD row (status token_verified) so an MFA-enrolled account can
     * add its TOTP as phase 2 — either in the same call (totp_code
     * present) or via a 202 mfa_required round-trip that keeps the row
     * parked. Non-MFA accounts consume and mint immediately.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            'challenge_id' => ['required', 'string'],
            'token' => ['sometimes', 'nullable', 'string', 'max:4096'],
            'totp_code' => ['sometimes', 'nullable', 'string', 'max:12'],
        ]);

        $tenant = $this->activeTenant($request, $validated['tenant_id']);
        if ($tenant instanceof JsonResponse) {
            return $tenant;
        }
        $tenantId = (string) $tenant->getKey();

        $token = (string) ($validated['token'] ?? '');
        $totp = (string) ($validated['totp_code'] ?? '');

        if ($token === '' && $totp === '') {
            return $this->invalidRedeem($request);
        }

        if ($token !== '') {
            try {
                $result = $this->otp->verify(
                    $tenantId,
                    [CustomerOtpChallenge::PURPOSE_LOGIN],
                    $validated['challenge_id'],
                    $token,
                    $this->now(),
                    holdOnMatch: true,
                );
            } catch (OtpProviderUnavailableException $e) {
                report($e);

                return $this->dependencyUnavailable($request);
            }

            if ($result->status !== OtpVerifyStatus::Held) {
                return $this->invalidRedeem($request);
            }

            $user = $this->findUser((string) $result->challenge->identifier);
            if ($user === null) {
                return $this->invalidRedeem($request);
            }

            $inactive = $this->membership($request, $tenantId, $user);
            if ($inactive instanceof JsonResponse) {
                return $inactive;
            }

            if ((bool) $user->mfa_enabled) {
                if ($totp === '') {
                    // Phase boundary: the row stays parked at
                    // token_verified; the second submit consumes it.
                    return new JsonResponse([
                        'data' => [
                            'object' => 'otp_challenge',
                            'challenge_id' => $validated['challenge_id'],
                            'status' => 'mfa_required',
                            'next_action' => 'complete_mfa',
                            'methods' => ['totp'],
                        ],
                        'meta' => ['request_id' => $this->requestId($request)],
                    ], 202);
                }

                $gate = $this->totpGate($request, $user, $totp);
                if ($gate instanceof JsonResponse) {
                    // Wrong code: the row stays held so the next submit
                    // can retry the TOTP within the hold window.
                    return $gate;
                }
            }

            if (! $this->otp->consumeVerified($tenantId, [CustomerOtpChallenge::PURPOSE_LOGIN], $validated['challenge_id'], $this->now()) instanceof StoredOtpChallenge) {
                return $this->invalidRedeem($request);
            }

            return $this->finishLogin($request, $tenant, $user);
        }

        // Phase 2: second factor only ({challenge_id, totp_code}) on a held row.
        $challenge = $this->otp->findVerified($tenantId, [CustomerOtpChallenge::PURPOSE_LOGIN], $validated['challenge_id'], $this->now());
        if (! $challenge instanceof StoredOtpChallenge) {
            return $this->invalidRedeem($request);
        }

        $user = $this->findUser((string) $challenge->identifier);
        if ($user === null) {
            return $this->invalidRedeem($request);
        }

        $inactive = $this->membership($request, $tenantId, $user);
        if ($inactive instanceof JsonResponse) {
            return $inactive;
        }

        if ((bool) $user->mfa_enabled) {
            $gate = $this->totpGate($request, $user, $totp);
            if ($gate instanceof JsonResponse) {
                return $gate;
            }
        }

        if (! $this->otp->consumeVerified($tenantId, [CustomerOtpChallenge::PURPOSE_LOGIN], $validated['challenge_id'], $this->now()) instanceof StoredOtpChallenge) {
            return $this->invalidRedeem($request);
        }

        return $this->finishLogin($request, $tenant, $user);
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

        return $verified ? true : $this->invalidRedeem($request);
    }

    private function finishLogin(Request $request, Tenant $tenant, User $user): JsonResponse
    {
        $tenantId = (string) $tenant->getKey();

        $scoped = $this->resolveTenantScope($tenant);
        try {
            // Self-heal: profiles predate the module for invite-seeded
            // accounts, so login backfills instead of failing.
            BuyerProfile::query()->firstOrCreate(
                ['user_id' => $user->getAuthIdentifier()],
                ['display_name' => $user->name, 'status' => BuyerProfile::STATUS_ACTIVE]
            );

            $this->audit->record(
                'customers.auth.logged_in',
                ['module' => 'customers', 'resource_type' => 'customer_session', 'resource_id' => (string) $user->getAuthIdentifier()],
                null,
                ['tenant_id' => $tenantId, 'user_id' => (string) $user->getAuthIdentifier()]
            );
            $this->activity->record((string) $user->getAuthIdentifier(), 'login', 'Signed in');
        } finally {
            if ($scoped['ownsContext']) {
                $scoped['context']->clear();
            }
        }

        $token = $this->mintToken($request, $user);
        if ($token instanceof JsonResponse) {
            return $token;
        }

        return new JsonResponse([
            'data' => [
                'object' => 'customer_session',
                'token' => $token['token'],
                'expires_at' => $token['expires_at'],
                'tenant_id' => $tenantId,
                'actor' => ['user_id' => (string) $user->external_id, 'session_id' => $token['session_id']],
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // M5 / TOKEN-001: the presented jti dies server-side — logout
        // revokes the credential (G4), it is no longer a client-side
        // discard that stays valid until the 24 h expiry.
        $actor = $request->attributes->get('actor');
        $sessionId = is_array($actor) && is_string($actor['session_id'] ?? null) ? $actor['session_id'] : null;

        if ($sessionId !== null) {
            ActorSession::revoke($sessionId);
        }

        $this->audit->record(
            'customers.auth.logged_out',
            ['module' => 'customers', 'resource_type' => 'customer_session', 'resource_id' => (string) $request->user()->getAuthIdentifier()],
            null,
            ['user_id' => (string) $request->user()->getAuthIdentifier()]
        );
        $this->activity->record((string) $request->user()->getAuthIdentifier(), 'logout', 'Signed out');

        return new JsonResponse([
            'data' => ['object' => 'customer_session', 'signed_out' => true],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * Typed failure envelopes: a dependency outage is a 503 — a
     * provider failure is never folded into the uniform 401 set and
     * never downgrades to another channel.
     */
    private function dependencyUnavailable(Request $request): JsonResponse
    {
        return new JsonResponse([
            'error' => ['code' => 'dependency_unavailable', 'message' => 'Verification is temporarily unavailable. Try again shortly.', 'request_id' => $this->requestId($request)],
        ], 503);
    }

    /**
     * Provider-side back-off on an identifier: clients keep branching
     * on `rate_limited` and back off, since every retry re-arms the
     * window.
     */
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
     * The single 401 every credential-shaped redeem failure shares:
     * unknown/foreign/expired/already-consumed/superseded challenge,
     * token MSG91 rejected, held row gone — indistinguishable by
     * design (no oracle).
     */
    private function invalidRedeem(Request $request): JsonResponse
    {
        return new JsonResponse([
            'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
        ], 401);
    }

    /**
     * Referral code issuance (idempotent) + attribution. Invalid and
     * self codes are ignored, never errors — a typo must not fail
     * signup. Reward rules are out of scope (attribution only).
     */
    private function issueReferralCode(string $tenantId, string $userId): void
    {
        if (ReferralCode::query()->where('user_id', $userId)->exists()) {
            return;
        }

        for ($attempts = 0; $attempts < 5; $attempts++) {
            try {
                $code = new ReferralCode();
                $code->forceFill([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'code' => ReferralCode::generate(),
                ]);
                $code->save();

                return;
            } catch (\Illuminate\Database\QueryException) {
                // Globally-unique code collision (astronomically rare):
                // regenerate instead of failing signup.
            }
        }
    }

    private function attributeReferral(Request $request, string $tenantId, string $userId, string $code): void
    {
        if ($code === '') {
            return;
        }

        $referral = ReferralCode::query()->where('code', $code)->first();

        // Unknown codes, foreign-tenant codes, and self codes are
        // ignored — a typo must not fail signup.
        if ($referral === null
            || (string) $referral->tenant_id !== $tenantId
            || (string) $referral->user_id === $userId) {
            return;
        }

        $attribution = new ReferralAttribution();
        $attribution->forceFill([
            'tenant_id' => $tenantId,
            'referrer_user_id' => (string) $referral->user_id,
            'referred_user_id' => $userId,
            'code' => $code,
        ]);
        $attribution->save();

        $this->audit->record(
            'customers.referral.attributed',
            ['module' => 'customers', 'resource_type' => 'referral_attribution', 'resource_id' => (string) $attribution->getKey()],
            null,
            ['tenant_id' => $tenantId, 'referrer_user_id' => (string) $referral->user_id, 'referred_user_id' => $userId]
        );
    }
}
