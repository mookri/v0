<?php

namespace App\Platform\Identity\Http\Controllers;

use App\Models\User;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * User lifecycle administration (platform-global surfaces, central data):
 * tenant-scoped user listing (via memberships), suspend/activate with
 * reason. Suspended users fail authentication everywhere once the guard
 * check lands with the token program; until then suspension is enforced
 * at membership resolution (suspended state denies entry).
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

    public function suspend(Request $request, string $id): JsonResponse
    {
        return $this->setStatus($request, $id, self::STATUS_SUSPENDED);
    }

    public function activate(Request $request, string $id): JsonResponse
    {
        return $this->setStatus($request, $id, self::STATUS_ACTIVE);
    }

    private function setStatus(Request $request, string $id, string $status): JsonResponse
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

        return new JsonResponse([
            'data' => [
                'id' => $user->getAuthIdentifier(),
                'object' => 'user',
                'status' => $status,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
