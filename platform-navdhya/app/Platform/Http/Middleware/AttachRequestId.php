<?php

namespace App\Platform\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Edge middleware generating the request correlation id (doc 05 §11.1).
 *
 * The id propagates to structured logs, audit entries, event envelopes and
 * outgoing webhooks via the `Navdhya-Request-Id` response header and the
 * request attribute. Clients may supply their own id for tracing; it is
 * sanitized to a strict charset.
 */
final class AttachRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $this->sanitize($request->header('Navdhya-Request-Id'));

        if ($requestId === null) {
            $requestId = 'req_'.Str::lower(Str::ulid()->toBase32());
        }

        $request->attributes->set('request_id', $requestId);

        $response = $next($request);

        $response->headers->set('Navdhya-Request-Id', $requestId);

        return $response;
    }

    private function sanitize(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $trimmed = substr(trim($value), 0, 64);

        return preg_match('/^[A-Za-z0-9._-]+$/', $trimmed) === 1 ? $trimmed : null;
    }
}
