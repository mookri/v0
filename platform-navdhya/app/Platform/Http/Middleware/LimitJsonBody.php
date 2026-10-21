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
            // The declared length is used when it is present: reading
            // the body first would buffer the whole payload into
            // memory before the cap is applied, so a 500 MB body was
            // fully materialised and only then rejected. The body is
            // read ONLY when the length is unknown, and an empty
            // Content-Length is treated as unknown (it used to cast to
            // 0 and skip the check entirely).
            $declared = $request->server('CONTENT_LENGTH');
            $size = ($declared === null || $declared === '')
                ? strlen((string) $request->getContent())
                : (int) $declared;

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
