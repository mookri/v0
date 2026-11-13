<?php

namespace App\Modules\Search\Observers;

use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Search\Models\SearchIndexOutbox;

class CatalogSearchObserver
{
    public function saved(CatalogItem $item): void
    {
        $tenantId = (string) ($item->tenant_id ?: config('platform.default_tenant', 'default'));

        SearchIndexOutbox::create([
            'tenant_id' => $tenantId,
            'entity_type' => 'puja_service',
            'entity_id' => (string) $item->id,
            'revision' => (int) ($item->revision ?? 1),
            'operation' => SearchIndexOutbox::OP_UPSERT,
            'status' => SearchIndexOutbox::STATUS_PENDING,
        ]);
    }

    public function deleted(CatalogItem $item): void
    {
        $tenantId = (string) ($item->tenant_id ?: config('platform.default_tenant', 'default'));

        SearchIndexOutbox::create([
            'tenant_id' => $tenantId,
            'entity_type' => 'puja_service',
            'entity_id' => (string) $item->id,
            'revision' => (int) ($item->revision ?? 1),
            'operation' => SearchIndexOutbox::OP_DELETE,
            'status' => SearchIndexOutbox::STATUS_PENDING,
        ]);
    }
}
