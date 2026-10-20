<?php

namespace App\Platform\Tenancy\Http\Controllers;

use App\Platform\Api\CursorPagination;
use App\Platform\Tenancy\Models\TenantMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tenant self-service surface (TEN-006 §4.3, platform-global): the
 * authenticated user's own memberships with tenant summaries.
 *
 * Platform-global exemption: mounted WITHOUT the `tenant` middleware
 * (this endpoint is how clients discover which tenants they can enter).
 * Only active memberships resolve a context elsewhere; here all states
 * are listed so clients can explain denials. Never leaks other users'
 * memberships: scoped to the authenticated principal, always.
 */
final class TenantSelfServiceController
{
    public function index(Request $request): JsonResponse
    {
        $query = TenantMembership::query()
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->with('tenant')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        [$items, $page] = CursorPagination::paginate($query, $request);

        return new JsonResponse([
            'data' => array_map(
                static fn (TenantMembership $membership): array => [
                    'id' => (string) $membership->getKey(),
                    'object' => 'tenant_membership',
                    'state' => $membership->state,
                    'tenant' => $membership->tenant === null ? null : [
                        'id' => (string) $membership->tenant->getKey(),
                        'slug' => $membership->tenant->slug ?? $membership->tenant->code,
                        'name' => $membership->tenant->name,
                        'status' => $membership->tenant->status ?? $membership->tenant->state,
                    ],
                ],
                iterator_to_array($items),
            ),
            'meta' => [
                'request_id' => (string) $request->attributes->get('request_id', ''),
                'page' => $page,
            ],
        ]);
    }
}
