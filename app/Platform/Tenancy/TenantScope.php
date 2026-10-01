<?php

namespace App\Platform\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope enforcing row-level tenant isolation (doc 02 §5, TEN-009).
 *
 * Bound from the ambient TenantContext on every read/update/delete:
 *  - resolved context   -> WHERE tenant_id = <context>
 *  - unresolved context -> fail-closed: matches NO rows, never "all tenants".
 *
 * Models exposing platform-default rows (tenant_id NULL — e.g. the policy
 * catalog's platform defaults, LD-15) implement IncludesPlatformDefaults so
 * NULL-tenant rows stay visible to every tenant while cross-tenant rows
 * remain hidden.
 */
final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);
        $column = $model->qualifyColumn('tenant_id');

        if (! $context->isResolved()) {
            // Fail-closed: an unresolvable context yields zero rows.
            $builder->whereRaw('0 = 1');

            return;
        }

        $tenantId = $context->tenantId();

        if ($model instanceof IncludesPlatformDefaults && $model->includesPlatformDefaults()) {
            $builder->where(function (Builder $query) use ($column, $tenantId): void {
                $query->whereNull($column)->orWhere($column, $tenantId);
            });

            return;
        }

        $builder->where($column, $tenantId);
    }
}
