<?php

namespace App\Platform\Tenancy;

use App\Platform\Tenancy\Exceptions\TenantContextMissingException;
use Illuminate\Database\Eloquent\Model;

/**
 * BelongsToTenant kernel trait (doc 02 §5.1, TEN-009).
 *
 * Applied to every tenant-scoped model:
 *  - creating: fills tenant_id from TenantContext; throws when the context
 *    is unresolved (fail-closed). An explicitly present tenant_id attribute
 *    (kernel maintenance / platform-default rows with NULL tenant) is
 *    respected; every such bypass site is architecture-tested.
 *  - global scope: TenantScope binds tenant_id from context on every read,
 *    update and delete (fail-closed when unresolved).
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function (Model $model): void {
            if (array_key_exists('tenant_id', $model->getAttributes())) {
                return;
            }

            $context = app(TenantContext::class);

            if (! $context->isResolved()) {
                throw new TenantContextMissingException('creating '.$model->getTable());
            }

            $model->setAttribute('tenant_id', $context->tenantId());
        });

        static::addGlobalScope(new TenantScope());
    }
}
