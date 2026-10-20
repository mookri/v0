<?php

namespace App\Platform\Tenancy\Exceptions;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Tenant-scoped API request without the X-Tenant-Id header (TEN-006).
 * Rendered by the error envelope as 400 `tenant_header_missing`.
 */
final class TenantHeaderMissingException extends BadRequestHttpException
{
    public function __construct()
    {
        parent::__construct('Tenant-scoped API requests require the X-Tenant-Id header.');
    }
}
