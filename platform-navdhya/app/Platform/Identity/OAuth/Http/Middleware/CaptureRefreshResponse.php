<?php

namespace App\Platform\Identity\OAuth\Http\Middleware;

use App\Platform\Identity\OAuth\RefreshIdempotencyStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * D4 lost-response recovery (ADR-0020): after a refresh exchange
 * succeeds, its response body is recorded against the request's
 * Idempotency-Key + fingerprint so a lost-response retry can recover it
 * (RecoverableRefreshTokenGrant replays the record). Recording is
 * first-write-wins and guarded — a dead store disables recovery only,
 * never the exchange itself. Replays are marked `Idempotent-Replay`
 * and never re-recorded: retries may not extend the window.
 */
final class CaptureRefreshResponse
{
    public function __construct(private readonly RefreshIdempotencyStore $store)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));

        if ($idempotencyKey === ''
            || (string) $request->input('grant_type', '') !== 'refresh_token'
            || $response->getStatusCode() !== 200
            || $response->headers->get('Idempotent-Replay') !== null
        ) {
            return $response;
        }

        $body = (string) $response->getContent();
        $decoded = json_decode($body, true);

        if (! is_array($decoded) || ! isset($decoded['access_token'], $decoded['refresh_token'])) {
            return $response;
        }

        $this->store->record($idempotencyKey, [
            'client_id' => $this->clientId($request),
            'refresh_token' => trim((string) $request->input('refresh_token', '')),
            'scope' => trim((string) $request->input('scope', '')),
        ], $body);

        return $response;
    }

    /**
     * League credential extraction mirrored (body parameter wins,
     * HTTP Basic username is the fallback) so the recorded fingerprint
     * equals the one the grant derives on a retry.
     */
    private function clientId(Request $request): string
    {
        $clientId = trim((string) $request->input('client_id', ''));

        if ($clientId !== '') {
            return $clientId;
        }

        $header = (string) $request->header('Authorization', '');
        if (stripos($header, 'Basic ') !== 0) {
            return '';
        }

        $decoded = base64_decode(substr($header, 6), true);
        if ($decoded === false || ! str_contains($decoded, ':')) {
            return '';
        }

        [$username] = explode(':', $decoded, 2);

        return $username;
    }
}
