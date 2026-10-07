<?php

namespace App\Platform\Identity\Http\Controllers;

use App\Platform\Api\WebMutations;
use App\Platform\Identity\Models\ApiKey;
use App\Platform\Identity\MachinePrincipal;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Machine API key administration: issue (plaintext once), list metadata,
 * revoke (immediate), rotate (24h dual-active overlap). Secrets are
 * SHA-256 stored; rotation creates a new row and revokes the old after
 * the overlap — never secret edits.
 */
final class ApiKeyController
{
    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    /**
     * Machine principals administer nothing: key management is a human
     * action behind an active membership. The membership lookup below
     * would deny them anyway (`key:*` never matches a user_id); this
     * early deny skips the query and states the rule explicitly.
     */
    private function machineDenial(Request $request): ?JsonResponse
    {
        if ($request->user() instanceof MachinePrincipal) {
            return new JsonResponse([
                'error' => ['code' => 'membership_inactive', 'message' => 'No active membership in this tenant.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        return null;
    }

    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->machineDenial($request)) {
            return $denied;
        }

        $validated = $request->validate(['tenant_id' => ['required', 'string']]);

        $tenant = Tenant::query()->find($validated['tenant_id']);
        if ($tenant === null || $tenant->state !== Tenant::STATE_ACTIVE) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Tenant unavailable.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $issuer = TenantMembership::query()
            ->where('tenant_id', (string) $tenant->getKey())
            ->where('user_id', (string) $request->user()->getAuthIdentifier())
            ->where('state', TenantMembership::STATE_ACTIVE)
            ->first();
        if ($issuer === null) {
            return new JsonResponse([
                'error' => ['code' => 'membership_inactive', 'message' => 'No active membership in this tenant.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        $keys = ApiKey::query()->where('tenant_id', (string) $tenant->getKey())->orderByDesc('created_at')->get();

        return new JsonResponse([
            'data' => $keys->map(static fn (ApiKey $key): array => [
                'id' => (string) $key->getKey(),
                'object' => 'api_key',
                'name' => $key->name,
                'prefix' => $key->prefix,
                'scopes' => $key->scopes ?? [],
                'rate_tier' => $key->rate_tier,
                'expires_at' => $key->expires_at,
                'last_used_at' => $key->last_used_at,
                'revoked_at' => $key->revoked_at,
            ])->all(),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // Shared actions (doc 06 §7.1-7.2): the console's forms and /api/v1
    // point here — one membership-checked path, negotiation only. The
    // secret exists only inside the envelope, so the web flash derives
    // from the successful response (shown once, escaped by the view);
    // API envelopes stay byte-identical.
    public function issue(Request $request): JsonResponse|RedirectResponse
    {
        return WebMutations::negotiate(
            $request,
            fn (): JsonResponse => $this->issueJson($request),
            static function (JsonResponse $result): string {
                $payload = $result->getData(true);
                $data = is_array($payload) && isset($payload['data']) && is_array($payload['data'])
                    ? $payload['data']
                    : [];
                $secret = isset($data['secret']) && is_string($data['secret']) ? $data['secret'] : '';

                return 'API key issued — copy it now, it is shown only once: '.$secret;
            },
        );
    }

    private function issueJson(Request $request): JsonResponse
    {
        if ($denied = $this->machineDenial($request)) {
            return $denied;
        }

        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:120'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string', 'regex:/^[a-z][a-z0-9_]*:[a-z][a-z0-9_]+$/'],
            'rate_tier' => ['nullable', 'string', 'in:standard,professional,enterprise,partner'],
            'ttl_hours' => ['nullable', 'integer', 'min:1', 'max:8760'],
        ]);

        $tenant = Tenant::query()->find($validated['tenant_id']);
        if ($tenant === null || $tenant->state !== Tenant::STATE_ACTIVE) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Tenant unavailable.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $issuer = TenantMembership::query()
            ->where('tenant_id', (string) $tenant->getKey())
            ->where('user_id', (string) $request->user()->getAuthIdentifier())
            ->where('state', TenantMembership::STATE_ACTIVE)
            ->first();
        if ($issuer === null) {
            return new JsonResponse([
                'error' => ['code' => 'membership_inactive', 'message' => 'No active membership in this tenant.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        $secret = 'sk_live_'.Str::random(32);
        $key = new ApiKey();
        $key->forceFill([
            'tenant_id' => (string) $tenant->getKey(),
            'name' => $validated['name'],
            'prefix' => substr($secret, 0, 15),
            'secret_hash' => hash('sha256', $secret),
            'scopes' => array_values($validated['scopes'] ?? []),
            'rate_tier' => $validated['rate_tier'] ?? 'standard',
            'expires_at' => isset($validated['ttl_hours'])
                ? now()->addHours($validated['ttl_hours'])->toDateTimeString()
                : null,
            'created_by' => (string) $request->user()->getAuthIdentifier(),
        ]);
        $key->save();

        return new JsonResponse([
            'data' => [
                'id' => (string) $key->getKey(),
                'object' => 'api_key',
                'prefix' => $key->prefix,
                'secret' => $secret,
                'expires_at' => $key->expires_at,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function revoke(Request $request, string $id): JsonResponse|RedirectResponse
    {
        return WebMutations::negotiate(
            $request,
            fn (): JsonResponse => $this->revokeJson($request, $id),
            'API key revoked.',
        );
    }

    private function revokeJson(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->machineDenial($request)) {
            return $denied;
        }

        $key = ApiKey::query()->find($id);

        if ($key === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Key not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $issuer = TenantMembership::query()
            ->where('tenant_id', $key->tenant_id)
            ->where('user_id', (string) $request->user()->getAuthIdentifier())
            ->where('state', TenantMembership::STATE_ACTIVE)
            ->first();
        if ($issuer === null) {
            return new JsonResponse([
                'error' => ['code' => 'membership_inactive', 'message' => 'No active membership in this tenant.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        $key->forceFill(['revoked_at' => now()->toDateTimeString()])->save();

        return new JsonResponse([
            'data' => ['id' => (string) $key->getKey(), 'object' => 'api_key', 'revoked_at' => $key->revoked_at],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function rotate(Request $request, string $id): JsonResponse|RedirectResponse
    {
        return WebMutations::negotiate(
            $request,
            fn (): JsonResponse => $this->rotateJson($request, $id),
            static function (JsonResponse $result): string {
                $payload = $result->getData(true);
                $data = is_array($payload) && isset($payload['data']) && is_array($payload['data'])
                    ? $payload['data']
                    : [];
                $secret = isset($data['secret']) && is_string($data['secret']) ? $data['secret'] : '';

                return 'API key rotated — copy the new secret now, it is shown only once: '.$secret;
            },
        );
    }

    private function rotateJson(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->machineDenial($request)) {
            return $denied;
        }

        $key = ApiKey::query()->find($id);

        if ($key === null || ! $key->isUsable()) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Key not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $issuer = TenantMembership::query()
            ->where('tenant_id', $key->tenant_id)
            ->where('user_id', (string) $request->user()->getAuthIdentifier())
            ->where('state', TenantMembership::STATE_ACTIVE)
            ->first();
        if ($issuer === null) {
            return new JsonResponse([
                'error' => ['code' => 'membership_inactive', 'message' => 'No active membership in this tenant.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        $secret = 'sk_live_'.Str::random(32);
        $replacement = new ApiKey();
        $replacement->forceFill([
            'tenant_id' => $key->tenant_id,
            'name' => $key->name.' (rotated)',
            'prefix' => substr($secret, 0, 15),
            'secret_hash' => hash('sha256', $secret),
            'scopes' => $key->scopes ?? [],
            'rate_tier' => $key->rate_tier,
            'expires_at' => $key->expires_at?->toDateTimeString(),
            'created_by' => (string) $request->user()->getAuthIdentifier(),
        ]);
        $replacement->save();

        // Overlap window: old key stays valid 24h, then must be revoked.
        // The overlap deadline rides the old row's note via expires_at only
        // when it was previously null — existing expiry always wins.
        if ($key->expires_at === null) {
            $key->forceFill(['expires_at' => now()->addHours(24)->toDateTimeString()])->save();
        }

        return new JsonResponse([
            'data' => [
                'id' => (string) $replacement->getKey(),
                'object' => 'api_key',
                'prefix' => $replacement->prefix,
                'secret' => $secret,
                'supersedes' => (string) $key->getKey(),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }
}
