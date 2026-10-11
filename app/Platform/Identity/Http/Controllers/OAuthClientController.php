<?php

namespace App\Platform\Identity\Http\Controllers;

use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;

/**
 * OAuth client management (platform surface): list, create, revoke.
 * Mirrors the console commands `platform:client:create` and `platform:client:revoke`
 * but exposed through the HTTP API with proper capability gating.
 */
final class OAuthClientController
{
    public function __construct(
        private readonly \App\Platform\Audit\AuditWriter $audit,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    /**
     * @return array{tenant: \App\Platform\Tenancy\Models\Tenant, ownsContext: bool, context: \App\Platform\Tenancy\TenantContext}|JsonResponse
     */
    private function scopeToTenant(\Illuminate\Http\Request $request, string $tenantId): array|\Illuminate\Http\JsonResponse
    {
        $tenant = \App\Platform\Tenancy\Models\Tenant::query()->find($tenantId);
        if ($tenant === null || $tenant->state !== \App\Platform\Tenancy\Models\Tenant::STATE_ACTIVE) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Tenant unavailable.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $membership = \App\Platform\Tenancy\Models\TenantMembership::query()
            ->where('tenant_id', (string) $tenant->getKey())
            ->where('user_id', (string) $request->user()->getAuthIdentifier())
            ->where('state', \App\Platform\Tenancy\Models\TenantMembership::STATE_ACTIVE)
            ->first();
        if ($membership === null) {
            return new JsonResponse([
                'error' => ['code' => 'membership_inactive', 'message' => 'No active membership in this tenant.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        $context = app(\App\Platform\Tenancy\TenantContext::class);
        $ownsContext = ! $context->isResolved();
        if ($ownsContext) {
            $context->set(
                (string) $tenant->getKey(),
                (string) $membership->getKey(),
                $membership->org_node_id ?? null,
                (new \App\Platform\Tenancy\ConnectionResolver())->cachedFor($tenant),
                ['user_id' => (string) $request->user()->getAuthIdentifier()],
            );
        }

        return ['tenant' => $tenant, 'ownsContext' => $ownsContext, 'context' => $context];
    }

    /**
     * List OAuth clients for the authenticated user's tenant.
     * Returns client profiles with their Passport client data.
     */
    public function index(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate(['tenant_id' => ['required', 'string']]);

        $scoped = $this->scopeToTenant($request, $validated['tenant_id']);
        if ($scoped instanceof JsonResponse) {
            return $scoped;
        }

        try {
            $tenantId = (string) $scoped['tenant']->getKey();

            $profiles = \App\Platform\Identity\OAuth\OAuthClientProfile::query()
                ->where('tenant_id', $tenantId)
                ->orderBy('created_at')
                ->get();

            // Join with Passport client data for name/secret metadata
            $clientIds = $profiles->pluck('client_id')->toArray();
            $clients = \Laravel\Passport\Client::query()
                ->whereIn('id', $clientIds)
                ->get()
                ->keyBy('id');

            $data = $profiles->map(function (\App\Platform\Identity\OAuth\OAuthClientProfile $profile) use ($clients): array {
                $client = $clients->get($profile->client_id);
                return [
                    'id' => (string) $profile->getKey(),
                    'client_id' => $profile->client_id,
                    'client_type' => $profile->client_type,
                    'tenant_id' => $profile->tenant_id,
                    'name' => $client?->name ?? '',
                    'redirect_uris' => $client?->redirect_uris ?? [],
                    'grant_types' => $client?->grant_types ?? [],
                    'revoked' => $client?->revoked ?? false,
                    'allowed_grants' => $profile->allowed_grants ?? [],
                    'allowed_scopes' => $profile->allowed_scopes ?? [],
                    'audience' => $profile->audience,
                    'access_ttl_seconds' => $profile->access_ttl_seconds,
                    'refresh_ttl_seconds' => $profile->refresh_ttl_seconds,
                    'require_pkce' => $profile->require_pkce,
                    'rate_tier' => $profile->rate_tier,
                    'status' => $profile->status,
                ];
            })->values()->all();

            return new JsonResponse([
                'data' => $data,
                'meta' => ['request_id' => $this->requestId($request)],
            ]);
        } finally {
            if ($scoped['ownsContext']) {
                $scoped['context']->clear();
            }
        }
    }

    /**
     * Create a new OAuth client (mirrors `platform:client:create`).
     */
    public function store(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        return \App\Platform\Api\WebMutations::negotiate(
            $request,
            fn (): \Illuminate\Http\JsonResponse => $this->storeJson($request),
            'OAuth client created.',
        );
    }

    private function storeJson(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:first_party_web,first_party_service,partner,pos_device'],
            'grants' => ['sometimes', 'array'],
            'grants.*' => ['string'],
            'redirect_uris' => ['nullable', 'array'],
            'redirect_uris.*' => ['string'],
            'allowed_scopes' => ['nullable', 'array'],
            'allowed_scopes.*' => ['string'],
            'audience' => ['nullable', 'string'],
            'access_ttl_seconds' => ['nullable', 'integer', 'min:1'],
            'refresh_ttl_seconds' => ['nullable', 'integer', 'min:1'],
            'require_pkce' => ['boolean'],
            'rate_tier' => ['sometimes', 'string', 'in:standard,high'],
        ]);

        $scoped = $this->scopeToTenant($request, $validated['tenant_id']);
        if ($scoped instanceof JsonResponse) {
            return $scoped;
        }
        $tenantId = (string) $scoped['tenant']->getKey();

        $type = $validated['type'];

        // For partner/pos_device, the client MUST be bound to a tenant.
        // We use the scoping tenant (from membership) as the client's tenant.
        if (in_array($type, [\App\Platform\Identity\OAuth\OAuthClientProfile::TYPE_PARTNER, \App\Platform\Identity\OAuth\OAuthClientProfile::TYPE_POS_DEVICE], true)) {
            if ($tenantId === null) {
                return new JsonResponse([
                    'error' => ['code' => 'validation_failed', 'message' => 'Client tenant context is required for partner and pos_device clients.', 'request_id' => $this->requestId($request)],
                ], 422);
            }
        }

        try {
            if (\Laravel\Passport\Client::query()->where('name', $validated['name'])->where('revoked', false)->exists()) {
                return new JsonResponse([
                    'error' => ['code' => 'conflict', 'message' => 'An active client with this name already exists.', 'request_id' => $this->requestId($request)],
                ], 409);
            }

            $grants = $validated['grants'] ?? match ($validated['type']) {
                \App\Platform\Identity\OAuth\OAuthClientProfile::TYPE_FIRST_PARTY_WEB => ['authorization_code', 'refresh_token'],
                \App\Platform\Identity\OAuth\OAuthClientProfile::TYPE_FIRST_PARTY_SERVICE => ['client_credentials'],
                \App\Platform\Identity\OAuth\OAuthClientProfile::TYPE_PARTNER => ['client_credentials'],
                \App\Platform\Identity\OAuth\OAuthClientProfile::TYPE_POS_DEVICE => ['urn:ietf:params:oauth:grant-type:device_code', 'refresh_token'],
            };

            $redirectUris = $validated['redirect_uris'] ?? [];

            $plainSecret = \Illuminate\Support\Str::random(48);
            $client = new \Laravel\Passport\Client();
            $client->forceFill([
                'name' => $validated['name'],
                'secret' => $plainSecret,
                'redirect_uris' => $redirectUris,
                'grant_types' => $grants,
                'revoked' => false,
            ]);
            $client->save();

            $profile = new \App\Platform\Identity\OAuth\OAuthClientProfile();
            $profile->forceFill([
                'client_id' => (string) $client->getKey(),
                'client_type' => $validated['type'],
                'tenant_id' => $tenantId,
                'allowed_grants' => $grants,
                'allowed_scopes' => $validated['allowed_scopes'] ?? [],
                'audience' => $validated['audience'] ?? null,
                'access_ttl_seconds' => $validated['access_ttl_seconds'] ?? null,
                'refresh_ttl_seconds' => $validated['refresh_ttl_seconds'] ?? null,
                'require_pkce' => $validated['require_pkce'] ?? true,
                'rate_tier' => $validated['rate_tier'] ?? 'standard',
                'status' => 'active',
            ]);
            $profile->save();

            $this->audit->record(
                'oauth.client.created',
                ['module' => 'platform', 'resource_type' => 'oauth_client', 'resource_id' => (string) $client->getKey()],
                null,
                ['client_id' => (string) $client->getKey(), 'name' => $validated['name'], 'type' => $validated['type']],
            );

            return new JsonResponse([
                'data' => [
                    'object' => 'oauth_client',
                    'id' => (string) $client->getKey(),
                    'name' => $validated['name'],
                    'type' => $validated['type'],
                    'secret' => $plainSecret, // shown once
                    'redirect_uris' => $redirectUris,
                    'grants' => $grants,
                ],
                'meta' => ['request_id' => $this->requestId($request)],
            ], 201);
        } finally {
            if ($scoped['ownsContext']) {
                $scoped['context']->clear();
            }
        }
    }

    /**
     * Revoke an OAuth client (mirrors `platform:client:revoke`).
     */
    public function revoke(\Illuminate\Http\Request $request, string $id): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        return \App\Platform\Api\WebMutations::negotiate(
            $request,
            fn (): \Illuminate\Http\JsonResponse => $this->revokeJson($request, $id),
            'OAuth client revoked.',
        );
    }

    private function revokeJson(\Illuminate\Http\Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate(['tenant_id' => ['required', 'string']]);

        $scoped = $this->scopeToTenant($request, $validated['tenant_id']);
        if ($scoped instanceof JsonResponse) {
            return $scoped;
        }
        $tenantId = (string) $scoped['tenant']->getKey();

        try {
            $client = \Laravel\Passport\Client::query()->whereKey($id)->where('revoked', false)->first();

            if ($client === null) {
                return new JsonResponse([
                    'error' => ['code' => 'not_found', 'message' => 'OAuth client not found or already revoked.', 'request_id' => $this->requestId($request)],
                ], 404);
            }

            // Verify the client belongs to the tenant (via profile)
            $profile = \App\Platform\Identity\OAuth\OAuthClientProfile::query()
                ->where('client_id', (string) $client->getKey())
                ->where('tenant_id', $tenantId)
                ->first();

            if ($profile === null) {
                return new JsonResponse([
                    'error' => ['code' => 'not_found', 'message' => 'OAuth client not found in this tenant.', 'request_id' => $this->requestId($request)],
                ], 404);
            }

            $client->forceFill(['revoked' => true])->save();

            \App\Platform\Identity\OAuth\OAuthClientProfile::query()
                ->where('client_id', (string) $client->getKey())
                ->update(['status' => 'disabled']);

            // Revoke all access tokens and refresh tokens
            $accessIds = \Laravel\Passport\Passport::token()->newQuery()
                ->where('client_id', (string) $client->getKey())
                ->where('revoked', false)
                ->pluck('id');

            \Laravel\Passport\Passport::token()->newQuery()->whereIn('id', $accessIds)->update(['revoked' => true]);
            \Laravel\Passport\Passport::refreshToken()->newQuery()->whereIn('access_token_id', $accessIds)->update(['revoked' => true]);

            // Revoke device codes
            $deviceModel = \Laravel\Passport\Passport::deviceCodeModel();
            $deviceModel::query()
                ->where('client_id', (string) $client->getKey())
                ->where('revoked', false)
                ->update(['revoked' => true]);

            // Flush memoized client rows (Octane/queue workers)
            \Illuminate\Support\Once::flush();

            $this->audit->record(
                'oauth.client.revoked',
                ['module' => 'platform', 'resource_type' => 'oauth_client', 'resource_id' => (string) $id],
                null,
                ['client_id' => (string) $id],
            );

            return new JsonResponse([
                'data' => ['object' => 'oauth_client_revocation', 'revoked' => true, 'client_id' => $id],
                'meta' => ['request_id' => $this->requestId($request)],
            ]);
        } finally {
            if ($scoped['ownsContext']) {
                $scoped['context']->clear();
            }
        }
    }

    /**
     * Show a single OAuth client with its profile.
     */
    public function show(\Illuminate\Http\Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate(['tenant_id' => ['required', 'string']]);

        $scoped = $this->scopeToTenant($request, $validated['tenant_id']);
        if ($scoped instanceof JsonResponse) {
            return $scoped;
        }
        $tenantId = (string) $scoped['tenant']->getKey();

        try {
            $client = \Laravel\Passport\Client::query()->whereKey($id)->firstOrFail();

            // Verify tenant ownership
            $profile = \App\Platform\Identity\OAuth\OAuthClientProfile::query()
                ->where('client_id', (string) $client->getKey())
                ->where('tenant_id', (string) $scoped['tenant']->getKey())
                ->first();

            if ($profile === null) {
                return new JsonResponse([
                    'error' => ['code' => 'not_found', 'message' => 'OAuth client not found in this tenant.', 'request_id' => $this->requestId($request)],
                ], 404);
            }

            $data = $this->serializeClient($client, $profile);

            return new JsonResponse([
                'data' => $data,
                'meta' => ['request_id' => $this->requestId($request)],
            ]);
        } finally {
            if ($scoped['ownsContext']) {
                $scoped['context']->clear();
            }
        }
    }

    /**
     * @param \Laravel\Passport\Client $client
     * @param \App\Platform\Identity\OAuth\OAuthClientProfile $profile
     * @return array<string, mixed>
     */
    private function serializeClient(\Laravel\Passport\Client $client, \App\Platform\Identity\OAuth\OAuthClientProfile $profile): array
    {
        return [
            'id' => (string) $client->getKey(),
            'name' => $client->name,
            'type' => $profile->client_type,
            'client_id' => $profile->client_id,
            'redirect_uris' => $client->redirect_uris ?? [],
            'grant_types' => $client->grant_types ?? [],
            'revoked' => $client->revoked ?? false,
            'redirect_uris' => $client->redirect_uris ?? [],
            'grant_types' => $client->grant_types ?? [],
            'revoked' => $client->revoked ?? false,
            'allowed_grants' => $profile->allowed_grants ?? [],
            'allowed_scopes' => $profile->allowed_scopes ?? [],
            'audience' => $profile->audience,
            'access_ttl_seconds' => $profile->access_ttl_seconds,
            'refresh_ttl_seconds' => $profile->refresh_ttl_seconds,
            'require_pkce' => $profile->require_pkce,
            'rate_tier' => $profile->rate_tier,
            'status' => $profile->status,
        ];
    }
}
