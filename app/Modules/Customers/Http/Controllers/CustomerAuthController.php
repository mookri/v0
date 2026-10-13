<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Models\User;
use App\Modules\Customers\Auth\CustomerOtpService;
use App\Modules\Customers\Auth\FirebaseIdTokenRejectedException;
use App\Modules\Customers\Auth\FirebaseIdTokenUnavailableException;
use App\Modules\Customers\Auth\OtpRateLimitedException;
use App\Modules\Customers\Contracts\VerifiesPhoneIdTokens;
use App\Modules\Customers\Models\BuyerProfile;
use App\Modules\Customers\Models\CustomerOtpChallenge;
use App\Modules\Customers\Models\FirebasePhoneAuthIntent;
use App\Modules\Customers\Models\ReferralAttribution;
use App\Modules\Customers\Models\ReferralCode;
use App\Modules\Customers\CustomerActivityRecorder;
use App\Platform\Audit\AuditWriter;
use App\Platform\Identity\ActorSessionRotation;
use App\Platform\Identity\ActorTokenIssuer;
use App\Platform\Identity\Models\ActorSession;
use App\Platform\Identity\Otp\OtpCaptchaRequiredException;
use App\Platform\Identity\Otp\OtpProviderRateLimitedException;
use App\Platform\Identity\Otp\OtpProviderUnavailableException;
use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Customer authentication (B2): OTP request, OTP-before-account signup,
 * signup verification (201 + HMAC actor token), login (200 + token), and
 * logout (revokes the presented session server-side, M5/TOKEN-001).
 * otp/signup/verify/login are public by design
 * (withoutMiddleware in Routes/api.php + throttle); logout rides the
 * module's auth.any + tenant group middleware.
 *
 * No-oracle rule: unknown identities, wrong/expired/exhausted/replayed
 * codes, and purpose mismatches all collapse to identical 202s (request)
 * or 401s (redeem). Tokens are backend-minted HMAC actor tokens
 * (ActorTokenIssuer, 24 h absolute TTL, interim until Passport) with
 * u = the user's external_id so AuthenticateAny resolves them with zero
 * auth-chain changes.
 */
