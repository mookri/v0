<?php

namespace App\Platform\Api;

use Symfony\Component\HttpFoundation\Response;

/**
 * Small helper mapping HTTP statuses to canonical messages/reasons so the
 * error envelope never leaks framework internals.
 */
final class ResponseMessages
{
    public static function forStatus(int $status): string
    {
        return match ($status) {
            400 => 'Bad request.',
            401 => 'Unauthenticated.',
            403 => 'Forbidden.',
            404 => 'Not found.',
            405 => 'Method not allowed.',
            409 => 'Conflict.',
            412 => 'Precondition failed.',
            422 => 'Unprocessable entity.',
            429 => 'Too many requests.',
            503 => 'Service unavailable.',
            default => 'Request failed.',
        };
    }

    public static function reasonPhrase(int $status): string
    {
        return Response::$statusTexts[$status] ?? 'Error';
    }
}
