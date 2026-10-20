<?php

namespace App\Platform\Tenancy\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * The authenticated user has no active membership in the requested tenant
 * (TEN-006). Rendered as 403 `membership_inactive`.
 */
final class MembershipInactiveException extends HttpException
{
    public function __construct(string $state = 'missing')
    {
        parent::__construct(403, sprintf('Membership is not active in this tenant (%s).', $state));
    }
}
