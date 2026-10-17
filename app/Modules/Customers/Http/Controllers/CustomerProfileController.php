<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Models\User;
use App\Modules\Customers\Models\BuyerProfile;
use App\Modules\Customers\Models\CustomerOtpChallenge;
use App\Modules\Customers\CustomerActivityRecorder;
use App\Platform\Audit\AuditWriter;
use App\Platform\Identity\Otp\OtpChallengeManager;
use App\Platform\Identity\Otp\OtpProviderUnavailableException;
use App\Platform\Identity\Otp\OtpVerifyStatus;
use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer profile reads + strict updates, and OTP-stepped-up
 * deactivate/reactivate. Deactivation suspends the caller's membership in
 * the header tenant (tenant resolution is live per request, so the next
 * call with the same bearer is denied); reactivation requires a fresh
 * OTP proof to the verified phone.
 */
final class CustomerProfileController extends CustomerSelfServiceController
{
    public function __construct(private readonly AuditWriter $audit, private readonly CustomerActivityRecorder $activity)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $found = $this->ownProfile($request);
        if ($found instanceof JsonResponse) {
            return $found;
        }

        return new JsonResponse([
            'data' => self::serialize($found['user'], $found['profile']),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $found = $this->ownProfile($request);
        if ($found instanceof JsonResponse) {
            return $found;
        }

        // Strict fields (api-platform §3.1, same contract as identity
        // self-service): unknown keys are a 422, never silently dropped.
        $allowed = ['name', 'display_name', 'locale'];
        $unknown = array_diff(array_keys($request->all()), $allowed);
        if ($unknown !== []) {
            return new JsonResponse([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => 'Unknown fields: '.implode(', ', $unknown).'.',
                    'details' => ['fields' => array_fill_keys($unknown, ['unknown_field'])],
                    'request_id' => $this->requestId($request),
                ],
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:190'],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:190'],
            'locale' => ['sometimes', 'string', 'max:8'],
        ]);

        ['user' => $user, 'profile' => $profile] = $found;

        if (isset($validated['name']) && $validated['name'] !== $user->name) {
            $user->forceFill(['name' => $validated['name']])->save();
        }

        $profileChanges = array_intersect_key($validated, ['display_name' => true, 'locale' => true]);
        if ($profileChanges !== []) {
            $profile->forceFill($profileChanges)->save();
        }

        $this->audit->record(
            'customers.profile.updated',
            ['module' => 'customers', 'resource_type' => 'buyer_profile', 'resource_id' => (string) $profile->getKey()],
            null,
            ['user_id' => (string) $user->getAuthIdentifier(), 'fields' => array_keys($validated)]
        );
        $this->activity->record((string) $user->getAuthIdentifier(), 'profile_updated', 'Profile updated');

        return new JsonResponse([
            'data' => self::serialize($user->fresh(), $profile->fresh()),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function deactivate(Request $request): JsonResponse
    {
        return $this->transition($request);
    }

    /**
     * Public by design (per-route opt-out in Routes/api.php): a suspended
     * membership cannot pass the tenant gate, so reactivation must accept
     * a fresh OTP proof instead of a session. Possession of the verified
     * phone re-authenticates — the same rule login follows.
     */
    public function reactivate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            'challenge_id' => ['required', 'string'],
            'token' => ['required', 'string', 'max:4096'],
        ]);

        $tenant = Tenant::query()->find($validated['tenant_id']);
        if ($tenant === null || $tenant->state !== Tenant::STATE_ACTIVE) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Tenant unavailable.', 'request_id' => $this->requestId($request)],
            ], 404);
        }
        $tenantId = (string) $tenant->getKey();

        try {
            $result = app(OtpChallengeManager::class)->verify(
                $tenantId,
                [CustomerOtpChallenge::PURPOSE_LOGIN],
                $validated['challenge_id'],
                $validated['token'],
                CarbonImmutable::parse(gmdate('Y-m-d\TH:i:s.v\Z'))
            );
        } catch (OtpProviderUnavailableException $e) {
            report($e);

            return new JsonResponse([
                'error' => ['code' => 'dependency_unavailable', 'message' => 'Verification is temporarily unavailable. Try again shortly.', 'request_id' => $this->requestId($request)],
            ], 503);
        }

        if ($result->status !== OtpVerifyStatus::Match) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $identifier = (string) $result->challenge->identifier;

        $user = User::query()
            ->where('phone', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if ($user === null) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $membership = TenantMembership::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->getAuthIdentifier())
            ->first();

        if ($membership === null) {
            return new JsonResponse([
                'error' => ['code' => 'membership_inactive', 'message' => 'No active membership in this tenant.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        if ($membership->state === TenantMembership::STATE_ACTIVE) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Account is already active.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $scoped = $this->resolveTenantScope($tenant);
        try {
            $membership->forceFill(['state' => TenantMembership::STATE_ACTIVE])->save();

            $profile = BuyerProfile::query()->firstOrCreate(
                ['user_id' => $user->getAuthIdentifier()],
                ['display_name' => $user->name]
            );
            $profile->forceFill(['status' => BuyerProfile::STATUS_ACTIVE])->save();

            $this->audit->record(
                'customers.account.reactivated',
                ['module' => 'customers', 'resource_type' => 'buyer_profile', 'resource_id' => (string) $profile->getKey()],
                null,
                ['user_id' => (string) $user->getAuthIdentifier(), 'membership_id' => (string) $membership->getKey()]
            );
            $this->activity->record((string) $user->getAuthIdentifier(), 'reactivated', 'Account reactivated');
        } finally {
            if ($scoped['ownsContext']) {
                $scoped['context']->clear();
            }
        }

        return new JsonResponse([
            'data' => [
                'object' => 'buyer_profile',
                'id' => (string) $profile->getKey(),
                'status' => $profile->status,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    private function transition(Request $request): JsonResponse
    {
        $found = $this->ownProfile($request);
        if ($found instanceof JsonResponse) {
            return $found;
        }

        $validated = $request->validate([
            'challenge_id' => ['required', 'string'],
            'token' => ['required', 'string', 'max:4096'],
        ]);

        // Fresh OTP proof (login-purpose challenge, single-use): possession
        // re-verified at decision time, never a stale session alone.
        try {
            $result = app(OtpChallengeManager::class)->verify(
                (string) app(TenantContext::class)->tenantId(),
                [CustomerOtpChallenge::PURPOSE_LOGIN],
                $validated['challenge_id'],
                $validated['token'],
                CarbonImmutable::parse(gmdate('Y-m-d\TH:i:s.v\Z'))
            );
        } catch (OtpProviderUnavailableException $e) {
            report($e);

            return new JsonResponse([
                'error' => ['code' => 'dependency_unavailable', 'message' => 'Verification is temporarily unavailable. Try again shortly.', 'request_id' => $this->requestId($request)],
            ], 503);
        }

        if ($result->status !== OtpVerifyStatus::Match) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        ['user' => $user, 'profile' => $profile] = $found;

        $membershipId = $this->membershipId();
        $membership = $membershipId !== null
            ? TenantMembership::query()->withoutGlobalScopes()->find($membershipId)
            : null;

        if ($membership === null || (string) $membership->user_id !== (string) $user->getAuthIdentifier()) {
            return new JsonResponse([
                'error' => ['code' => 'membership_inactive', 'message' => 'No active membership in this tenant.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        if ($membership->state === TenantMembership::STATE_SUSPENDED) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Account is already deactivated.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $membership->forceFill(['state' => TenantMembership::STATE_SUSPENDED])->save();
        $profile->forceFill(['status' => BuyerProfile::STATUS_DEACTIVATED])->save();

        // SEC-015 §8.1 (9c): membership revocation kills the member's
        // refresh lineages for THIS tenant — the live tenant re-check
        // already denies every call; this stops the lineage minting.
        $this->revokeTenantLineages(
            (string) $user->getAuthIdentifier(),
            (string) app(TenantContext::class)->tenantId(),
            'membership_revoked'
        );

        $this->audit->record(
            'customers.account.deactivated',
            ['module' => 'customers', 'resource_type' => 'buyer_profile', 'resource_id' => (string) $profile->getKey()],
            null,
            ['user_id' => (string) $user->getAuthIdentifier(), 'membership_id' => (string) $membership->getKey()]
        );
        $this->activity->record((string) $user->getAuthIdentifier(), 'deactivated', 'Account deactivated');

        return new JsonResponse([
            'data' => [
                'object' => 'buyer_profile',
                'id' => (string) $profile->getKey(),
                'status' => $profile->status,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
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
                ['system' => 'customer-profile']
            );
        }

        return ['tenant' => $tenant, 'ownsContext' => $ownsContext, 'context' => $context];
    }

    /**
     * Account deletion (soft delete + audit).
     * Requires fresh OTP proof (same as deactivate). Sets deleted_at on profile,
     * suspends membership, records audit + activity.
     */
    public function delete(Request $request): JsonResponse
    {
        $found = $this->ownProfile($request);
        if ($found instanceof JsonResponse) {
            return $found;
        }

        $validated = $request->validate([
            'challenge_id' => ['required', 'string'],
            'token' => ['required', 'string', 'max:4096'],
        ]);

        // Fresh OTP proof (login-purpose challenge, single-use)
        try {
            $result = app(OtpChallengeManager::class)->verify(
                (string) app(TenantContext::class)->tenantId(),
                [CustomerOtpChallenge::PURPOSE_LOGIN],
                $validated['challenge_id'],
                $validated['token'],
                CarbonImmutable::parse(gmdate('Y-m-d\TH:i:s.v\Z'))
            );
        } catch (OtpProviderUnavailableException $e) {
            report($e);

            return new JsonResponse([
                'error' => ['code' => 'dependency_unavailable', 'message' => 'Verification is temporarily unavailable. Try again shortly.', 'request_id' => $this->requestId($request)],
            ], 503);
        }

        if ($result->status !== OtpVerifyStatus::Match) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        ['user' => $user, 'profile' => $profile] = $found;

        $membershipId = $this->membershipId();
        $membership = $membershipId !== null
            ? TenantMembership::query()->withoutGlobalScopes()->find($membershipId)
            : null;

        if ($membership === null || (string) $membership->user_id !== (string) $user->getAuthIdentifier()) {
            return new JsonResponse([
                'error' => ['code' => 'membership_inactive', 'message' => 'No active membership in this tenant.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        // Soft delete profile
        $profile->forceFill(['status' => BuyerProfile::STATUS_DEACTIVATED])->save();
        $profile->delete(); // sets deleted_at

        // Suspend membership
        $membership->forceFill(['state' => TenantMembership::STATE_SUSPENDED])->save();

        // SEC-015 §8.1 (9c): deletion suspends the membership too —
        // same lineage kill as deactivation.
        $this->revokeTenantLineages(
            (string) $user->getAuthIdentifier(),
            (string) app(TenantContext::class)->tenantId(),
            'membership_revoked'
        );

        $this->audit->record(
            'customers.account.deleted',
            ['module' => 'customers', 'resource_type' => 'buyer_profile', 'resource_id' => (string) $profile->getKey()],
            null,
            ['user_id' => (string) $user->getAuthIdentifier(), 'membership_id' => (string) $membership->getKey()]
        );
        $this->activity->record((string) $user->getAuthIdentifier(), 'deleted', 'Account deleted');

        return new JsonResponse([
            'data' => [
                'object' => 'buyer_profile',
                'id' => (string) $profile->getKey(),
                'status' => $profile->status,
                'deleted_at' => $profile->deleted_at,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * SEC-015 §8.1 bulk trigger (9c): revoke the member's access +
     * refresh tokens minted by THIS tenant's clients (token → client →
     * profile.tenant_id is the only tenant signal tokens carry — no
     * tenant claim exists on them yet), invalidate the status cache,
     * and chain each kill. Tokens minted for other tenants and
     * tenant-less platform clients are untouched: their authorization
     * is a different membership's concern.
     */
    private function revokeTenantLineages(string $userId, string $tenantId, string $reason): void
    {
        $clientIds = \App\Platform\Identity\OAuth\OAuthClientProfile::query()
            ->where('tenant_id', $tenantId)
            ->pluck('client_id');

        if ($clientIds->isEmpty()) {
            return;
        }

        $accessIds = \Laravel\Passport\Passport::token()->newQuery()
            ->where('user_id', $userId)
            ->whereIn('client_id', $clientIds)
            ->where('revoked', false)
            ->pluck('id');

        if ($accessIds->isEmpty()) {
            return;
        }

        \Laravel\Passport\Passport::token()->newQuery()->whereIn('id', $accessIds)->update(['revoked' => true]);
        \Laravel\Passport\Passport::refreshToken()->newQuery()->whereIn('access_token_id', $accessIds)->update(['revoked' => true]);

        app(\App\Platform\Identity\Federation\RevocationStatusCache::class)
            ->invalidateTokens($accessIds->all());

        $recorder = app(\App\Platform\Identity\Federation\AuthEventRecorder::class);
        foreach ($accessIds as $jti) {
            $recorder->record([
                'event_type' => 'token.revoked',
                'decision' => 'allow',
                'subject_id' => $userId,
                'tenant_ref' => $tenantId,
                'token_jti' => (string) $jti,
                'reason_code' => $reason,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function serialize(User $user, BuyerProfile $profile): array
    {
        return [
            'id' => (string) $profile->getKey(),
            'object' => 'buyer_profile',
            'display_name' => $profile->display_name,
            'name' => $user->name,
            'locale' => $profile->locale,
            'status' => $profile->status,
            'phone' => self::maskPhone($user->phone),
            'phone_verified_at' => $user->phone_verified_at,
            'email' => self::maskEmail($user->email),
            'email_verified_at' => self::maskEmail($user->email) !== null ? $user->email_verified_at : null,
        ];
    }
}
