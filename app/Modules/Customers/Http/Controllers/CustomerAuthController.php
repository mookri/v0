<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Models\User;
use App\Modules\Customers\Auth\CustomerOtpService;
use App\Modules\Customers\Auth\OtpRateLimitedException;
use App\Modules\Customers\Models\BuyerProfile;
use App\Modules\Customers\Models\CustomerOtpChallenge;
use App\Platform\Audit\AuditWriter;
use App\Platform\Identity\ActorTokenIssuer;
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
 * best-effort logout. otp/signup/verify/login are public by design
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
    private const TOKEN_TTL_SECONDS = 86400;

    public function __construct(
        private readonly CustomerOtpService $otp,
        private readonly AuditWriter $audit,
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

        $minted = ActorTokenIssuer::mint(
            $secret,
            (string) $user->external_id,
            self::TOKEN_TTL_SECONDS,
            gmdate('Y-m-d\TH:i:s.v\Z'),
        );

        return [
            'token' => $minted['token'],
            'expires_at' => $minted['expires_at'],
            'session_id' => $minted['session_id'],
        ];
    }

    public function requestOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            // verify_*/change_* purposes unlock with their confirm endpoints
            // (B3+); until then only login + signup challenges are issued.
            'purpose' => ['required', 'string', 'in:login,signup'],
            'identity' => ['required', 'string', 'max:64'],
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
                $this->now()
            );
        } catch (OtpRateLimitedException) {
            return new JsonResponse([
                'error' => ['code' => 'rate_limited', 'message' => 'Too many attempts.', 'request_id' => $this->requestId($request)],
            ], 429);
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
                ]
            );
        } catch (OtpRateLimitedException) {
            return new JsonResponse([
                'error' => ['code' => 'rate_limited', 'message' => 'Too many attempts.', 'request_id' => $this->requestId($request)],
            ], 429);
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
            'password' => bcrypt(Str::random(32)),
            'phone' => $phone,
            'phone_verified_at' => $this->now()->toDateTimeString(),
            'external_id' => 'cust_'.strtolower((string) Str::ulid()),
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

    public function login(Request $request): JsonResponse
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
        // Best-effort until the jti revocation store lands (TOKEN-001):
        // the client discards the bearer; the server records the event.
        // The token stays technically valid until its 24 h expiry.
        $this->audit->record(
            'customers.auth.logged_out',
            ['module' => 'customers', 'resource_type' => 'customer_session', 'resource_id' => (string) $request->user()->getAuthIdentifier()],
            null,
            ['user_id' => (string) $request->user()->getAuthIdentifier()]
        );

        return new JsonResponse([
            'data' => ['object' => 'customer_session', 'signed_out' => true],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
