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
        $access = $this->findAccessToken($token);

        if ($access !== null && ! (bool) $access->getAttribute('revoked') && ! $this->expired($access->getAttribute('expires_at'))) {
            return [
                'active' => true,
                'scope' => implode(' ', $this->jwtScopes($token)),
                'client_id' => (string) $access->getAttribute('client_id'),
                'sub' => (string) $access->getAttribute('user_id'),
                'exp' => $this->timestamp($access->getAttribute('expires_at')),
                'iat' => $this->timestamp($access->getAttribute('created_at')),
                'token_type' => 'Bearer',
            ];
        }

        $refresh = $this->findRefreshToken($token);

        if ($refresh !== null && ! (bool) $refresh->getAttribute('revoked') && ! $this->expired($refresh->getAttribute('expires_at'))) {
            return [
                'active' => true,
                'client_id' => $this->refreshClientId($refresh),
                'exp' => $this->timestamp($refresh->getAttribute('expires_at')),
                'iat' => $this->timestamp($refresh->getAttribute('created_at')),
                'token_type' => 'Bearer',
            ];
        }

        return ['active' => false];
    }

    /**
     * Scopes live in the JWT, not the database: decode descriptively
     * (the DB row above is the authority for `active`).
     *
     * @return list<string>
     */
    private function jwtScopes(string $token): array
    {
        if (substr_count($token, '.') !== 2) {
            return [];
        }

        $claims = json_decode(base64_decode(strtr(explode('.', $token)[1], '-_', '+/'), true) ?: '[]', true);

        if (! is_array($claims) || ! isset($claims['scopes']) || ! is_array($claims['scopes'])) {
            return [];
        }

        return array_values(array_filter($claims['scopes'], 'is_string'));
    }

    private function refreshClientId(mixed $refresh): string
    {
        $access = \Laravel\Passport\Passport::token()->newQuery()->whereKey($refresh->getAttribute('access_token_id'))->first();

        return $access !== null ? (string) $access->getAttribute('client_id') : '';
    }

    private function expired(mixed $at): bool
    {
        if ($at === null) {
            return false;
        }

        $timestamp = $this->timestamp($at);

        return $timestamp !== null && $timestamp <= time();
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
