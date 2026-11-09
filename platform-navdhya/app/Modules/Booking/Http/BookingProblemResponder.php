<?php

namespace App\Modules\Booking\Http;

use Illuminate\Http\JsonResponse;

/**
 * RFC 7807 / RFC 9457 Problem Details for HTTP APIs responder.
 * Standardizes all 4xx/5xx responses on V2 API surfaces.
 */
final class BookingProblemResponder
{
    /**
     * @param array<string, mixed> $extra
     */
    public static function create(
        int $status,
        string $code,
        string $title,
        string $detail,
        ?string $instance = null,
        array $extra = []
    ): JsonResponse {
        $requestId = (string) (request()?->attributes?->get('request_id') ?? '');
        $payload = [
            'type' => 'https://api.navdhya.com/problems/' . $code,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'code' => $code,
            'instance' => $instance ?? (string) request()?->path(),
            'request_id' => $requestId,
        ];

        if (! empty($extra)) {
            $payload = array_merge($payload, $extra);
        }

        return new JsonResponse($payload, $status, [
            'Content-Type' => 'application/problem+json',
        ]);
    }

    /**
     * Parse weak or strong If-Match header into integer version number.
     * Supports formats: W/"v2", W/"2", "v2", "2", 2.
     */
    public static function parseIfMatch(?string $header): ?int
    {
        if ($header === null) {
            return null;
        }

        $trimmed = trim($header);
        if ($trimmed === '') {
            return null;
        }

        // Weak: W/"v2" or W/"2"
        if (preg_match('/^W\/"v?(\d+)"$/i', $trimmed, $matches)) {
            return (int) $matches[1];
        }

        // Strong: "v2" or "2"
        if (preg_match('/^"v?(\d+)"$/i', $trimmed, $matches)) {
            return (int) $matches[1];
        }

        if (is_numeric($trimmed)) {
            return (int) $trimmed;
        }

        return null;
    }
}

