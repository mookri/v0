<?php

namespace App\Platform\Api;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * HTTP exception carrying an explicit error-envelope code (doc 06 §4.2)
 * for cases where the code is not derivable from the status alone
 * (e.g. 403 `state_forbidden` vs 403 `permission_missing`).
 */
final class CodedHttpException extends HttpException
{
    /**
     * @param  array<string, string>  $headers  extra response headers (e.g. WWW-Authenticate), merged onto the envelope response
     */
    public function __construct(
        int $status,
        public readonly string $envelopeCode,
        string $message,
        array $headers = [],
    ) {
        parent::__construct($status, $message, null, $headers);
    }
}
