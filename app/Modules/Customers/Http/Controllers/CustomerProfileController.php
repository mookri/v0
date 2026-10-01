<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Models\User;
use App\Modules\Customers\Auth\CustomerOtpService;
use App\Modules\Customers\Models\BuyerProfile;
use App\Modules\Customers\Models\CustomerOtpChallenge;
use App\Modules\Customers\CustomerActivityRecorder;
use App\Platform\Audit\AuditWriter;
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
            'code' => ['required', 'string'],
        ]);

        $tenant = Tenant::query()->find($validated['tenant_id']);
        if ($tenant === null || $tenant->state !== Tenant::STATE_ACTIVE) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Tenant unavailable.', 'request_id' => $this->requestId($request)],
            ], 404);
        }
        $tenantId = (string) $tenant->getKey();

        $proof = app(CustomerOtpService::class)->verify(
            $tenantId,
            CustomerOtpChallenge::PURPOSE_LOGIN,
            $validated['challenge_id'],
            $validated['code'],
            CarbonImmutable::parse(gmdate('Y-m-d\TH:i:s.v\Z'))
        );

        if ($proof === null) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired code.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $user = User::query()
            ->where('phone', (string) $proof->identifier)
            ->orWhere('email', (string) $proof->identifier)
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
            'code' => ['required', 'string'],
        ]);

        // Fresh OTP proof (login-purpose challenge, single-use): possession
        // re-verified at decision time, never a stale session alone.
        $proof = app(CustomerOtpService::class)->verify(
            (string) app(TenantContext::class)->tenantId(),
            CustomerOtpChallenge::PURPOSE_LOGIN,
            $validated['challenge_id'],
            $validated['code'],
            CarbonImmutable::parse(gmdate('Y-m-d\TH:i:s.v\Z'))
        );

        if ($proof === null) {
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
