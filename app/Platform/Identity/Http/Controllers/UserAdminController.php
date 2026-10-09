<?php

namespace App\Platform\Identity\Http\Controllers;

use App\Models\User;
use App\Platform\Api\WebMutations;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * User lifecycle administration (platform-global surfaces, central data):
 * tenant-scoped user listing (via memberships), suspend/activate with
 * reason. Suspension is enforced at every API credential check (HMAC +
 * dual-accept status gates, refresh treated as revoked) and cascades
 * here: suspending kills the user's OAuth tokens and web sessions, so
 * no suspended credential survives past the 15-minute access TTL.
 * Activation does not resurrect anything — fresh login required.
 */
final class UserAdminController
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_DEACTIVATED = 'deactivated';

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate(['tenant_id' => ['required', 'string']]);

        $memberships = TenantMembership::query()
            ->where('tenant_id', $validated['tenant_id'])
            ->with('user')
            ->orderBy('created_at')
            ->get();

        return new JsonResponse([
            'data' => $memberships->map(static function (TenantMembership $membership): array {
                /** @var User|null $user */
                $user = $membership->user;
                if ($user === null) {
                    return ['id' => (string) $membership->getKey(), 'object' => 'tenant_user', 'state' => $membership->state, 'user' => null];
                }

                return [
                    'id' => (string) $membership->getKey(),
                    'object' => 'tenant_user',
                    'state' => $membership->state,
                    'user' => [
                        'id' => $user->getAuthIdentifier(),
                        'name' => $user->name,
                        'email' => $user->email,
                        'status' => $user->status ?? self::STATUS_ACTIVE,
                    ],
                ];
            })->values()->all(),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // Shared actions (doc 06 §7.1-7.2): the console's row forms and
    // /api/v1 point here — one tenant/issuuer/credential-revocation path,
    // negotiation only (web redirects back with a coded flash, API keeps
    // its envelope untouched). The web route mirrors the API guards
    // exactly: tenant + platform:user:manage + a fresh MFA proof.
    public function suspend(Request $request, string $id): JsonResponse|RedirectResponse
    {
        return WebMutations::negotiate(
            $request,
            fn (): JsonResponse => $this->setStatusJson($request, $id, self::STATUS_SUSPENDED),
            'User suspended.',
        );
    }

    public function activate(Request $request, string $id): JsonResponse|RedirectResponse
    {
        return WebMutations::negotiate(
            $request,
            fn (): JsonResponse => $this->setStatusJson($request, $id, self::STATUS_ACTIVE),
            'User activated.',
        );
    }

    private function setStatusJson(Request $request, string $id, string $status): JsonResponse
    {
        $validated = $request->validate(['tenant_id' => ['required', 'string']]);

        $tenant = \App\Platform\Tenancy\Models\Tenant::query()->find($validated['tenant_id']);
        if ($tenant === null || $tenant->state !== \App\Platform\Tenancy\Models\Tenant::STATE_ACTIVE) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Tenant unavailable.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $issuer = \App\Platform\Tenancy\Models\TenantMembership::query()
            ->where('tenant_id', (string) $tenant->getKey())
            ->where('user_id', (string) $request->user()->getAuthIdentifier())
            ->where('state', \App\Platform\Tenancy\Models\TenantMembership::STATE_ACTIVE)
            ->first();
        if ($issuer === null) {
            return new JsonResponse([
                'error' => ['code' => 'membership_inactive', 'message' => 'No active membership in this tenant.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        $user = User::query()->find($id);

        if ($user === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'User not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        if (($user->status ?? self::STATUS_ACTIVE) === self::STATUS_DEACTIVATED) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Deactivated users cannot change state.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $user->forceFill(['status' => $status])->save();

        if ($status === self::STATUS_SUSPENDED) {
            $this->revokeCredentials((string) $user->getAuthIdentifier());
        }

        return new JsonResponse([
            'data' => [
                'id' => $user->getAuthIdentifier(),
                'object' => 'user',
                'status' => $status,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * Bulk revocation trigger (SEC-015 §8.1): all OAuth tokens for the
     * user die, every web session row goes with them, and — since
     * M5/TOKEN-001 — the HMAC actor sessions too.
     */
    private function revokeCredentials(string $userId): void
    {
        $accessIds = \Laravel\Passport\Passport::token()->newQuery()
            ->where('user_id', $userId)
            ->pluck('id');

        \Laravel\Passport\Passport::token()->newQuery()->whereIn('id', $accessIds)->update(['revoked' => true]);
        \Laravel\Passport\Passport::refreshToken()->newQuery()->whereIn('access_token_id', $accessIds)->update(['revoked' => true]);

        \Illuminate\Support\Facades\DB::connection(config('database.default'))
            ->table('sessions')
            ->where('user_id', $userId)
            ->delete();

        $externalId = \App\Models\User::query()->whereKey($userId)->value('external_id');
        if (is_string($externalId) && $externalId !== '') {
            \App\Platform\Identity\Models\ActorSession::revokeForUser($externalId);
        }
    }
}
