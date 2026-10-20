<?php

namespace App\Platform\Tenancy\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * The requested tenant is not valid for the presented credential
 * (TEN-006 fail-closed). Rendered as 401 `tenant_not_in_token`.
 */
final class TenantNotInTokenException extends HttpException
{
    public function __construct()
    {
        parent::__construct(401, 'The requested tenant is not valid for this credential.');
    }
}
