<?php

namespace App\Providers;

use App\Platform\Audit\AuditWriter;
use App\Platform\Authz\AuthzCacheInvalidator;
use App\Platform\Authz\CapabilityAuthorizer;
use App\Platform\Authz\CapabilityParser;
use App\Platform\Authz\ConditionEvaluator;
use App\Platform\Authz\Console\AuthzExplainCommand;
use App\Platform\Authz\Console\SyncPermissionsCommand;
use App\Platform\Authz\DecisionCache;
use App\Platform\Authz\Models\CapabilityMetadata;
use App\Platform\Authz\Models\MembershipRoleAssignment;
use App\Platform\Authz\Models\Policy;
use App\Platform\Authz\Models\Role;
use App\Platform\Authz\Models\RolePermission;
use App\Platform\Authz\Models\SodRule;
use App\Platform\Authz\PolicyEvaluationEngine;
use App\Platform\Authz\PolicyValidator;
use App\Platform\Authz\PrincipalResolver;
use App\Platform\Tenancy\Console\TenantAdoptLegacyCommand;
use App\Platform\Tenancy\Console\TenantBackfillV2Command;
use App\Platform\Tenancy\Console\TenantProvisionSchemaCommand;
use App\Platform\Tenancy\Console\TenantDemoteCommand;
use App\Platform\Tenancy\Console\TenantPromoteCommand;
use App\Platform\Tenancy\Console\TenantPurgeSharedCommand;
use App\Platform\Modules\Console\ModuleListCommand;
use App\Platform\Api\Console\ApiComposeCommand;
use App\Platform\Console\CleanupExpiredCommand;
use App\Platform\Console\DoctorCommand;
use App\Platform\Modules\ManifestValidator;
use App\Platform\Modules\ModuleRegistry;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

/**
 * Boots the platform kernel module system (doc 01 §2) and the Phase 0K
 * authorization services: the ambient TenantContext (singleton — the
 * scope, authorizer, audit writer and resolution middleware MUST share one
 * instance), the capability parser (SEC-020), the policy grammar
 * validator (SEC-021/LD-15), the audit writer (K-025), the capability
 * authorizer (SEC-022 floor) and the SEC-022 precedence-chain engine with
 * its generation-based decision cache (T-0K01-02).
 */
final class PlatformServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ManifestValidator::class);

        $this->app->singleton(ModuleRegistry::class, function (Application $app): ModuleRegistry {
            return new ModuleRegistry($app, $app->make(ManifestValidator::class));
        });

        $this->app->singleton(TenantContext::class);
        $this->app->singleton(CapabilityParser::class);
        $this->app->singleton(PolicyValidator::class);
        $this->app->singleton(AuditWriter::class);
        $this->app->singleton(CapabilityAuthorizer::class);
        $this->app->singleton(ConditionEvaluator::class);
        $this->app->singleton(DecisionCache::class);
        $this->app->singleton(PolicyEvaluationEngine::class);
        $this->app->singleton(AuthzCacheInvalidator::class);
        $this->app->singleton(PrincipalResolver::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ModuleListCommand::class,
                DoctorCommand::class,
                CleanupExpiredCommand::class,
                ApiComposeCommand::class,
                SyncPermissionsCommand::class,
                AuthzExplainCommand::class,
                TenantBackfillV2Command::class,
                TenantProvisionSchemaCommand::class,
                TenantAdoptLegacyCommand::class,
                TenantPromoteCommand::class,
                TenantDemoteCommand::class,
                TenantPurgeSharedCommand::class,
            ]);
        }

        $this->registerAuthzCacheInvalidation($this->app->make(AuthzCacheInvalidator::class));

        // Discover + validate + order modules eagerly so manifest errors
        // surface at boot, not lazily on first use (doc 01 §2-2).
        $this->app->make(ModuleRegistry::class)->boot();
    }

    /**
     * Decision-cache invalidation hooks (AC-T-0K01-02-3): any mutation of
     * authorization state — a policy row, a grant row (role, permission,
     * membership assignment), a capability registry row, or an SoD rule
     * row — bumps the owning tenant's decision generation, so cached
     * decisions become unreachable within the same request cycle in
     * which the state changed. Platform-default rows (tenant_id NULL,
     * policies and SoD rules alike) bump the global generation. Bulk
     * query-builder mutations that bypass model events stay bounded by
     * the cache TTL (belt-and-braces).
     */
    private function registerAuthzCacheInvalidation(AuthzCacheInvalidator $invalidator): void
    {
        $invalidate = static function (Model $model) use ($invalidator): void {
            $tenantId = $model->getAttribute('tenant_id');

            $invalidator->invalidateForPolicy(is_string($tenantId) ? $tenantId : null);
        };

        $models = [
            Policy::class,
            Role::class,
            RolePermission::class,
            MembershipRoleAssignment::class,
            CapabilityMetadata::class,
            SodRule::class,
        ];

        foreach ($models as $modelClass) {
            $modelClass::saved($invalidate);
            $modelClass::deleted($invalidate);
        }
    }
}