final class CustomerAuthController
{
    public function __construct(
        private readonly CustomerOtpService $otp,
        private readonly AuditWriter $audit,
        private readonly CustomerActivityRecorder $activity,
        private readonly VerifiesPhoneIdTokens $phoneIdTokens,
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
     * S48 typed failure envelopes: a missing reCAPTCHA attestation is
     * a 422 (the client must render the widget and retry); a provider
     * outage is a 503 — a dependency failure is never folded into the
     * uniform 401 set and never downgrades to another channel.
     */
    private function captchaRequired(Request $request): JsonResponse
    {
        return new JsonResponse([
            'error' => [
                'code' => 'validation_failed',
                'message' => 'Captcha verification is required.',
                'details' => ['fields' => ['captcha_token' => ['captcha_required']]],
                'request_id' => $this->requestId($request),
            ],
        ], 422);
    }

    private function dependencyUnavailable(Request $request): JsonResponse
    {
        return new JsonResponse([
            'error' => ['code' => 'dependency_unavailable', 'message' => 'Verification is temporarily unavailable. Try again shortly.', 'request_id' => $this->requestId($request)],
        ], 503);
    }

    /**
     * Provider-side back-off on an identifier: clients keep branching on
     * `rate_limited` and back off, since every retry re-arms the window.
     */
    private function providerRateLimited(Request $request): JsonResponse
    {
        return new JsonResponse([
            'error' => ['code' => 'rate_limited', 'message' => 'Too many attempts.', 'request_id' => $this->requestId($request)],
        ], 429);
    }

    /**
     * Public captcha config (S48): lets clients render the reCAPTCHA
     * widget exactly when the server will demand an attestation. The
     * site key is public by design (Google serves it in every page
     * that renders the widget).
     */
    public function captchaConfig(Request $request): JsonResponse
    {
        return new JsonResponse([
            'data' => [
                'object' => 'captcha_config',
                'provider' => 'recaptcha',
                'site_key' => config('otp-providers.audiences.customer.firebase.recaptcha_key'),
                'required' => filter_var(config('otp-policy.captcha.customer', false), FILTER_VALIDATE_BOOLEAN),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function requestOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            // verify_*/change_* purposes unlock with their confirm endpoints
            // (B3+); until then only login + signup challenges are issued.
            'purpose' => ['required', 'string', 'in:login,signup'],
            'identity' => ['required', 'string', 'max:64'],
            // reCAPTCHA v2 token (S48 pass-through): forwarded as the
            // provider attestation; only demanded when the audience's
            // policy says so (remote providers, per otp-policy.php).
            'captcha_token' => ['nullable', 'string', 'max:4096'],
        ]);

        $tenant = $this->activeTenant($request, $validated['tenant_id']);
        if ($tenant instanceof JsonResponse) {
            return $tenant;
        }
        $tenantId = (string) $tenant->getKey();

        // No oracle: unknown login identities get an identical 202 with a
        // challenge id that can never verify (verify finds no row → 401,
        // the same code a wrong OTP produces).
        if ($validated['purpose'] === CustomerOtpChallenge::PURPOSE_LOGIN
            && $this->findUser($validated['identity']) === null) {
            return new JsonResponse([
                'data' => [
                    'object' => 'otp_challenge',
                    'challenge_id' => (string) Str::ulid(),
                    'expires_in' => CustomerOtpService::TTL_SECONDS,
                ],
                'meta' => ['request_id' => $this->requestId($request)],
            ], 202);
        }

        try {
            $challenge = $this->otp->issue(
                $tenantId,
                $validated['purpose'],
                $validated['identity'],
                $this->now(),
                attestation: $validated['captcha_token'] ?? null,
            );
        } catch (OtpRateLimitedException) {
            return new JsonResponse([
                'error' => ['code' => 'rate_limited', 'message' => 'Too many attempts.', 'request_id' => $this->requestId($request)],
            ], 429);
        } catch (OtpCaptchaRequiredException) {
            return $this->captchaRequired($request);
        } catch (OtpProviderRateLimitedException $e) {
            // Provider-side back-off on this identifier (Google's
            // TOO_MANY_ATTEMPTS family): retryable, so the same 429 shape
            // our local caps use — not a 503 implying the whole channel
            // is down. Recorded like the outage branch: the envelope
            // hides the provider head, the log keeps it.
            report($e);

            return $this->providerRateLimited($request);
        } catch (OtpProviderUnavailableException $e) {
            // S50 observability: the typed 503 hides the provider's
            // symbolic error from the client, so record it server-side.
            // The exception message carries only symbolic codes (HTTP
            // status + Google error head/reason) — never the phone,
            // token, code, or session handle — so report() is PII-safe.
            report($e);

            return $this->dependencyUnavailable($request);
        }

        return new JsonResponse([
            'data' => [
                'object' => 'otp_challenge',
                'challenge_id' => (string) $challenge->getKey(),
                'expires_in' => CustomerOtpService::TTL_SECONDS,
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
            // Reserved for the referrals wave: carried in the challenge
            // payload, unattributed until then.
            'referral_code' => ['nullable', 'string', 'max:64'],
            'captcha_token' => ['nullable', 'string', 'max:4096'],
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
                attestation: $validated['captcha_token'] ?? null,
            );
        } catch (OtpRateLimitedException) {
            return new JsonResponse([
                'error' => ['code' => 'rate_limited', 'message' => 'Too many attempts.', 'request_id' => $this->requestId($request)],
            ], 429);
        } catch (OtpCaptchaRequiredException) {
            return $this->captchaRequired($request);
        } catch (OtpProviderRateLimitedException $e) {
            // Provider-side back-off on this identifier (Google's
            // TOO_MANY_ATTEMPTS family): retryable, so the same 429 shape
            // our local caps use — not a 503 implying the whole channel
            // is down. Recorded like the outage branch: the envelope
            // hides the provider head, the log keeps it.
            report($e);

            return $this->providerRateLimited($request);
        } catch (OtpProviderUnavailableException $e) {
            // S50 observability: the typed 503 hides the provider's
            // symbolic error from the client, so record it server-side.
            // The exception message carries only symbolic codes (HTTP
            // status + Google error head/reason) — never the phone,
            // token, code, or session handle — so report() is PII-safe.
            report($e);

            return $this->dependencyUnavailable($request);
        }

        return new JsonResponse([
            'data' => [
                'object' => 'otp_challenge',
                'challenge_id' => (string) $challenge->getKey(),
                'expires_in' => CustomerOtpService::TTL_SECONDS,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 202);
    }

    public function verifySignup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            'challenge_id' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $tenant = $this->activeTenant($request, $validated['tenant_id']);
        if ($tenant instanceof JsonResponse) {
            return $tenant;
        }
        $tenantId = (string) $tenant->getKey();

        $challenge = $this->otp->verify(
            $tenantId,
            CustomerOtpChallenge::PURPOSE_SIGNUP,
            $validated['challenge_id'],
            $validated['code'],
            $this->now()
        );

        if ($challenge === null) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $payload = $challenge->payload ?? [];
        $phone = (string) ($payload['phone'] ?? '');

        if ($phone === '' || User::query()->where('phone', $phone)->exists()) {
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

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            'challenge_id' => ['required', 'string'],
            'code' => ['required', 'string'],
            'totp_code' => ['sometimes', 'nullable', 'string'],
        ]);

        $tenant = $this->activeTenant($request, $validated['tenant_id']);
        if ($tenant instanceof JsonResponse) {
            return $tenant;
        }
        $tenantId = (string) $tenant->getKey();

        $challenge = $this->otp->verify(
            $tenantId,
            CustomerOtpChallenge::PURPOSE_LOGIN,
            $validated['challenge_id'],
            $validated['code'],
            $this->now()
        );

        if ($challenge === null) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $user = $this->findUser((string) $challenge->identifier);

        if ($user === null) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

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

        // Enrolled MFA upgrades login to two factors: possession (OTP)
        // plus the TOTP code. Absent or wrong codes collapse to the
        // single 401 (no oracle on MFA state); the M4 lockout is the one
        // observable state, surfaced as 429 + Retry-After.
        if ((bool) $user->mfa_enabled) {
            $totp = (string) ($validated['totp_code'] ?? '');

            $verified = false;
            if ($totp !== '') {
                try {
                    $verified = app(\App\Platform\Identity\MfaService::class)->verify($user, $totp, time());
                } catch (\App\Platform\Identity\MfaLockedException $locked) {
                    return new JsonResponse([
                        'error' => ['code' => 'rate_limited', 'message' => 'Too many failed attempts. Try again later.', 'request_id' => $this->requestId($request)],
                    ], 429)->header('Retry-After', (string) $locked->retryAfter);
                }
            }

            if (! $verified) {
                return new JsonResponse([
                    'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
                ], 401);
            }
        }

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
     * Firebase phone login, step 1 (kit): record WHICH E.164 number the
     * client is about to authenticate with, BEFORE the client SDK starts
     * its Firebase handshake. No oracle — a row is created for any
     * well-formed phone under an active tenant whether or not an account
     * exists; eligibility is decided at exchange against Firebase's
     * verified claim, never against this request. The row is the
     * anti-replay hinge: it binds the phone, expires in 10 minutes, and
     * is consumed exactly once (see firebaseExchange).
     */
    public function firebaseIntent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            // Prefix-optional like signup's rule: exchange normalizes to
            // the +E.164 spelling Firebase's phone_number claim always
            // carries before comparing.
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{6,15}$/'],
        ]);

        $tenant = $this->activeTenant($request, $validated['tenant_id']);
        if ($tenant instanceof JsonResponse) {
            return $tenant;
        }

        $intent = FirebasePhoneAuthIntent::query()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'purpose' => FirebasePhoneAuthIntent::PURPOSE_LOGIN,
            'phone' => $validated['phone'],
            'expires_at' => $this->now()->addSeconds(FirebasePhoneAuthIntent::TTL_SECONDS),
        ]);

