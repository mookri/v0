<?php

namespace App\Platform\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * JSON body size cap (process gate, zero runtime risk): rejects JSON
 * request bodies over 1MB with 413 before routing work happens. File
 * uploads ride multipart (never JSON) and keep their own per-endpoint
 * caps; this guard covers accidental or abusive JSON payloads only.
 */
final class LimitJsonBody
{
    public const MAX_BYTES = 1048576;

    public function handle(Request $request, Closure $next): Response
    {
        $contentType = (string) $request->headers->get('Content-Type', '');

        if (str_contains($contentType, 'application/json')) {
            $size = (int) $request->server('CONTENT_LENGTH', strlen((string) $request->getContent()));
            if ($size > self::MAX_BYTES) {
                return response()->json([
                    'error' => [
                        'code' => 'payload_too_large',
                        'message' => 'JSON body exceeds 1MB.',
                        'request_id' => (string) $request->attributes->get('request_id', ''),
                    ],
                ], 413);
            }
        }

        return $next($request);
    }
}
