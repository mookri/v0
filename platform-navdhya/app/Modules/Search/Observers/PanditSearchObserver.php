<?php

namespace App\Modules\Search\Observers;

use App\Modules\Pandit\Models\PanditProfile;
use App\Modules\Search\Models\SearchIndexOutbox;

class PanditSearchObserver
{
    public function saved(PanditProfile $pandit): void
    {
        $tenantId = (string) ($pandit->tenant_id ?: config('platform.default_tenant', 'default'));

        SearchIndexOutbox::create([
            'tenant_id' => $tenantId,
            'entity_type' => 'pandit',
            'entity_id' => (string) $pandit->id,
            'revision' => (int) ($pandit->revision ?? 1),
            'operation' => SearchIndexOutbox::OP_UPSERT,
            'status' => SearchIndexOutbox::STATUS_PENDING,
        ]);
    }

    public function deleted(PanditProfile $pandit): void
    {
        $tenantId = (string) ($pandit->tenant_id ?: config('platform.default_tenant', 'default'));

        SearchIndexOutbox::create([
            'tenant_id' => $tenantId,
            'entity_type' => 'pandit',
            'entity_id' => (string) $pandit->id,
            'revision' => (int) ($pandit->revision ?? 1),
            'operation' => SearchIndexOutbox::OP_DELETE,
            'status' => SearchIndexOutbox::STATUS_PENDING,
        ]);
    }
}