        return new JsonResponse([
            'data' => [
                'object' => 'firebase_phone_intent',
                'intent_id' => (string) $intent->getKey(),
                'expires_in' => FirebasePhoneAuthIntent::TTL_SECONDS,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 202);
    }

    /**
     * Firebase phone login, step 2 (kit): verify the ID token the client
     * SDK minted after the SMS round-trip, bind it to the intent, and
     * mint the exact same actor token every other login path issues — so
     * Laravel stays authoritative for customer lookup, membership, MFA,
     * and the session itself.
     *
     * Every credential-shaped failure collapses to one 401 (no oracle on
     * token state, account existence, MFA state, or phone binding); only
     * infrastructure failure is distinguishable, as the typed 503.
     */
    public function firebaseExchange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            'intent_id' => ['required', 'string', 'max:40'],
            'id_token' => ['required', 'string', 'max:8192'],
            'totp_code' => ['sometimes', 'nullable', 'string'],
        ]);

        $tenant = $this->activeTenant($request, $validated['tenant_id']);
        if ($tenant instanceof JsonResponse) {
            return $tenant;
        }
        $tenantId = (string) $tenant->getKey();

        $intent = FirebasePhoneAuthIntent::query()
            ->where('id', $validated['intent_id'])
            ->where('tenant_id', $tenantId)
            ->where('purpose', FirebasePhoneAuthIntent::PURPOSE_LOGIN)
            ->first();

        if ($intent === null || $intent->expires_at->isPast()) {
            return $this->invalidExchange($request);
        }

        try {
            $verified = $this->phoneIdTokens->verify($validated['id_token']);
        } catch (FirebaseIdTokenRejectedException) {
            return $this->invalidExchange($request);
        } catch (FirebaseIdTokenUnavailableException $exception) {
            // S50: infrastructure failures stay visible (symbolic
            // message) and typed — a Firebase outage never masquerades
            // as a wrong token.
            report($exception);

            return $this->dependencyUnavailable($request);
        }

        // Phone sign-in only: a token minted by a password or federated
        // session must never satisfy the phone intent.
        if ($verified['provider'] !== 'phone') {
            return $this->invalidExchange($request);
        }

        // Identity comes from Firebase's verified claim — but it must be
        // the phone THIS intent was opened for, so a harvested token
        // cannot be replayed into an intent for another number. Signup's
        // rule is prefix-optional, so normalize to the claim's +E.164.
        $intentPhone = str_starts_with((string) $intent->phone, '+')
            ? (string) $intent->phone
            : '+' . (string) $intent->phone;

        if ($verified['phone'] !== $intentPhone) {
            return $this->invalidExchange($request);
        }

        // Replay floor: the authentication must post-date the intent, so
        // a token minted for an earlier flow cannot satisfy a later one
        // (30 s allowance between Google's clock and ours).
        if ($verified['auth_time'] + 30 < $intent->created_at->getTimestamp()) {
            return $this->invalidExchange($request);
        }

        $user = $this->findUserByPhone($verified['phone']);

        if ($user === null) {
            // Login only: verified phone proof is not account creation.
            return $this->invalidExchange($request);
        }

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

        // MFA parity with login(): phone possession is factor one on both
        // paths, so an enrolled TOTP second factor is demanded here too —
        // absent/wrong codes collapse to the same 401 (no oracle on MFA
        // state); the M4 lockout is the one observable, as 429 + Retry-After.
        if ((bool) $user->mfa_enabled) {
            $totp = (string) ($validated['totp_code'] ?? '');

            $mfaVerified = false;
            if ($totp !== '') {
                try {
                    $mfaVerified = app(\App\Platform\Identity\MfaService::class)->verify($user, $totp, time());
                } catch (\App\Platform\Identity\MfaLockedException $locked) {
                    return new JsonResponse([
                        'error' => ['code' => 'rate_limited', 'message' => 'Too many failed attempts. Try again later.', 'request_id' => $this->requestId($request)],
                    ], 429)->header('Retry-After', (string) $locked->retryAfter);
                }
            }

            if (! $mfaVerified) {
                return $this->invalidExchange($request);
            }
        }

        // Single-use gate: one atomic DELETE decides which of any
        // concurrent exchanges with this intent proceeds — the loser
        // finds 0 rows and collapses to the uniform 401 above the mint.
        $consumed = FirebasePhoneAuthIntent::query()
            ->where('id', (string) $intent->getKey())
            ->where('tenant_id', $tenantId)
            ->delete() === 1;

        if (! $consumed) {
            return $this->invalidExchange($request);
        }

        $scoped = $this->resolveTenantScope($tenant);
        try {
            // Self-heal like login: profiles predate the module for
            // invite-seeded accounts, so this backfills instead of failing.
            BuyerProfile::query()->firstOrCreate(
                ['user_id' => $user->getAuthIdentifier()],
                ['display_name' => $user->name, 'status' => BuyerProfile::STATUS_ACTIVE]
            );

            $this->audit->record(
                'customers.auth.logged_in',
                ['module' => 'customers', 'resource_type' => 'customer_session', 'resource_id' => (string) $user->getAuthIdentifier()],
                null,
                ['tenant_id' => $tenantId, 'user_id' => (string) $user->getAuthIdentifier(), 'channel' => 'firebase']
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

    /**
     * The single 401 every credential-shaped exchange failure shares:
     * unknown/foreign/expired/already-consumed intent, token Firebase
     * rejected, wrong provider or phone, stale auth_time, unknown
     * account, failed MFA — indistinguishable by design (no oracle).
     */
    private function invalidExchange(Request $request): JsonResponse
    {
        return new JsonResponse([
            'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired credentials.', 'request_id' => $this->requestId($request)],
        ], 401);
    }

    /**
     * Firebase's phone_number claim is always +E.164, but accounts may
     * have been created through the prefix-optional signup rule, so both
     * spellings are tried before concluding no account exists.
     */
    private function findUserByPhone(string $phone): ?User
    {
        return User::query()
            ->where('phone', $phone)
            ->orWhere('phone', ltrim($phone, '+'))
            ->first();
    }
}
