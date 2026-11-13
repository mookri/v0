<?php

namespace App\Modules\Jyotish\Http;

use Illuminate\Http\JsonResponse;

/**
 * RFC 7807 / RFC 9457 Problem Details responder for Jyotish API surfaces.
 * Standardizes all client/server errors with machine-readable error codes.
 */
final class JyotishProblemResponder
{
    /**
     * @param int $status HTTP status code
     * @param string $code Domain error code (e.g. "ambiguous_place", "validation_failed")
     * @param string $title Short title
     * @param string $detail Human-readable diagnostic description
     * @param string|null $instance URI path where problem occurred
     * @param array<string, mixed> $extra Supplementary payload attributes
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
     * Alias for create().
     */
    public static function respond(
        int $status,
        string $errorCode,
        string $title,
        string $detail,
        ?string $instance = null,
        array $extra = []
    ): JsonResponse {
        return self::create($status, $errorCode, $title, $detail, $instance, $extra);
    }

    /**
     * @param string $detail
     * @param array<string, mixed> $invalidParam
     */
    public static function validationFailed(string $detail, array $invalidParam = []): JsonResponse
    {
        $extra = [];
        if (! empty($invalidParam)) {
            $extra['invalid_params'] = [$invalidParam];
            $extra['error'] = [
                'code' => 'validation_failed',
                'invalid_params' => [$invalidParam],
            ];
        } else {
            $extra['error'] = [
                'code' => 'validation_failed',
            ];
        }

        return self::create(
            status: 422,
            code: 'validation_failed',
            title: 'Unprocessable Entity',
            detail: $detail,
            extra: $extra,
        );
    }
}

