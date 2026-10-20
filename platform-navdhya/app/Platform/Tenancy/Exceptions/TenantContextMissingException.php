<?php

namespace App\Platform\Tenancy\Exceptions;

use RuntimeException;

/**
 * Thrown when tenant-scoped state is written or required outside a resolved
 * TenantContext (doc 02 §4 fail-closed, TEN-006/TEN-009). Callers MUST treat
 * this as a hard denial — never default to "all tenants".
 */
final class TenantContextMissingException extends RuntimeException
{
    public function __construct(string $operation = 'tenant-scoped operation')
    {
        parent::__construct(sprintf(
            'Tenant context is not resolved; refusing %s (fail-closed, doc 02 §4).',
            $operation,
        ));
    }
}
