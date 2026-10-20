<?php

namespace App\Platform\Identity\Http\Controllers;

use App\Models\User;
use App\Platform\Api\WebMutations;
use App\Platform\Authz\Models\MembershipRoleAssignment;
use App\Platform\Authz\Models\Role;
use App\Platform\Identity\Contracts\TrackSeeder;
use App\Platform\Identity\Models\Invite;
use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Track-scoped invites (identity self-service extension): one endpoint
 * family for every persona — track is data, never a code fork. Tokens are
 * SHA-256 hashes (plaintext shown once); acceptance is an atomic,
 * all-or-nothing transaction: user resolve-or-create → membership + role
 * grants → track seed → invite consumed. Supply tracks seed draft
 * applications; unknown roles fail the issue call, never accept.
 */
final class InviteController
{
    private const TRACKS = ['pandit', 'partner-vendor', 'partner-individual', 'customer', 'staff'];

    private const MAX_ISSUED_PER_HOUR = 10;

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    /**
     * Tenant invite inventory for the console — metadata only: the
     * one-time token and its hash never leave the server after issue.
     * Scoped like issue (tenant required, issuer active in it).
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate(['tenant_id' => ['required', 'string', 'size:26']]);

        $tenant = Tenant::query()->find($validated['tenant_id']);
        if ($tenant === null || $tenant->state !== Tenant::STATE_ACTIVE) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Tenant unavailable.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $membership = $this->issuerMembership($request, (string) $tenant->getKey());
        if ($membership instanceof JsonResponse) {
            return $membership;
        }

        $invites = Invite::query()
            ->where('tenant_id', (string) $tenant->getKey())
            ->orderByDesc('created_at')
            ->get();

        return new JsonResponse([
            'data' => $invites->map(static fn (Invite $invite): array => [
                'id' => (string) $invite->getKey(),
                'object' => 'invite',
                'track' => $invite->track,
                'identifier' => $invite->identifier,
                'roles' => $invite->roles ?? [],
                'state' => $invite->state,
                'expires_at' => $invite->expires_at->toIso8601String(),
                'created_at' => $invite->created_at?->toIso8601String(),
                'accepted_by' => $invite->accepted_by,
            ])->values()->all(),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * Issuers need an active membership in the invite's tenant (least
     * privilege) — the shared gate for list/issue/revoke.
     */
    private function issuerMembership(Request $request, string $tenantId): TenantMembership|JsonResponse
    {
        $membership = TenantMembership::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', (string) $request->user()->getAuthIdentifier())
            ->where('state', TenantMembership::STATE_ACTIVE)
            ->first();

        if ($membership === null) {
            return new JsonResponse([
                'error' => ['code' => 'membership_inactive', 'message' => 'No active membership in this tenant.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        return $membership;
    }

    // Shared actions (doc 06 §7.1-7.2): the invites console forms and
    // /api/v1/identity/invites point here — one issue/revoke path,
    // negotiation only. The acceptance token exists only inside the
    // issue envelope, so the web flash carries it (shown once, escaped
    // by the view); API envelopes stay byte-identical (InviteTest pins).
    public function issue(Request $request): JsonResponse|RedirectResponse
    {
        return WebMutations::negotiate(
            $request,
            fn (): JsonResponse => $this->issueJson($request),
            static fn (JsonResponse $result): string
                => 'Invite issued — pass this token to the invitee now, it is shown only once: '.WebMutations::stringData($result, 'token'),
        );
    }

    private function issueJson(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string', 'size:26'],
            'track' => ['required', 'string', 'in:'.implode(',', self::TRACKS)],
            'identifier' => ['required', 'string', 'max:190'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string'],
            'ttl_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
        ]);

        $tenant = Tenant::query()->find($validated['tenant_id']);
        if ($tenant === null || $tenant->state !== Tenant::STATE_ACTIVE) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Tenant unavailable.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        // Issuers need an active membership here (least privilege); the
        // resolved context below lets tenant-scoped reads (roles) work.
        $issuerMembership = $this->issuerMembership($request, (string) $tenant->getKey());
        if ($issuerMembership instanceof JsonResponse) {
            return $issuerMembership;
        }

        $context = app(TenantContext::class);
        $ownsContext = ! $context->isResolved();
        if ($ownsContext) {
            $context->set(
                (string) $tenant->getKey(),
                (string) $issuerMembership->getKey(),
                $issuerMembership->org_node_id,
                (new ConnectionResolver())->cachedFor($tenant),
                ['user_id' => (string) $request->user()->getAuthIdentifier()]
            );
        }

        try {
            foreach ($validated['roles'] ?? [] as $code) {
                $role = Role::query()->where('code', $code)->first();
                if ($role === null) {
                    return new JsonResponse([
                        'error' => ['code' => 'validation_failed', 'message' => "Unknown role: {$code}.", 'details' => ['fields' => ['roles' => ['unknown_role']]], 'request_id' => $this->requestId($request)],
                    ], 422);
                }
            }

            $recent = Invite::query()
                ->where('tenant_id', $tenant->getKey())
                ->where('created_by', (string) $request->user()->getAuthIdentifier())
                ->where('created_at', '>=', now()->subHour())
                ->count();
            if ($recent >= self::MAX_ISSUED_PER_HOUR) {
                return new JsonResponse([
                    'error' => ['code' => 'rate_limited', 'message' => 'Too many invites.', 'request_id' => $this->requestId($request)],
                ], 429);
            }

            $token = 'inv_'.Str::random(32);
            $invite = new Invite();
            $invite->forceFill([
                'tenant_id' => (string) $tenant->getKey(),
                'track' => $validated['track'],
                'roles' => array_values($validated['roles'] ?? []),
                'identifier' => $validated['identifier'],
                'token_hash' => hash('sha256', $token),
                'state' => Invite::STATE_ISSUED,
                'expires_at' => now()->addHours($validated['ttl_hours'] ?? 72)->toDateTimeString(),
                'created_by' => (string) $request->user()->getAuthIdentifier(),
            ]);
            $invite->save();
        } finally {
            if ($ownsContext) {
                $context->clear();
            }
        }

        return new JsonResponse([
            'data' => [
                'id' => (string) $invite->getKey(),
                'object' => 'invite',
                'token' => $token,
                'expires_at' => $invite->expires_at->toIso8601String(),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function accept(Request $request): JsonResponse
    {
        $validated = $request->validate(['token' => ['required', 'string']]);

        $invite = Invite::query()->where('token_hash', hash('sha256', $validated['token']))->first();

        // Identical shape for unknown/expired/consumed (no oracle).
        if ($invite === null || $invite->state !== Invite::STATE_ISSUED || $invite->expires_at->isPast()) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired invite.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $tenant = Tenant::query()->find($invite->tenant_id);
        if ($tenant === null || $tenant->state !== Tenant::STATE_ACTIVE) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired invite.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        // Acceptance runs tenant-scoped writes (roles, assignments, draft
        // application) with no authenticated principal yet: resolve the
        // invite tenant's context explicitly and clear it after.
        $context = app(TenantContext::class);
        $ownsContext = ! $context->isResolved();
        if ($ownsContext) {
            $context->set(
                (string) $tenant->getKey(),
                null,
                null,
                (new ConnectionResolver())->cachedFor($tenant),
                ['system' => 'invite-accept', 'invite_id' => (string) $invite->getKey()]
            );
        }

        try {
            $result = DB::connection('central')->transaction(function () use ($invite, $tenant) {
                $claimed = Invite::query()->whereKey($invite->getKey())->where('state', Invite::STATE_ISSUED)->lockForUpdate()->first();
                if ($claimed === null) {
                    return null;
                }

                $user = User::query()->where('email', $invite->identifier)->first();
                if ($user === null) {
                    $user = new User();
                    $user->forceFill([
                        'name' => $invite->identifier,
                        'email' => filter_var($invite->identifier, FILTER_VALIDATE_EMAIL) ? $invite->identifier : $invite->identifier.'@invite.local',
                        'password' => bcrypt(Str::random(32)),
                    ]);
                    $user->save();
                }

                $membership = TenantMembership::query()->firstOrCreate(
                    ['tenant_id' => (string) $tenant->getKey(), 'user_id' => $user->getAuthIdentifier()],
                    ['state' => TenantMembership::STATE_ACTIVE]
                );

                foreach ($invite->roles ?? [] as $code) {
                    $role = Role::query()->where('code', $code)->first();
                    if ($role === null) {
                        throw new \RuntimeException("role vanished: {$code}");
                    }
                    MembershipRoleAssignment::query()->firstOrCreate([
                        'membership_id' => (string) $membership->getKey(),
                        'role_id' => (string) $role->getKey(),
                    ]);
                }

                $seeded = $this->seedTrack($tenant, $user, $invite);

                $invite->forceFill(['state' => Invite::STATE_ACCEPTED, 'accepted_by' => (string) $user->getAuthIdentifier()])->save();

                return ['user_id' => (string) $user->getAuthIdentifier(), 'membership_id' => (string) $membership->getKey(), 'seeded' => $seeded];
            });
        } catch (\Throwable) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired invite.', 'request_id' => $this->requestId($request)],
            ], 401);
        } finally {
            if ($ownsContext) {
                $context->clear();
            }
        }

        if ($result === null) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid or expired invite.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        return new JsonResponse([
            'data' => [
                'object' => 'invite_acceptance',
                'user_id' => $result['user_id'],
                'membership_id' => $result['membership_id'],
                'seeded' => $result['seeded'],
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function revoke(Request $request, string $id): JsonResponse|RedirectResponse
    {
        return WebMutations::negotiate(
            $request,
            fn (): JsonResponse => $this->revokeJson($request, $id),
            'Invite revoked.',
        );
    }

    private function revokeJson(Request $request, string $id): JsonResponse
    {
        // Tenant travels as the console's hidden field or the standard
        // X-Tenant-Id header (the OpenAPI fragment has always required
        // it): an invite outside the caller's tenant answers 404 — the
        // same vocabulary as a plain miss, never an existence oracle.
        $tenantId = (string) ($request->input('tenant_id') ?? $request->header('X-Tenant-Id'));
        if (! Str::isUlid($tenantId)) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => 'tenant_id is required.', 'details' => ['fields' => ['tenant_id' => ['required']]], 'request_id' => $this->requestId($request)],
            ], 422);
        }

        $membership = $this->issuerMembership($request, $tenantId);
        if ($membership instanceof JsonResponse) {
            return $membership;
        }

        $invite = Invite::query()->where('tenant_id', $tenantId)->find($id);

        if ($invite === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Invite not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $invite->forceFill(['state' => Invite::STATE_REVOKED])->save();

        return new JsonResponse([
            'data' => ['id' => (string) $invite->getKey(), 'object' => 'invite', 'state' => $invite->state],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function seedTrack(Tenant $tenant, User $user, Invite $invite): ?array
    {
        if (! app()->bound(TrackSeeder::class)) {
            return null;
        }

        return app(TrackSeeder::class)->seed($invite->track, (string) $user->getAuthIdentifier());
    }
}
