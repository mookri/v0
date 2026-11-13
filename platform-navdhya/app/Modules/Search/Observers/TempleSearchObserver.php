<?php

namespace App\Modules\Search\Observers;

use App\Modules\Search\Models\SearchIndexOutbox;
use App\Modules\Temple\Models\Temple;

class TempleSearchObserver
{
    public function saved(Temple $temple): void
    {
        $tenantId = (string) ($temple->tenant_id ?: config('platform.default_tenant', 'default'));

        SearchIndexOutbox::create([
            'tenant_id' => $tenantId,
            'entity_type' => 'temple',
            'entity_id' => (string) $temple->id,
            'revision' => (int) ($temple->revision ?? 1),
            'operation' => SearchIndexOutbox::OP_UPSERT,
            'status' => SearchIndexOutbox::STATUS_PENDING,
        ]);
    }

    public function deleted(Temple $temple): void
    {
        $tenantId = (string) ($temple->tenant_id ?: config('platform.default_tenant', 'default'));

        SearchIndexOutbox::create([
            'tenant_id' => $tenantId,
            'entity_type' => 'temple',
            'entity_id' => (string) $temple->id,
            'revision' => (int) ($temple->revision ?? 1),
            'operation' => SearchIndexOutbox::OP_DELETE,
            'status' => SearchIndexOutbox::STATUS_PENDING,
        ]);
    }
}
