<?php

namespace App\Platform\Identity\OAuth\Http\Controllers;

use App\Platform\Identity\OAuth\OAuthClientProfile;
use App\Platform\Identity\OAuth\ResolvesOAuthTokens;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Client;

/**
 * RFC 7662 token introspection (SEC-015 §8.2): restricted to
 * first-party service clients (the future extracted-services
 * mechanism — carve-out rule C4). Client-authenticated; per-client
 * throttled; every call logged. Unknown, expired, and revoked tokens
 * answer `{active: false}` without distinguishing (no oracle).
 * Errors use league's shape: protocol surface, not the kernel envelope.
 */
final class IntrospectController
{
    use ResolvesOAuthTokens;

    public function __invoke(Request $request): JsonResponse
    {
        [$validated, $denial] = $this->validateProtocol($request, [
            'token' => ['required', 'string'],
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
        ]);

        if ($denial !== null) {
            return $denial;
        }

        $client = Client::query()->whereKey($validated['client_id'])->first();

        if ($client === null
            || (bool) $client->getAttribute('revoked')
            || $client->getAttribute('secret') === null
            || ! Hash::check($validated['client_secret'], (string) $client->getAttribute('secret'))) {
            return new JsonResponse([
                'error' => 'invalid_client',
                'error_description' => 'Client authentication failed.',
                'message' => 'Client authentication failed.',
            ], 401);
        }

        $profile = OAuthClientProfile::query()->where('client_id', (string) $client->getKey())->first();

        if ($profile === null || $profile->client_type !== OAuthClientProfile::TYPE_FIRST_PARTY_SERVICE) {
            return new JsonResponse([
                'error' => 'insufficient_scope',
                'error_description' => 'Introspection is restricted to first-party service clients.',
                'message' => 'Introspection is restricted to first-party service clients.',
            ], 403);
        }

        $answer = $this->introspect($validated['token']);

        Log::info('OAuth token introspected', [
            'client_id' => (string) $client->getKey(),
            'active' => $answer['active'],
        ]);

        return new JsonResponse($answer);
    }

    /**
     * @return array<string, mixed>
     */
    private function introspect(string $token): array
    {
        // Access leg (9c): signature-verified FIRST — a forged JWT
        // carrying a real jti must not resurrect a live row's claims
        // (RFC 7662: an invalid token answers active:false). The row
        // stays the authority for revocation/expiry; verified claims
        // are the authority for scope display.
        $claims = \App\Platform\Identity\PassportJwtVerifier::verify($token, time());

        if ($claims !== null) {
            $access = \Laravel\Passport\Passport::token()->newQuery()
                ->whereKey((string) $claims['jti'])
                ->first();

            if ($access !== null
                && ! (bool) $access->getAttribute('revoked')
                && ! $this->expired($access->getAttribute('expires_at'))
                && $this->ownerIsActive($access->getAttribute('user_id'))) {
                return [
                    'active' => true,
                    'scope' => implode(' ', $claims['scopes']),
                    'client_id' => (string) $access->getAttribute('client_id'),
                    'sub' => (string) $access->getAttribute('user_id'),
                    'exp' => $this->timestamp($access->getAttribute('expires_at')),
                    'iat' => $this->timestamp($access->getAttribute('created_at')),
                    'token_type' => 'Bearer',
                ];
            }

            // A verified-signature JWT is definitively an access
            // token — no refresh fallback can revive it.
            return ['active' => false];
        }

        // Refresh leg: league-encrypted (never a JWT), row-authoritative.
        $refresh = $this->findRefreshToken($token);

        if ($refresh !== null && ! (bool) $refresh->getAttribute('revoked') && ! $this->expired($refresh->getAttribute('expires_at'))) {
            $owner = \Laravel\Passport\Passport::token()->newQuery()
                ->whereKey($refresh->getAttribute('access_token_id'))
                ->first();

            if ($owner !== null && ! $this->ownerIsActive($owner->getAttribute('user_id'))) {
                return ['active' => false];
            }

            return [
                'active' => true,
                'client_id' => $owner !== null ? (string) $owner->getAttribute('client_id') : '',
                'exp' => $this->timestamp($refresh->getAttribute('expires_at')),
                'iat' => $this->timestamp($refresh->getAttribute('created_at')),
                'token_type' => 'Bearer',
            ];
        }

        return ['active' => false];
    }

    /**
     * Lifecycle gate (9c): the resource server denies suspended and
     * deactivated owners live on every request, so introspection must
     * not answer active:true for credentials the audience would
     * refuse. Machine tokens (no user owner) skip this leg.
     */
    private function ownerIsActive(mixed $userId): bool
    {
        if ($userId === null || $userId === '') {
            return true;
        }

        $user = \App\Models\User::query()->find((string) $userId);

        return $user !== null && $user->isActive();
    }

    /**
     * Fail closed (9c): a row without an expiry is not "valid
     * forever" — no expiry means no proof of bounded lifetime.
     */
    private function expired(mixed $at): bool
    {
        if ($at === null) {
            return true;
        }

        $timestamp = $this->timestamp($at);

        return $timestamp === null || $timestamp <= time();
    }

    private function timestamp(mixed $value): ?int
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        if (is_string($value)) {
            $parsed = strtotime($value);

            return $parsed === false ? null : $parsed;
        }

        return null;
    }
}
