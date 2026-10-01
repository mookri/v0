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
    public function __construct(
        int $status,
        public readonly string $envelopeCode,
        string $message,
    ) {
        parent::__construct($status, $message);
    }
}
