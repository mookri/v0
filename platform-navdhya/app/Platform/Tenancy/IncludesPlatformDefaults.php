<?php

namespace App\Platform\Tenancy;

/**
 * Marks tenant-scoped models that also expose platform-default rows
 * (tenant_id NULL) which are readable by every tenant (LD-15 platform
 * defaults). Mutating platform-default rows still requires platform-level
 * authority and is rejected on the tenant admin surfaces.
 */
interface IncludesPlatformDefaults
{
    public function includesPlatformDefaults(): bool;
}
