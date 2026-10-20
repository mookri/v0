<?php

namespace App\Platform\Tenancy\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * The resolved tenant is suspended (TEN-015 lifecycle states).
 * Rendered as 403 `tenant_suspended`.
 */
final class TenantSuspendedException extends HttpException
{
    public function __construct()
    {
        parent::__construct(403, 'The tenant is suspended.');
    }
}
